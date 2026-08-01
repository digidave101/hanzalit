<?php
/**
 * link_docs.php — Run via SSH OR browser (protected by admin auth)
 * Scans combined/ folder, matches to products DB, builds product_documents table
 *
 * SSH:     php ~/public_html/portal/quotation/link_docs.php
 * Browser: https://ti-kitmeer.com/portal/quotation/link_docs.php
 */

$CLI = (php_sapi_name() === 'cli');

if (!$CLI) {
    // Browser auth — same credentials as admin portal
    session_start();
    $ADMINS = ['dhanzal'=>'aA292199','fdegheidy'=>'Winner#1'];
    if (isset($_POST['u'])) {
        if (isset($ADMINS[$_POST['u']]) && $ADMINS[$_POST['u']] === $_POST['p'])
            $_SESSION['lda'] = true;
        else header('Location: ?err=1');
    }
    if (empty($_SESSION['lda'])) { showLogin($_GET['err']??''); exit; }
    echo '<!DOCTYPE html><html><head><title>Link Docs</title>
    <style>body{font-family:monospace;background:#060b12;color:#c8dde8;padding:24px;white-space:pre-wrap;}
    .ok{color:#2dd4a0}.warn{color:#d4a020}.err{color:#e08080}.head{color:#5ab0e8;font-weight:bold}</style></head><body>';
}

define('BASE',         dirname(__FILE__) . '/');
define('COMBINED_DIR', BASE . 'combined/');

require_once BASE . 'db.php';
set_time_limit(0);

function p(string $msg, string $cls = '') {
    global $CLI;
    if ($CLI) echo $msg . "\n";
    else echo '<span' . ($cls ? " class=\"$cls\"" : '') . '>' . htmlspecialchars($msg) . '</span>' . "\n";
    if (!$CLI) ob_flush(); flush();
}

// ── STEP 1: CREATE product_documents TABLE ────────────────────────────────
p("=== Phase 1: Ensure product_documents table ===", 'head');
$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS product_documents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_id    VARCHAR(100) NOT NULL,
    combined_pdf VARCHAR(300) NOT NULL,
    flyer_pdf   VARCHAR(300) DEFAULT NULL,
    curric_pdf  VARCHAR(300) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_model (model_id),
    INDEX idx_model (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
p("✓ product_documents table ready", 'ok');

// ── STEP 2: LOAD MAPPINGS for flyer/curric detail ─────────────────────────
$mappings = [];
$mapFile = BASE . 'doc_mappings.json';
if (file_exists($mapFile)) {
    $raw = json_decode(file_get_contents($mapFile), true) ?: [];
    // Index by extracted model
    foreach ($raw as $flyer => $curric) {
        $model = strtoupper(preg_replace('/[^A-Z0-9\-]/i','', explode('_', basename($flyer))[0]));
        $mappings[$model] = ['flyer'=>basename($flyer), 'curric'=>basename($curric)];
    }
}
p("✓ Loaded " . count($mappings) . " flyer→curriculum mappings", 'ok');

// ── STEP 3: SCAN combined/ FOLDER ─────────────────────────────────────────
p("\n=== Phase 2: Scan combined/ folder ===", 'head');
$combined = glob(COMBINED_DIR . '*_combined.pdf') ?: [];
p("Found " . count($combined) . " combined PDFs");

// ── STEP 4: MATCH TO PRODUCTS DB ──────────────────────────────────────────
p("\n=== Phase 3: Match to products database ===", 'head');

// Load all model_ids from DB for matching
$dbModels = $db->query("SELECT model_id FROM products")->fetchAll(PDO::FETCH_COLUMN);
// Build lookup: uppercase stripped model → original model_id
$dbIndex = [];
foreach ($dbModels as $mid) {
    $key = strtoupper(preg_replace('/[^A-Z0-9\-]/i','', $mid));
    $dbIndex[$key][] = $mid;
    // Also index base model (strip voltage suffix -XEF etc)
    $base = preg_replace('/-X[A-Z]{2,3}$/i', '', $key);
    if ($base !== $key) $dbIndex[$base][] = $mid;
}

$inserted = 0; $updated = 0; $unmatched = []; $matched = [];

$ins = $db->prepare(
    "INSERT INTO product_documents (model_id, combined_pdf, flyer_pdf, curric_pdf)
     VALUES (:m, :c, :f, :k)
     ON DUPLICATE KEY UPDATE combined_pdf=:c2, flyer_pdf=:f2, curric_pdf=:k2,
                             updated_at=CURRENT_TIMESTAMP"
);

foreach ($combined as $path) {
    $filename = basename($path);
    // Extract model: everything before _combined.pdf
    $model = strtoupper(preg_replace('/_combined\.pdf$/i','', $filename));

    // Try exact match first
    $matchedIds = $dbIndex[$model] ?? [];

    // Try progressively shorter prefixes if no exact match
    if (empty($matchedIds)) {
        // Try stripping common suffixes: -XEF, -XAD, etc.
        $stripped = preg_replace('/-X[A-Z]{2,3}$/', '', $model);
        $matchedIds = $dbIndex[$stripped] ?? [];
    }
    if (empty($matchedIds)) {
        // Try matching just the prefix up to last dash segment
        $parts = explode('-', $model);
        for ($trim = count($parts)-1; $trim >= 2; $trim--) {
            $prefix = implode('-', array_slice($parts, 0, $trim));
            if (isset($dbIndex[$prefix])) { $matchedIds = $dbIndex[$prefix]; break; }
        }
    }

    if (empty($matchedIds)) {
        $unmatched[] = $filename;
        p("  UNMATCHED: $filename", 'warn');
        continue;
    }

    // Use primary match (first result)
    $modelId = $matchedIds[0];
    $map = $mappings[$model] ?? [];
    $relPath = 'combined/' . $filename;

    $ins->execute([
        ':m'  => $modelId,
        ':c'  => $relPath,
        ':f'  => $map['flyer']  ?? null,
        ':k'  => $map['curric'] ?? null,
        ':c2' => $relPath,
        ':f2' => $map['flyer']  ?? null,
        ':k2' => $map['curric'] ?? null,
    ]);

    // If multiple DB models match (voltage variants), insert for all
    foreach (array_slice($matchedIds, 1) as $extraId) {
        try {
            $ins->execute([':m'=>$extraId,':c'=>$relPath,':f'=>$map['flyer']??null,':k'=>$map['curric']??null,
                           ':c2'=>$relPath,':f2'=>$map['flyer']??null,':k2'=>$map['curric']??null]);
        } catch (Exception $e) { /* skip duplicates */ }
    }

    $matched[] = $modelId;
    p("  OK: $filename → $modelId" . (count($matchedIds)>1 ? " (+" . (count($matchedIds)-1) . " variants)" : ""), 'ok');
    $inserted++;
}

// ── SUMMARY ────────────────────────────────────────────────────────────────
$total_linked = (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn();
p("\n=== DONE ===", 'head');
p("✓ Linked:     $inserted files matched to database", 'ok');
p("✓ DB total:   $total_linked product_documents entries", 'ok');
if ($unmatched) {
    p("⚠ Unmatched: " . count($unmatched) . " files (no matching product in DB)", 'warn');
    foreach ($unmatched as $u) p("    - $u", 'warn');
}
p("\nNext: The quotation engine can now offer PDF downloads per product.");
if (!$CLI) echo '</body></html>';

function showLogin(string $err) { ?>
<!DOCTYPE html><html><head><title>Link Docs</title>
<style>body{background:#060b12;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;font-family:sans-serif}
.b{background:#0d2137;padding:28px 32px;border-radius:10px;border:1px solid #1a4060;width:300px}
h2{color:#2dd4a0;margin:0 0 18px;font-size:1rem}
input{width:100%;padding:8px 11px;background:#060c14;border:1px solid #1a3a5a;border-radius:5px;color:#c8dde8;margin-bottom:10px;font-size:.9rem;box-sizing:border-box}
button{width:100%;padding:9px;background:#1a6fa0;border:none;border-radius:5px;color:#fff;font-weight:700;cursor:pointer}
.e{color:#e08080;font-size:.8rem;margin-bottom:8px}</style></head>
<body><div class="b"><h2>Link Documents</h2>
<?php if($err) echo '<div class="e">Invalid credentials</div>'; ?>
<form method="post"><input name="u" placeholder="Username"><input name="p" type="password" placeholder="Password"><button>Go</button></form>
</div></body></html><?php }
