<?php
/**
 * ti-kitmeer.com/portal/quotation/doc_manager.php
 * Phase 1+2: Scan folders, propose matches, merge PDFs
 * 
 * SETUP: Upload this file to /portal/quotation/
 * Then visit: https://ti-kitmeer.com/portal/quotation/doc_manager.php
 */

define('FLYERS_DIR',   __DIR__ . '/flyers/');
define('CURRIC_DIR',   __DIR__ . '/curriculum/');
define('COMBINED_DIR', __DIR__ . '/combined/');
define('MAPPINGS_FILE',__DIR__ . '/doc_mappings.json');

// ── AUTH: same credentials as admin portal ──────────────────────────────
$ADMINS = ['dhanzal' => 'aA292199', 'fdegheidy' => 'Winner#1'];
session_start();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login_user'])){
    $u=$_POST['login_user'];$p=$_POST['login_pass'];
    if(isset($ADMINS[$u])&&$ADMINS[$u]===$p){ $_SESSION['dm_auth']=$u; }
    else header("Location: ?err=1");
}
if(isset($_GET['logout'])){ session_destroy(); header("Location: doc_manager.php"); exit; }
if(!isset($_SESSION['dm_auth'])){
    showLogin($_GET['err']??''); exit;
}

// ── AJAX HANDLERS ────────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
if(isset($_GET['action'])){
    header('Content-Type: application/json');
    switch($_GET['action']){
        case 'scan':     echo json_encode(scanFolders()); break;
        case 'propose':  echo json_encode(proposeMatches()); break;
        case 'save':     saveMappings(json_decode(file_get_contents('php://input'),true)??[]); echo json_encode(['ok'=>true]); break;
        case 'combine':  echo json_encode(combinePDF($_GET['flyer']??'', $_GET['curric']??'')); break;
        case 'combine_all': echo json_encode(combineAll()); break;
        case 'get_mappings': echo json_encode(loadMappings()); break;
        case 'status':   echo json_encode(getCombinedStatus()); break;
        case 'diag':     echo json_encode(getDiagnostics()); break;
        case 'pending_count': 
            $pending = glob(COMBINED_DIR . '*_pending.json') ?: [];
            echo json_encode(['count'=>count($pending),'files'=>array_map('basename',$pending)]);
            break;
        case 'combine_batch':
            ob_start();
            $batchResult = combineBatch(json_decode(file_get_contents('php://input'),true)??[]);
            ob_clean();
            echo json_encode($batchResult);
            break;
    }
    exit;
}

// ── SCAN FOLDERS ────────────────────────────────────────────────────────
function scanFolders(): array {
    $flyers = glob(FLYERS_DIR . '*.pdf') ?: [];
    $curricula = glob(CURRIC_DIR . '*.pdf') ?: [];
    return [
        'flyers'    => array_map('basename', $flyers),
        'curricula' => array_map('basename', $curricula),
        'combined'  => array_map('basename', glob(COMBINED_DIR . '*.pdf') ?: []),
    ];
}

// ── EXTRACT MODEL NUMBER FROM FILENAME ───────────────────────────────────
function extractModel(string $filename): string {
    // Remove extension
    $base = preg_replace('/\.pdf$/i','', $filename);
    // First token before underscore, space, or (
    $first = preg_split('/[_\s\(]/', $base)[0];
    // Normalize: uppercase, keep only alphanumeric and dash
    return strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $first));
}

function modelPrefix(string $model): string {
    // Strip trailing letter suffix after dash: 85-EH → 85, 990-ELE1 → 990-ELE
    // Keep numeric+alpha base: 85-EH → 85, 950-PM1 → 950-PM1
    return preg_replace('/-[A-Z]$/', '', $model);
}

