<?php
@set_time_limit(300);
@ini_set('memory_limit','512M');
error_reporting(0);

require_once __DIR__.'/vendor/autoload.php';

$dir   = __DIR__.'/product_docs/';
$files = array_values(array_filter(scandir($dir), fn($f) => str_ends_with(strtolower($f), ".pdf")));

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<style>body{font-family:monospace;padding:20px;background:#0d2137;color:#eee;}
h2{color:#0db8b0;} .ok{color:#4caf50;} .fail{color:#f44336;} .info{color:#90caf9;}
pre{background:#1a2e44;padding:15px;border-radius:6px;overflow:auto;}
.warn{color:#ff9800;background:#332200;padding:8px;border-radius:4px;margin:8px 0;}
</style></head><body>';
echo '<h2>PDF 1.4 Converter — TI/Kitmeer Portal</h2>';
echo '<p style="color:#90caf9">Scanning '.count($files).' files in product_docs/...</p>';
echo '<pre>';

$converted = 0;
$skipped   = 0;
$failed    = 0;

foreach ($files as $fname) {
    $path = $dir.$fname;

    // Skip non-PDF junk that might be in the folder
    if (!str_ends_with(strtolower($fname), '.pdf')) continue;
    if (!file_exists($path)) continue;

    $size = round(filesize($path)/1024).' KB';

    // Test if already FPDI-compatible
    try {
        $test = new \setasign\Fpdi\Fpdi();
        $test->setSourceFile($path);
        $test->importPage(1);
        echo "<span class='ok'>✓ SKIP: $fname ($size) — already FPDI-compatible</span>\n";
        $skipped++;
        continue;
    } catch (\Throwable $e) {
        echo "<span class='info'>→ CONVERTING: $fname ($size)</span>\n";
    }

    // Try proc_open + GhostScript
    $success = false;
    if (function_exists('proc_open')) {
        $tmpOut = tempnam(sys_get_temp_dir(),'pdf14_').'.pdf';
        foreach (['/usr/bin/gs','/usr/local/bin/gs','gs'] as $gs) {
            $cmd = "$gs -dBATCH -dNOPAUSE -dSAFER -dQUIET"
                 . " -sDEVICE=pdfwrite -dCompatibilityLevel=1.4"
                 . " -sOutputFile=".escapeshellarg($tmpOut)
                 . " ".escapeshellarg($path);
            $desc = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
            $proc = @proc_open($cmd,$desc,$pipes);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                stream_get_contents($pipes[1]); fclose($pipes[1]);
                stream_get_contents($pipes[2]); fclose($pipes[2]);
                $ret = proc_close($proc);
                if ($ret===0 && file_exists($tmpOut) && filesize($tmpOut)>1000) {
                    rename($tmpOut,$path);
                    echo "  <span class='ok'>✓ Converted via GhostScript</span>\n\n";
                    $success = true;
                    $converted++;
                    break;
                }
            }
            @unlink($tmpOut);
        }
    }

    if (!$success) {
        echo "  <span class='fail'>✗ FAILED — manual conversion needed (pdf24.com)</span>\n\n";
        $failed++;
    }
}

echo "\n<span class='info'>Done. Scanned ".count($files)." files: $converted converted, $skipped skipped, $failed failed.</span>\n";
echo '</pre>';
echo '<p style="color:#f44336;font-weight:bold">⚠ Delete convert_pdfs.php from public_html/portal/quotation/ after use!</p>';
echo '</body></html>';
