<?php
/**
 * link_zip_brochures.php
 *
 * Upload a ZIP of PDF brochures, auto extract, and link each PDF to the
 * matching product in the `products` table (product_documents.combined_pdf).
 *
 * Matching strategy:
 *   1. Try to pull a model ID out of the filename (e.g. "275-150.pdf") and
 *      match it against products.model_id (exact, then prefix).
 *   2. If no model ID pattern is found (most DAC "Cutaway / Dissectible"
 *      files are named by product title, not model number), clean up the
 *      filename and fuzzy match it against title_only / title_description
 *      using PHP similar_text() scoring. Products that already HAVE a PDF
 *      linked are scored but flagged, since this tool is meant to fill in
 *      MISSING brochures first.
 *
 * Nothing is written to the database until you review the suggested
 * matches on screen and click "Link Selected". You can override any
 * suggestion with the search box before confirming.
 *
 * UPLOAD TO: public_html/portal/quotation/link_zip_brochures.php
 * OPEN IN BROWSER: https://ti-kitmeer.com/portal/quotation/link_zip_brochures.php
 */

// Never let PHP dump HTML warnings/notices into what should be a JSON
// response — capture everything and, for JSON actions, convert any error
// (including fatals) into a clean JSON error payload instead of raw HTML.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_time_limit(0);
ob_start();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$isJsonAction = in_array($action, ['analyze', 'confirm', 'search_products'], true);

function json_out($data) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($isJsonAction) {
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) return false;
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'PHP fatal error: ' . $err['message'] . ' in ' . basename($err['file']) . ' line ' . $err['line']
            ]);
        }
    });
}

try {
    require_once __DIR__ . '/db.php'; // must expose getDB(): PDO
} catch (Throwable $e) {
    if ($isJsonAction) json_out(['error' => 'Could not load db.php: ' . $e->getMessage()]);
    throw $e;
}

if (!function_exists('getDB')) {
    if ($isJsonAction) json_out(['error' => 'db.php loaded but getDB() function is not defined. Check the require path.']);
}

if (!class_exists('ZipArchive')) {
    if ($isJsonAction && $action === 'analyze') {
        json_out(['error' => 'PHP ZipArchive extension is not enabled on this server. Ask Hostinger support to enable php-zip, or I can rewrite this to shell out to the unzip binary instead.']);
    }
}

define('TMP_ROOT', __DIR__ . '/tmp_zip_batches');
define('FLYERS_ROOT', __DIR__ . '/flyers');

if (!is_dir(TMP_ROOT)) { @mkdir(TMP_ROOT, 0775, true); }

/* -------------------------------------------------------------------- */
/* Helpers                                                               */
/* -------------------------------------------------------------------- */

function clean_title_from_filename(string $filename): string {
    $name = preg_replace('/\.pdf$/i', '', $filename);
    // common export junk: #U00bd_ (½), #U00be_ (¾), stray underscores used for inch marks
    $name = str_replace(['#U00bd', '#U00be'], ['1/2', '3/4'], $name);
    $name = preg_replace('/_+/', ' ', $name);
    $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name); // trailing " (1)", " (2)"
    $name = preg_replace('/[.,]+$/', '', trim($name));
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function extract_model_id(string $filename): ?string {
    // matches patterns like 275-150, 275-160D, T7031-XAF, 202-000
    if (preg_match('/\b([A-Z]{0,3}\d{2,4}-[A-Z0-9\/]{2,6})\b/i', $filename, $m)) {
        return strtoupper($m[1]);
    }
    return null;
}

