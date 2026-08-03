<?php
/* ============================================================
   export_catalog.php  —  TI catalog exporter for the website rebuild
   ------------------------------------------------------------
   WHERE TO PUT IT
     Upload to:  public_html/portal/quotation/      (right next to db.php)

   HOW TO USE IT
     1. Inspect :  /portal/quotation/export_catalog.php?key=TIK2026
                   (lists every table, its columns, and row count)
     2. Export  :  click the green "Export CSV" button next to a table,
                   or visit  ...export_catalog.php?key=TIK2026&export=TABLENAME

   Reuses getDB() from your existing db.php, so no credentials live here.
   Change the KEY below to anything you like before uploading.
   ============================================================ */

$KEY = 'TIK2026';                 // <- change this to your own word
if (($_GET['key'] ?? '') !== $KEY) { http_response_code(403); exit('Forbidden. Add ?key=YOURKEY to the address.'); }

/* locate the shared DB connector */
$paths = [__DIR__.'/db.php', __DIR__.'/../quotation/db.php', __DIR__.'/../db.php'];
$loaded = false;
foreach ($paths as $p) { if (is_file($p)) { require_once $p; $loaded = true; break; } }
if (!$loaded || !function_exists('getDB')) {
    exit('Could not find db.php / getDB(). Put this file in /portal/quotation/ next to db.php.');
}
$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$export = $_GET['export'] ?? '';

/* ---------- EXPORT MODE ---------- */
if ($export !== '') {
    if (!in_array($export, $tables, true)) { exit('Unknown table name.'); }
    $rows = $pdo->query("SELECT * FROM `$export`");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$export.'.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));          // UTF-8 BOM so Excel reads accents
    $first = true;
    foreach ($rows as $r) {
        if ($first) { fputcsv($out, array_keys($r)); $first = false; }
        fputcsv($out, array_map(fn($v) => is_null($v) ? '' : $v, array_values($r)));
    }
    if ($first) { fputcsv($out, ['(table is empty)']); }
    fclose($out);
    exit;
}

/* ---------- INSPECT MODE ---------- */
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta charset="utf-8"><title>TI Catalog Exporter</title>';
echo '<style>
 body{font-family:Arial,Helvetica,sans-serif;background:#eef4f9;color:#0d2137;padding:26px;max-width:1000px;margin:auto;}
 h1{color:#1C3F94;margin:0 0 4px;} p{color:#33526b;}
 table{border-collapse:collapse;width:100%;margin:10px 0 20px;background:#fff;box-shadow:0 1px 6px rgba(13,33,55,.08);}
 td,th{border:1px solid #c8dde8;padding:8px 11px;font-size:13px;text-align:left;vertical-align:top;}
 th{background:#1C3F94;color:#fff;}
 a.btn{display:inline-block;background:#2BAE9E;color:#fff;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:13px;white-space:nowrap;}
 a.btn:hover{background:#23978a;}
 .cols{color:#4a6a82;font-size:12px;line-height:1.5;}
 .cnt{font-weight:bold;color:#1C3F94;}
</style>';
echo '<h1>TI Catalog Exporter</h1>';
echo '<p>Find the catalog table (the one with thousands of rows and a model column), and the <b>product_documents</b> table. Export both, then upload the two CSV files to me in chat.</p>';
foreach ($tables as $t) {
    $cnt  = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $u = htmlspecialchars($_SERVER['PHP_SELF']).'?key='.urlencode($KEY).'&export='.urlencode($t);
    echo '<table>';
    echo '<tr><th style="width:78%">'.htmlspecialchars($t).' &nbsp; <span style="font-weight:normal;opacity:.85">('.$cnt.' rows)</span></th>';
    echo '<th style="text-align:right"><a class="btn" href="'.$u.'">Export CSV</a></th></tr>';
    echo '<tr><td colspan="2" class="cols">'.htmlspecialchars(implode(', ', $cols)).'</td></tr>';
    echo '</table>';
}