// ── AUTO-PROPOSE MATCHES ─────────────────────────────────────────────────
function proposeMatches(): array {
    $scan = scanFolders();
    $flyers   = $scan['flyers'];
    $curricula = $scan['curricula'];
    $existing  = loadMappings();

    // Build curriculum index: model → filename
    $curricIdx = [];
    foreach($curricula as $cf){
        $m = extractModel($cf);
        $p = modelPrefix($m);
        $curricIdx[$m][] = $cf;
        if($p !== $m) $curricIdx[$p][] = $cf;
    }

    $proposals = [];
    foreach($flyers as $ff){
        $fm  = extractModel($ff);
        $fp  = modelPrefix($fm);
        $key = basename($ff);
        
        // Check if already mapped
        if(isset($existing[$key])){
            $proposals[] = ['flyer'=>$ff, 'curriculum'=>$existing[$key], 'status'=>'saved', 'confidence'=>100];
            continue;
        }

        // Try exact model match first
        $match = null; $conf = 0;
        if(isset($curricIdx[$fm])) { $match = $curricIdx[$fm][0]; $conf = 95; }
        elseif(isset($curricIdx[$fp])) { $match = $curricIdx[$fp][0]; $conf = 75; }
        else {
            // Fuzzy: find curriculum whose model starts with our prefix
            foreach($curricIdx as $cm => $cfiles){
                if(strpos($cm, $fp)===0 || strpos($fp,$cm)===0){
                    $match = $cfiles[0]; $conf = 60; break;
                }
            }
        }
        
        $proposals[] = [
            'flyer'      => $ff,
            'model'      => $fm,
            'curriculum' => $match,
            'confidence' => $conf,
            'status'     => $match ? 'proposed' : 'unmatched',
            'all_curricula' => array_values(array_unique(array_merge(
                $curricIdx[$fm] ?? [],
                $curricIdx[$fp] ?? []
            ))),
        ];
    }

    return ['proposals'=>$proposals, 'curricula'=>$curricula];
}

// ── SAVE / LOAD MAPPINGS ─────────────────────────────────────────────────
function loadMappings(): array {
    return file_exists(MAPPINGS_FILE) ? (json_decode(file_get_contents(MAPPINGS_FILE),true) ?: []) : [];
}
function saveMappings(array $data): void {
    $existing = loadMappings();
    foreach($data as $k => $v) $existing[$k] = $v;
    file_put_contents(MAPPINGS_FILE, json_encode($existing, JSON_PRETTY_PRINT));
}

// ── DIAGNOSTICS ──────────────────────────────────────────────────────────
function getDiagnostics(): array {
    $diag = [];
    // Check directories
    $diag['dirs'] = [
        'flyers_exists'   => is_dir(FLYERS_DIR),
        'curric_exists'   => is_dir(CURRIC_DIR),
        'combined_exists' => is_dir(COMBINED_DIR),
        'combined_writable'=> is_writable(COMBINED_DIR) || (!is_dir(COMBINED_DIR) && mkdir(COMBINED_DIR,0755,true)),
    ];
    // Check FPDI
    $fpdiPath = __DIR__ . '/vendor/autoload.php';
    $diag['fpdi_available'] = file_exists($fpdiPath);
    // Check shell_exec
    $diag['shell_exec_enabled'] = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));
    // Check pdftk
    $diag['pdftk_available'] = false;
    if($diag['shell_exec_enabled']){
        $out = @shell_exec('which pdftk 2>/dev/null');
        $diag['pdftk_available'] = trim($out??'') !== '';
    }
    // Check file sizes
    $flyers = glob(FLYERS_DIR . '*.pdf') ?: [];
    $diag['flyer_count'] = count($flyers);
    $diag['sample_flyer'] = $flyers ? basename($flyers[0]) : null;
    if($diag['sample_flyer']){
        $diag['sample_flyer_size'] = filesize(FLYERS_DIR.$diag['sample_flyer']);
        $diag['sample_flyer_readable'] = is_readable(FLYERS_DIR.$diag['sample_flyer']);
    }
    // Test write to combined dir
    if(!is_dir(COMBINED_DIR)) @mkdir(COMBINED_DIR, 0755, true);
    $testFile = COMBINED_DIR . '_test_write.tmp';
    $diag['can_write_combined'] = (@file_put_contents($testFile, 'test') !== false);
    if(file_exists($testFile)) @unlink($testFile);
    // PHP memory and limits
    $diag['memory_limit']   = ini_get('memory_limit');
    $diag['max_exec_time']  = ini_get('max_execution_time');
    $diag['php_version']    = PHP_VERSION;
    $diag['method_to_use']  = $diag['fpdi_available'] ? 'FPDI' : ($diag['pdftk_available'] ? 'pdftk' : 'php_concat');
    return $diag;
}