function find_by_model_id(PDO $db, string $modelId): array {
    $stmt = $db->prepare("SELECT model_id, title_only, manufacturer FROM products WHERE model_id = :m LIMIT 1");
    $stmt->execute([':m' => $modelId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return [$row];

    $stmt = $db->prepare("SELECT model_id, title_only, manufacturer FROM products WHERE model_id LIKE :m LIMIT 5");
    $stmt->execute([':m' => $modelId . '%']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function has_existing_doc(PDO $db, string $modelId): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM product_documents WHERE model_id = :m AND combined_pdf IS NOT NULL AND combined_pdf != ''");
    $stmt->execute([':m' => $modelId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Fuzzy match a cleaned filename against product titles.
 * Returns up to $limit candidates sorted by score desc, each with a
 * `has_pdf` flag so the UI can warn about overwriting an existing brochure.
 */
function fuzzy_match_products(PDO $db, string $cleanName, int $limit = 5): array {
    // Pull the DAC catalog (title text search) — restrict to DAC Worldwide by default
    // since these filenames follow DAC's "X Cutaway / Dissectible" naming convention.
    $stmt = $db->prepare("
        SELECT p.model_id, p.title_only, p.title_description, p.manufacturer,
               (SELECT COUNT(*) FROM product_documents d
                 WHERE d.model_id COLLATE utf8mb4_unicode_ci = p.model_id COLLATE utf8mb4_unicode_ci
                   AND d.combined_pdf IS NOT NULL AND d.combined_pdf != '') AS has_pdf
        FROM products p
        WHERE p.manufacturer LIKE 'DACW%'
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $needle = strtolower($cleanName);
    $scored = [];
    foreach ($rows as $row) {
        $titleA = strtolower($row['title_only'] ?? '');
        $titleB = strtolower($row['title_description'] ?? '');
        similar_text($needle, $titleA, $pctA);
        similar_text($needle, $titleB, $pctB);
        $pct = max($pctA, $pctB);
        if ($pct < 30) continue;
        $scored[] = [
            'model_id' => $row['model_id'],
            'title'    => $row['title_only'] ?: $row['title_description'],
            'score'    => round($pct, 1),
            'has_pdf'  => (bool)$row['has_pdf'],
        ];
    }
    usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($scored, 0, $limit);
}

/* -------------------------------------------------------------------- */
/* AJAX: live search box for manual override                           */
/* -------------------------------------------------------------------- */
if ($action === 'search_products') {
    try {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') json_out([]);
        $db = getDB();
        $stmt = $db->prepare("
            SELECT p.model_id, p.title_only, p.title_description, p.manufacturer,
                   (SELECT COUNT(*) FROM product_documents d
                     WHERE d.model_id COLLATE utf8mb4_unicode_ci = p.model_id COLLATE utf8mb4_unicode_ci
                       AND d.combined_pdf IS NOT NULL AND d.combined_pdf != '') AS has_pdf
            FROM products p
            WHERE p.model_id LIKE :q OR p.title_only LIKE :q2 OR p.title_description LIKE :q3
            LIMIT 25
        ");
        $like = '%' . $q . '%';
        $stmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'model_id' => $r['model_id'],
                'title'    => $r['title_only'] ?: $r['title_description'],
                'manufacturer' => $r['manufacturer'],
                'has_pdf'  => (bool)$r['has_pdf'],
            ];
        }
        json_out($out);
    } catch (Throwable $e) {
        json_out(['error' => 'search_products failed: ' . $e->getMessage()]);
    }
}

/* -------------------------------------------------------------------- */
/* Step 1: upload + extract zip + analyze matches                       */
/* -------------------------------------------------------------------- */
if ($action === 'analyze' && isset($_FILES['zipfile'])) {
  try {
    $db = getDB();

    $batchId = bin2hex(random_bytes(8));
    $batchDir = TMP_ROOT . '/' . $batchId;
    mkdir($batchDir, 0775, true);

    $zipTmpPath = $_FILES['zipfile']['tmp_name'];
    if (!$zipTmpPath || $_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) {
        json_out(['error' => 'Upload failed (error code ' . ($_FILES['zipfile']['error'] ?? '?') . ')']);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipTmpPath) !== true) {
        json_out(['error' => 'Could not open ZIP file.']);
    }

    $results = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        $baseName = basename($entryName);
        if ($baseName === '' || substr($baseName, -4) !== '.pdf') continue; // skip folders, __MACOSX junk, non-pdf
        if (stripos($entryName, '__MACOSX') !== false) continue;

        $destPath = $batchDir . '/' . $baseName;
        $stream = $zip->getStream($entryName);
        if (!$stream) continue;
        $out = fopen($destPath, 'w');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        $modelId = extract_model_id($baseName);
        $candidates = [];

        if ($modelId) {
            $dbMatches = find_by_model_id($db, $modelId);
            foreach ($dbMatches as $m) {
                $candidates[] = [
                    'model_id' => $m['model_id'],
                    'title'    => $m['title_only'],
                    'score'    => 100.0,
                    'has_pdf'  => has_existing_doc($db, $m['model_id']),
                ];
            }
        }

        if (empty($candidates)) {
            $clean = clean_title_from_filename($baseName);
            $candidates = fuzzy_match_products($db, $clean);
        }

        $results[] = [
            'filename'   => $baseName,
            'clean_name' => clean_title_from_filename($baseName),
            'extracted_model' => $modelId,
            'candidates' => $candidates,
        ];
    }
    $zip->close();

    json_out(['batch_id' => $batchId, 'files' => $results]);
  } catch (Throwable $e) {
    json_out(['error' => 'analyze failed: ' . $e->getMessage()]);
  }
}

/* -------------------------------------------------------------------- */
/* Step 2: confirm + link (move files, write DB rows)                   */
/* -------------------------------------------------------------------- */
if ($action === 'confirm') {
  try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    $batchId = $payload['batch_id'] ?? '';
    $subfolder = preg_replace('/[^a-z0-9_\-]/i', '', $payload['subfolder'] ?? 'dac');
    $decisions = $payload['decisions'] ?? []; // [{filename, model_id}]

    if (!$batchId || !preg_match('/^[a-f0-9]{16}$/', $batchId)) {
        json_out(['error' => 'Invalid batch id.']);
    }
    $batchDir = TMP_ROOT . '/' . $batchId;
    if (!is_dir($batchDir)) {
        json_out(['error' => 'Batch expired or not found. Please re-upload the ZIP.']);
    }

    $destDir = FLYERS_ROOT . '/' . $subfolder;
    if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }

    $db = getDB();
    $ins = $db->prepare("
        INSERT INTO product_documents (model_id, combined_pdf)
        VALUES (:m, :c)
        ON DUPLICATE KEY UPDATE combined_pdf = :c2, updated_at = CURRENT_TIMESTAMP
    ");

    $linked = [];
    $errors = [];

    foreach ($decisions as $d) {
        $filename = basename($d['filename'] ?? '');
        $modelId = trim($d['model_id'] ?? '');
        if ($filename === '' || $modelId === '') continue;

        $srcPath = $batchDir . '/' . $filename;
        if (!file_exists($srcPath)) {
            $errors[] = "$filename: source file missing from batch";
            continue;
        }

        // keep destination filename stable/sane: use the model id + original extension
        $safeModel = preg_replace('/[^A-Za-z0-9_\-]/', '_', $modelId);
        $destFilename = $safeModel . '.pdf';
        $destPath = $destDir . '/' . $destFilename;

        if (!rename($srcPath, $destPath)) {
            $errors[] = "$filename: could not move file into $subfolder/";
            continue;
        }

        $webPath = 'flyers/' . $subfolder . '/' . $destFilename;
        try {
            $ins->execute([':m' => $modelId, ':c' => $webPath, ':c2' => $webPath]);
            $linked[] = ['model_id' => $modelId, 'file' => $destFilename];
        } catch (Exception $e) {
            $errors[] = "$filename: DB error - " . $e->getMessage();
        }
    }

    // clean up any leftover unmatched files + the temp dir
    array_map('unlink', glob($batchDir . '/*.pdf') ?: []);
    @rmdir($batchDir);

    json_out(['linked' => $linked, 'errors' => $errors]);
  } catch (Throwable $e) {
    json_out(['error' => 'confirm failed: ' . $e->getMessage()]);
  }
}

/* -------------------------------------------------------------------- */
/* Default: render the upload + review page                             */
/* -------------------------------------------------------------------- */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Link ZIP Brochures — TI Kitmeer</title>
<style>
  body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; background:#0b1220; color:#d7e3ee; margin:0; padding:24px; }
  h1 { font-size:20px; color:#7fc4ff; }
  .card { background:#121a2b; border:1px solid #223049; border-radius:10px; padding:20px; margin-bottom:16px; }
  select, input[type=file], button, input[type=text] { font-size:14px; padding:8px 10px; border-radius:6px; border:1px solid #2c3c5a; background:#0e1626; color:#e5edf7; }
  button { background:#2f7de1; border:none; cursor:pointer; font-weight:600; }
  button:disabled { background:#2c3c5a; cursor:not-allowed; }
  button.secondary { background:#3a4b6b; }
  table { width:100%; border-collapse:collapse; margin-top:14px; font-size:13px; }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid #223049; vertical-align:top; }
  th { color:#8fa6c4; font-weight:600; }
  .score-high { color:#2dd4a0; font-weight:600; }
  .score-mid { color:#d4a020; font-weight:600; }
  .score-low { color:#e08080; font-weight:600; }
  .has-pdf-warn { color:#e08080; font-size:11px; }
  .filename { color:#8fa6c4; font-size:12px; }
  .status-ok { color:#2dd4a0; }
  .status-err { color:#e08080; }
  #summary { margin-top:16px; font-size:14px; }
  .search-box { width:260px; }
  .autocomplete-list { position:absolute; background:#0e1626; border:1px solid #2c3c5a; max-height:180px; overflow-y:auto; z-index:10; width:260px; }
  .autocomplete-list div { padding:6px 8px; cursor:pointer; font-size:12px; }
  .autocomplete-list div:hover { background:#2f7de1; }
</style>
</head>
<body>

<h1>📎 Link ZIP Brochures</h1>

<div class="card">
  <p>Upload a ZIP of PDF brochures. Files named with a model ID (e.g. <code>275-150.pdf</code>) match directly.
  Files named by product title (e.g. <code>Globe Valve Dissectible.pdf</code>) are fuzzy matched against the DAC Worldwide
  catalog — review the suggestions below before linking anything.</p>
  <form id="uploadForm">
    <label>Destination subfolder:
      <select id="subfolder">
        <option value="dac" selected>dac</option>
        <option value="amatrol">amatrol</option>
        <option value="bayport">bayport</option>
        <option value="uploads">uploads</option>
      </select>
    </label>
    &nbsp;&nbsp;
    <input type="file" id="zipfile" accept=".zip" required>
    &nbsp;&nbsp;
    <button type="submit" id="analyzeBtn">Analyze ZIP</button>
  </form>
</div>

<div id="reviewCard" class="card" style="display:none;">
  <h3>Review matches</h3>
  <table id="reviewTable">
    <thead>
      <tr><th>File</th><th>Suggested product</th><th>Score</th><th>Override</th><th>Link?</th></tr>
    </thead>
    <tbody></tbody>
  </table>
  <div style="margin-top:16px;">
    <button id="confirmBtn">Link Selected</button>
    <button type="button" class="secondary" onclick="location.reload()">Start Over</button>
  </div>
  <div id="summary"></div>
</div>

<script>
let batchId = null;
let fileResults = [];

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fileInput = document.getElementById('zipfile');
  if (!fileInput.files.length) return;
  const btn = document.getElementById('analyzeBtn');
  btn.disabled = true; btn.textContent = 'Analyzing…';

  const fd = new FormData();
  fd.append('action', 'analyze');
  fd.append('zipfile', fileInput.files[0]);

  try {
    const res = await fetch('?action=analyze', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) { alert(data.error); btn.disabled=false; btn.textContent='Analyze ZIP'; return; }
    batchId = data.batch_id;
    fileResults = data.files;
    renderReview();
  } catch (err) {
    alert('Error analyzing ZIP: ' + err);
  }
  btn.disabled = false; btn.textContent = 'Analyze ZIP';
});

function scoreClass(score) {
  if (score >= 80) return 'score-high';
  if (score >= 50) return 'score-mid';
  return 'score-low';
}

function renderReview() {
  document.getElementById('reviewCard').style.display = 'block';
  const tbody = document.querySelector('#reviewTable tbody');
  tbody.innerHTML = '';

  fileResults.forEach((f, idx) => {
    const top = f.candidates[0] || null;
    const tr = document.createElement('tr');
    tr.dataset.filename = f.filename;

    const modelCell = top ? top.model_id : '';
    const titleCell = top ? top.title : '(no match found — search manually)';
    const scoreCell = top ? top.score + '%' : '—';
    const warn = top && top.has_pdf ? '<div class="has-pdf-warn">⚠ already has a PDF linked</div>' : '';

    tr.innerHTML = `
      <td>${f.filename}<div class="filename">${f.clean_name}</div></td>
      <td><span class="match-title">${titleCell}</span><div class="filename match-model">${modelCell}</div>${warn}</td>
      <td class="${top ? scoreClass(top.score) : ''}">${scoreCell}</td>
      <td>
        <input type="text" class="search-box" placeholder="search model or title…" data-idx="${idx}">
        <div class="autocomplete-list" style="display:none;"></div>
      </td>
      <td><input type="checkbox" class="link-check" ${top ? 'checked' : ''}></td>
    `;
    tbody.appendChild(tr);
  });

  document.querySelectorAll('.search-box').forEach(input => {
    let timer = null;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      const list = input.nextElementSibling;
      if (q.length < 2) { list.style.display = 'none'; return; }
      timer = setTimeout(async () => {
        const res = await fetch('?action=search_products&q=' + encodeURIComponent(q));
        const items = await res.json();
        list.innerHTML = '';
        items.forEach(it => {
          const div = document.createElement('div');
          const warn = it.has_pdf ? ' ⚠ has PDF' : '';
          div.textContent = `${it.model_id} — ${it.title}${warn}`;
          div.addEventListener('click', () => {
            const tr = input.closest('tr');
            tr.querySelector('.match-title').textContent = it.title;
            tr.querySelector('.match-model').textContent = it.model_id;
            tr.querySelector('.link-check').checked = true;
            list.style.display = 'none';
            input.value = '';
          });
          list.appendChild(div);
        });
        list.style.display = items.length ? 'block' : 'none';
      }, 250);
    });
  });
}

document.getElementById('confirmBtn').addEventListener('click', async () => {
  const rows = document.querySelectorAll('#reviewTable tbody tr');
  const decisions = [];
  rows.forEach(tr => {
    const checked = tr.querySelector('.link-check').checked;
    const modelId = tr.querySelector('.match-model').textContent.trim();
    if (checked && modelId) {
      decisions.push({ filename: tr.dataset.filename, model_id: modelId });
    }
  });
  if (!decisions.length) { alert('Nothing checked to link.'); return; }

  const btn = document.getElementById('confirmBtn');
  btn.disabled = true; btn.textContent = 'Linking…';

  const res = await fetch('?action=confirm', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      batch_id: batchId,
      subfolder: document.getElementById('subfolder').value,
      decisions
    })
  });
  const data = await res.json();
  btn.disabled = false; btn.textContent = 'Link Selected';

  const summary = document.getElementById('summary');
  let html = `<p class="status-ok">Linked ${data.linked.length} file(s).</p>`;
  if (data.linked.length) {
    html += '<ul>' + data.linked.map(l => `<li>${l.model_id} → ${l.file}</li>`).join('') + '</ul>';
  }
  if (data.errors && data.errors.length) {
    html += `<p class="status-err">Errors:</p><ul>` + data.errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
  }
  summary.innerHTML = html;
});
</script>

</body>
</html>