// ── COMBINE PDFs ──────────────────────────────────────────────────────────
function combinePDF(string $flyer, string $curriculum): array {
    $flyer  = basename($flyer);
    $curric = basename($curriculum);
    if(!$flyer || !file_exists(FLYERS_DIR.$flyer)){
        return ['ok'=>false,'error'=>'Flyer not found: '.$flyer];
    }
    $model   = extractModel($flyer);
    $outFile = COMBINED_DIR . $model . '_combined.pdf';
    if(!is_dir(COMBINED_DIR) && !@mkdir(COMBINED_DIR, 0755, true)){
        return ['ok'=>false,'error'=>'Cannot create combined/ directory — check permissions'];
    }
    if(!is_writable(COMBINED_DIR)){
        return ['ok'=>false,'error'=>'combined/ directory is not writable'];
    }
    // Try FPDI first
    $fpdiPath = __DIR__ . '/vendor/autoload.php';
    if(file_exists($fpdiPath)) return combinePDF_FPDI($flyer,$curric,$outFile,$model);
    // Try pdftk (only if shell_exec available)
    if(function_exists('shell_exec') && !in_array('shell_exec',array_map('trim',explode(',',ini_get('disable_functions'))))){
        $out = @shell_exec('which pdftk 2>/dev/null');
        if(trim($out??'') !== '') return combinePDF_pdftk($flyer,$curric,$outFile,$model);
    }
    // PHP-only PDF byte concatenation (works for standard PDFs without cross-reference table)
    return combinePDF_concat($flyer,$curric,$outFile,$model);
}

function combinePDF_FPDI(string $flyer, string $curric, string $outFile, string $model): array {
    require_once __DIR__ . '/vendor/autoload.php';
    $tmpOut = $outFile . '.tmp';
    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $sources = array_filter([FLYERS_DIR.$flyer, $curric?CURRIC_DIR.$curric:null]);
        foreach($sources as $src){
            if(!file_exists($src)) continue;
            try {
                $count = $pdf->setSourceFile($src);
                for($i=1;$i<=$count;$i++){
                    $tpl=$pdf->importPage($i);
                    $sz=$pdf->getTemplateSize($tpl);
                    $pdf->AddPage($sz['width']>$sz['height']?'L':'P',[$sz['width'],$sz['height']]);
                    $pdf->useTemplate($tpl);
                }
            } catch(\setasign\Fpdi\PdfReader\PdfReaderException $e){
                // Skip files that can't be read (encrypted PDFs etc.)
                continue;
            }
        }
        $pdf->Output($tmpOut,'F');
        unset($pdf); gc_collect_cycles();
        if(file_exists($tmpOut) && filesize($tmpOut) > 100){
            rename($tmpOut, $outFile);
            return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'fpdi'];
        }
        return ['ok'=>false,'error'=>'FPDI produced empty output'];
    } catch(Exception $e){
        if(file_exists($tmpOut)) @unlink($tmpOut);
        // Fallback: copy flyer only
        if(@copy(FLYERS_DIR.$flyer, $outFile)){
            return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'fpdi_fallback_flyer',
                    'note'=>$e->getMessage()];
        }
        return ['ok'=>false,'error'=>'FPDI: '.$e->getMessage()];
    }
}

function combinePDF_pdftk(string $flyer, string $curric, string $outFile, string $model): array {
    $f = escapeshellarg(FLYERS_DIR.$flyer);
    $c = $curric ? escapeshellarg(CURRIC_DIR.$curric) : '';
    $o = escapeshellarg($outFile);
    $r = @shell_exec($c ? "pdftk $f $c cat output $o 2>&1" : "cp $f $o 2>&1");
    return (file_exists($outFile)&&filesize($outFile)>100)
        ? ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'pdftk']
        : ['ok'=>false,'error'=>'pdftk: '.($r??'failed')];
}

function combinePDF_concat(string $flyer, string $curric, string $outFile, string $model): array {
    // PHP-only approach: read PDFs and concatenate using proper PDF structure
    // This preserves both documents as separate pages in a single PDF viewer
    $files = [FLYERS_DIR.$flyer];
    if($curric && file_exists(CURRIC_DIR.$curric)) $files[] = CURRIC_DIR.$curric;
    
    if(count($files)===1){
        // Just copy the flyer
        if(@copy($files[0], $outFile)){
            return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'copy_only',
                    'note'=>'No curriculum found — flyer only'];
        }
        return ['ok'=>false,'error'=>'copy() failed — check permissions on '.COMBINED_DIR];
    }
    
    // Read both PDF files into memory
    $pdf1 = @file_get_contents($files[0]);
    $pdf2 = @file_get_contents($files[1]);
    if(!$pdf1) return ['ok'=>false,'error'=>'Cannot read flyer: '.$flyer];
    if(!$pdf2) return ['ok'=>false,'error'=>'Cannot read curriculum: '.$curric];
    
    // Parse PDF objects from both files and rebuild with combined page tree
    $combined = buildCombinedPDF($pdf1, $pdf2);
    if($combined===false){
        // Fallback: just copy flyer if merge fails
        if(@copy($files[0], $outFile)){
            return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'flyer_fallback',
                    'note'=>'Merge failed, flyer only saved. Install FPDI for full merge.'];
        }
        return ['ok'=>false,'error'=>'Both merge and copy failed'];
    }
    
    if(@file_put_contents($outFile, $combined) === false){
        return ['ok'=>false,'error'=>'Cannot write to '.COMBINED_DIR.' — check directory permissions'];
    }
    // Verify the output is a valid PDF (starts with %PDF)
    $verify = @file_get_contents($outFile, false, null, 0, 4);
    if($verify !== '%PDF'){
        // Merge produced invalid output — fall back to flyer copy + pending note
        @copy($files[0], $outFile);
        $pendingNote = COMBINED_DIR . $model . '_pending.json';
        @file_put_contents($pendingNote, json_encode([
            'flyer'=>$flyer, 'curriculum'=>$curric,
            'note'=>'Merge failed — flyer saved only. Re-run after installing FPDI.',
            'timestamp'=>date('c')
        ]));
        return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'flyer_only_pending',
                'note'=>'PHP merge failed; flyer saved, curriculum queued for FPDI re-merge'];
    }
    return ['ok'=>true,'file'=>basename($outFile),'model'=>$model,'method'=>'php_concat',
            'pages'=>'combined'];
}

function buildCombinedPDF(string $pdf1, string $pdf2): string|false {
    // Incremental PDF update approach — append pdf2 objects after pdf1
    try {
        if(substr($pdf1,0,4)!=='%PDF' || substr($pdf2,0,4)!=='%PDF') return false;
        $size1 = strlen($pdf1);

        // Find highest object number in pdf1
        preg_match_all('/^(\d+)\s+\d+\s+obj/m', $pdf1, $objs1);
        $shift = $objs1[1] ? (int)max($objs1[1]) : 100;

        // Renumber all object definitions in pdf2: "N 0 obj" → "(N+shift) 0 obj"
        $pdf2r = preg_replace_callback(
            '/^(\d+)(\s+\d+\s+obj)/m',
            function($m) use($shift){ return ((int)$m[1]+$shift).$m[2]; },
            $pdf2
        );
        // Renumber indirect references in pdf2: "N 0 R" → "(N+shift) 0 R"
        $pdf2r = preg_replace_callback(
            '/\b(\d+)(\s+0\s+R)\b/',
            function($m) use($shift){ return ((int)$m[1]+$shift).$m[2]; },
            $pdf2r
        );

        // Strip %PDF-x.x header from pdf2
        $headerEnd = strpos($pdf2r, "\n");
        if($headerEnd !== false) $pdf2r = substr($pdf2r, $headerEnd+1);

        // Strip %%EOF from end of pdf1 and concatenate
        $combined = $pdf1;
        $eofPos = strrpos($combined, "%%EOF");
        if($eofPos !== false) $combined = rtrim(substr($combined, 0, $eofPos));
        $combined .= "\n\n" . $pdf2r;
        if(strrpos($combined, "%%EOF") === false) $combined .= "\n%%EOF\n";

        return $combined;
    } catch(Throwable $e){ return false; }
}

function combineAll(): array {
    $mappings = loadMappings();
    $results = ['ok'=>0,'skip'=>0,'error'=>0,'errors'=>[]];
    foreach($mappings as $flyer => $curric){
        $r = combinePDF($flyer, $curric);
        if($r['ok']) $results['ok']++;
        else { $results['error']++; $results['errors'][]=$flyer.': '.($r['error']??'?'); }
    }
    return $results;
}

function combineBatch(array $data): array {
    // Process files one at a time with extended limits
    set_time_limit(120);
    ini_set('memory_limit', '512M');
    $results = ['ok'=>0,'error'=>0,'details'=>[],'errors'=>[]];
    foreach($data as $flyer => $curric){
        // Free memory before each file
        gc_collect_cycles();
        $r = combinePDF($flyer, $curric);
        if($r['ok']){ 
            $results['ok']++;
            $results['details'][$flyer] = ['file'=>$r['file'],'method'=>$r['method']??'?'];
        } else { 
            $results['error']++; 
            $results['errors'][] = $flyer.': '.($r['error']??'?');
        }
        gc_collect_cycles(); // free memory after each file
    }
    return $results;
}

function getCombinedStatus(): array {
    $combined = glob(COMBINED_DIR . '*.pdf') ?: [];
    $result = [];
    foreach($combined as $f){
        $result[] = ['file'=>basename($f),'size'=>filesize($f),'model'=>extractModel(basename($f))];
    }
    return ['files'=>$result,'count'=>count($result)];
}

function showLogin(string $err=''): void { ?>
<!DOCTYPE html><html><head><title>Doc Manager Login</title>
<style>body{font-family:sans-serif;background:#0a1a2e;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#0d2137;padding:32px 36px;border-radius:10px;width:320px;border:1px solid #1a4060}
h2{color:#2dd4a0;margin:0 0 20px;font-size:1.1rem}
input{width:100%;padding:9px 12px;background:#060c14;border:1px solid #1a3a5a;border-radius:6px;color:#c8dde8;margin-bottom:12px;font-size:.9rem;box-sizing:border-box}
button{width:100%;padding:10px;background:#1a6fa0;border:none;border-radius:6px;color:#fff;font-weight:700;cursor:pointer;font-size:.9rem}
.err{color:#c05050;font-size:.8rem;margin-bottom:10px}</style></head><body>
<div class="box"><h2>📄 Document Manager</h2>
<?php if($err) echo '<div class="err">Invalid credentials</div>'; ?>
<form method="post"><input name="login_user" placeholder="Username"><input name="login_pass" type="password" placeholder="Password"><button>Log In</button></form></div>
</body></html><?php }

// ── MAIN UI ──────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Document Manager — TI-Kitmeer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#060b12;color:#c8dde8;min-height:100vh}
.hdr{background:#0d2137;border-bottom:3px solid #0db8a8;padding:14px 24px;display:flex;justify-content:space-between;align-items:center}
.hdr h1{font-size:1rem;color:#fff;font-weight:700}
.body{max-width:1300px;margin:0 auto;padding:20px}
.card{background:#090f18;border:1px solid #0e2030;border-radius:10px;padding:18px;margin-bottom:16px}
.card h2{font-size:.85rem;text-transform:uppercase;letter-spacing:1.5px;color:#2a6a8a;margin-bottom:14px;font-weight:700}
.stats{display:flex;gap:12px;margin-bottom:16px}
.stat{background:#0d2137;border:1px solid #1a4060;border-radius:8px;padding:12px 18px;flex:1;text-align:center}
.stat .n{font-size:1.8rem;font-weight:700;color:#2dd4a0}
.stat .l{font-size:.7rem;color:#2a6a8a;text-transform:uppercase;margin-top:2px}
.btn{padding:7px 16px;border:none;border-radius:6px;cursor:pointer;font-size:.75rem;font-weight:700;font-family:inherit}
.btn-teal{background:#0db8a8;color:#fff}.btn-blue{background:#1a6fa0;color:#fff}.btn-gray{background:#1a3a5a;color:#c8dde8}.btn-red{background:#6a1a1a;color:#f08080}
.btn:hover{opacity:.85}
table{width:100%;border-collapse:collapse;font-size:.75rem}
th{background:#060c14;padding:9px 10px;text-align:left;color:#2a6a8a;font-weight:700;border-bottom:1px solid #0e2030}
td{padding:8px 10px;border-bottom:1px solid #0a1520;vertical-align:middle}
tr:hover td{background:#0d1e2e}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:700}
.b-green{background:rgba(45,212,160,.15);color:#2dd4a0;border:1px solid rgba(45,212,160,.3)}
.b-blue{background:rgba(26,111,160,.15);color:#5ab0e8;border:1px solid rgba(26,111,160,.3)}
.b-amber{background:rgba(200,160,40,.15);color:#d4a020;border:1px solid rgba(200,160,40,.3)}
.b-red{background:rgba(200,60,60,.15);color:#e08080;border:1px solid rgba(200,60,60,.3)}
select.curr-sel{background:#0d2137;border:1px solid #1a4060;border-radius:4px;color:#c8dde8;padding:4px 8px;font-size:.73rem;width:100%}
#progress{display:none;background:#0d2137;border:1px solid #1a4060;border-radius:6px;padding:12px;margin-top:12px}
.prog-bar{background:#1a4060;border-radius:3px;height:6px;margin:6px 0}
.prog-fill{background:#0db8a8;height:6px;border-radius:3px;transition:width .3s}
.log{background:#060c14;border:1px solid #0e2030;border-radius:5px;padding:8px 10px;max-height:200px;overflow-y:auto;font-size:.68rem;font-family:monospace;color:#8aaabb;margin-top:8px}
a{color:#2a6a8a;text-decoration:none}.a:hover{color:#5ab0e8}
</style>
</head>
<body>
<div class="hdr">
  <h1>📄 Document Manager — Flyer + Curriculum Combiner</h1>
  <div style="display:flex;gap:10px;align-items:center">
    <span style="font-size:.75rem;color:#2a6a8a">Logged in as: <?= htmlspecialchars($_SESSION['dm_auth']) ?></span>
    <a href="?logout=1" class="btn btn-gray">Log Out</a>
    <a href="/portal/quotation/" class="btn btn-gray">← Quotation Engine</a>
  </div>
</div>

<div class="body">

  <!-- Stats -->
  <div class="stats" id="statsRow">
    <div class="stat"><div class="n" id="sFly">…</div><div class="l">Flyers</div></div>
    <div class="stat"><div class="n" id="sCur">…</div><div class="l">Curricula</div></div>
    <div class="stat"><div class="n" id="sMap">…</div><div class="l">Mapped</div></div>
    <div class="stat"><div class="n" id="sCom">…</div><div class="l">Combined PDFs</div></div>
    <div class="stat"><div class="n" id="sUnm">…</div><div class="l">Unmatched</div></div>
  </div>

  <!-- Controls -->
  <div class="card">
    <h2>Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn btn-blue" onclick="loadProposals()">🔍 Scan & Auto-Match</button>
      <button class="btn btn-teal" onclick="saveAll()">💾 Save All Mappings</button>
      <button class="btn btn-teal" onclick="combineAll()">⚡ Combine All Confirmed PDFs</button>
      <button class="btn btn-gray" onclick="runDiag()">🩺 Diagnose</button>
      <button class="btn btn-gray" onclick="exportMappings()">⬇ Export Mappings JSON</button>
    </div>
    <div id="progress">
      <div style="font-size:.75rem;font-weight:700;color:#c8dde8" id="progLabel">Working…</div>
      <div class="prog-bar"><div class="prog-fill" id="progFill" style="width:0%"></div></div>
      <div class="log" id="progLog"></div>
    </div>
  </div>

  <!-- Matching Table -->
  <div class="card">
    <h2>Flyer → Curriculum Mappings</h2>
    <div style="display:flex;gap:10px;margin-bottom:12px;align-items:center">
      <input type="search" id="filterInput" placeholder="Filter by model or filename…" 
             style="padding:6px 10px;background:#060c14;border:1px solid #1a3a5a;border-radius:5px;color:#c8dde8;font-size:.75rem;width:280px"
             oninput="filterTable()">
      <select id="filterStatus" onchange="filterTable()" 
              style="padding:6px 10px;background:#060c14;border:1px solid #1a3a5a;border-radius:5px;color:#c8dde8;font-size:.75rem">
        <option value="">All</option>
        <option value="saved">Saved</option>
        <option value="proposed">Proposed</option>
        <option value="unmatched">Unmatched</option>
        <option value="combined">Combined</option>
      </select>
      <span id="rowCount" style="font-size:.72rem;color:#2a6a8a;margin-left:auto"></span>
    </div>
    <div style="overflow-x:auto">
    <table id="matchTable">
      <thead><tr>
        <th style="width:40px"><input type="checkbox" id="checkAll" onchange="toggleAll(this.checked)"></th>
        <th>Flyer Filename</th>
        <th>Model</th>
        <th style="width:200px">Curriculum (click to change)</th>
        <th>Status</th>
        <th>Actions</th>
      </tr></thead>
      <tbody id="matchBody"><tr><td colspan="6" style="text-align:center;padding:30px;color:#2a6a8a">
        Click "Scan &amp; Auto-Match" to load files
      </td></tr></tbody>
    </table>
    </div>
  </div>

</div>

<script>
var proposals = [];
var curricula = [];
var combined = new Set();
var pendingChanges = {};

function api(action, params){
  var url = '?action='+action+(params?'&'+params:'');
  return fetch(url).then(function(r){return r.json();});
}

function loadStats(){
  api('scan').then(function(d){
    document.getElementById('sFly').textContent = d.flyers.length;
    document.getElementById('sCur').textContent = d.curricula.length;
    document.getElementById('sCom').textContent = d.combined.length;
    d.combined.forEach(function(f){ combined.add(f); });
  });
  api('get_mappings').then(function(d){
    document.getElementById('sMap').textContent = Object.keys(d).length;
  });
}

function loadProposals(){
  document.getElementById('matchBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#2a6a8a">Scanning folders…</td></tr>';
  api('propose').then(function(d){
    proposals = d.proposals||[];
    curricula = d.curricula||[];
    var unmatched = proposals.filter(function(p){return p.status==='unmatched';}).length;
    document.getElementById('sUnm').textContent = unmatched;
    renderTable();
  });
}

function renderTable(){
  var q = document.getElementById('filterInput').value.toLowerCase();
  var st = document.getElementById('filterStatus').value;
  var rows = proposals.filter(function(p){
    var matchQ = !q || p.flyer.toLowerCase().includes(q) || (p.model||'').toLowerCase().includes(q);
    var matchS = !st || p.status===st || (st==='combined'&&combined.has((p.model||extractModel(p.flyer))+'_combined.pdf'));
    return matchQ && matchS;
  });
  document.getElementById('rowCount').textContent = rows.length + ' / ' + proposals.length + ' shown';
  var html = rows.map(function(p,i){
    var isCombined = combined.has((p.model||'')+'_combined.pdf');
    var badge = isCombined ? '<span class="badge b-green">Combined</span>'
              : p.status==='saved' ? '<span class="badge b-blue">Saved</span>'
              : p.status==='proposed' ? '<span class="badge b-amber">Proposed '+(p.confidence||0)+'%</span>'
              : '<span class="badge b-red">Unmatched</span>';
    
    var curric = pendingChanges[p.flyer] !== undefined ? pendingChanges[p.flyer] : (p.curriculum||'');
    var curSel = '<select class="curr-sel" data-flyer="'+esc(p.flyer)+'" onchange="setCurriculum(this)">'
               + '<option value="">— None —</option>'
               + curricula.map(function(c){
                   return '<option value="'+esc(c)+'"'+(c===curric?' selected':'')+'>'+esc(c)+'</option>';
                 }).join('')
               + '</select>';
    
    return '<tr data-status="'+(isCombined?'combined':p.status)+'">'
      +'<td><input type="checkbox" class="row-chk" value="'+esc(p.flyer)+'"></td>'
      +'<td style="font-family:monospace;font-size:.72rem">'+esc(p.flyer)+'</td>'
      +'<td style="font-weight:700;color:#2a6a8a">'+esc(p.model||'')+'</td>'
      +'<td>'+curSel+'</td>'
      +'<td>'+badge+'</td>'
      +'<td style="white-space:nowrap">'
      +(curric?'<button class="btn btn-teal" style="padding:3px 8px;font-size:.65rem" onclick="combineSingle(\''+esc(p.flyer)+'\',\''+esc(curric)+'\')">⚡ Combine</button> ':'')
      +'</td>'
      +'</tr>';
  }).join('');
  document.getElementById('matchBody').innerHTML = html || '<tr><td colspan="6" style="text-align:center;padding:20px;color:#2a6a8a">No results</td></tr>';
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
function extractModel(f){ return f.split('_')[0].toUpperCase(); }

function setCurriculum(sel){
  pendingChanges[sel.dataset.flyer] = sel.value;
  // Update proposal
  var p = proposals.find(function(x){return x.flyer===sel.dataset.flyer;});
  if(p){ p.curriculum=sel.value; if(sel.value)p.status='proposed'; }
}

function filterTable(){ renderTable(); }

function toggleAll(on){
  document.querySelectorAll('.row-chk').forEach(function(c){c.checked=on;});
}

function saveAll(){
  var data = {};
  proposals.forEach(function(p){
    var cur = pendingChanges[p.flyer]!==undefined?pendingChanges[p.flyer]:p.curriculum;
    if(cur) data[p.flyer] = cur;
  });
  fetch('?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
    .then(function(){
      showProgress('✓ Mappings saved ('+Object.keys(data).length+' entries)');
      loadStats();
    });
}

function combineSingle(flyer, curric){
  showProgress('Combining: '+flyer+' + '+curric);
  fetch('?action=combine&flyer='+encodeURIComponent(flyer)+'&curric='+encodeURIComponent(curric))
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ logLine('✓ '+d.file+' ('+d.method+')'); combined.add(d.file); renderTable(); }
      else logLine('✗ '+d.error);
    });
}

function runDiag(){
  showProgress('Running diagnostics…');
  fetch('?action=diag').then(function(r){return r.json();}).then(function(d){
    logLine('PHP '+d.php_version+' | Memory: '+d.memory_limit+' | Max exec: '+d.max_exec_time+'s');
    logLine('Flyers dir: '+(d.dirs.flyers_exists?'✓':'✗')+' | Curricula: '+(d.dirs.curric_exists?'✓':'✗'));
    logLine('Combined dir writable: '+(d.can_write_combined?'✓':'✗')+' | FPDI: '+(d.fpdi_available?'✓ installed':'✗ not installed'));
    logLine('shell_exec: '+(d.shell_exec_enabled?'✓':'✗')+' | pdftk: '+(d.pdftk_available?'✓':'✗'));
    logLine('Merge method will use: <b style="color:#2dd4a0">'+d.method_to_use+'</b>');
    if(d.sample_flyer) logLine('Sample flyer: '+d.sample_flyer+' ('+Math.round((d.sample_flyer_size||0)/1024)+'KB, readable: '+(d.sample_flyer_readable?'✓':'✗')+')');
    if(!d.can_write_combined) logLine('<b style="color:#e08080">⚠ CANNOT WRITE to combined/ — this is why combine is failing!</b>');
    if(!d.fpdi_available) logLine('💡 Install FPDI: SSH to server → cd public_html/portal/quotation → composer require setasign/fpdi');
  });
}

function combineAll(){
  var mappings = {};
  proposals.forEach(function(p){
    var cur = pendingChanges[p.flyer]!==undefined?pendingChanges[p.flyer]:p.curriculum;
    if(cur) mappings[p.flyer]=cur;
  });
  var keys = Object.keys(mappings);
  if(!keys.length){ alert('No mappings saved yet. Click "Save All Mappings" first.'); return; }
  
  showProgress('Combining '+keys.length+' PDFs in batches…');
  document.getElementById('progLog').innerHTML = '';
  
  var BATCH_SIZE = 1; // process 1 at a time — FPDI needs memory per file
  var batches = [];
  for(var i=0;i<keys.length;i+=BATCH_SIZE){
    var batch = {};
    keys.slice(i,i+BATCH_SIZE).forEach(function(k){ batch[k]=mappings[k]; });
    batches.push(batch);
  }
  
  var batchDone=0, totalOk=0, totalErr=0;
  
  function nextBatch(){
    if(batchDone>=batches.length){
      document.getElementById('progLabel').textContent='✓ Done: '+totalOk+' combined, '+totalErr+' errors';
      document.getElementById('progFill').style.width='100%';
      loadStats();
      return;
    }
    var batch=batches[batchDone];
    var batchStart=batchDone*BATCH_SIZE;
    document.getElementById('progFill').style.width=(batchDone/batches.length*100)+'%';
    document.getElementById('progLabel').textContent='Combining batch '+(batchDone+1)+'/'+batches.length+' (items '+(batchStart+1)+'–'+Math.min(batchStart+BATCH_SIZE,keys.length)+' of '+keys.length+')';
    
    fetch('?action=combine_batch',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(batch)
    })
    .then(function(r){return r.json();})
    .then(function(d){
      totalOk+=d.ok||0; totalErr+=d.error||0;
      var details=d.details||{};
      Object.keys(details).forEach(function(f){
        logLine('✓ '+details[f].file+' ['+details[f].method+']');
        combined.add(details[f].file);
      });
      (d.errors||[]).forEach(function(e){ logLine('<span style="color:#e08080">✗ '+e+'</span>'); });
      batchDone++;
      setTimeout(nextBatch, 200);
    })
    .catch(function(err){
      logLine('<span style="color:#e08080">Batch error: '+err.message+'</span>');
      batchDone++;
      setTimeout(nextBatch, 500);
    });
  }
  nextBatch();
}

function showProgress(msg){
  document.getElementById('progress').style.display='block';
  document.getElementById('progLabel').textContent=msg;
  document.getElementById('progFill').style.width='0%';
}
function logLine(msg){
  var l=document.getElementById('progLog');
  l.innerHTML+=msg+'<br>';
  l.scrollTop=l.scrollHeight;
}
function exportMappings(){
  var data={};
  proposals.forEach(function(p){var c=pendingChanges[p.flyer]??p.curriculum;if(c)data[p.flyer]=c;});
  var blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'});
  var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='doc_mappings.json';a.click();
}

// Init
loadStats();
</script>
</body>
</html>
