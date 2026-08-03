<?php
/**
 * combine_docs.php — Run via SSH:
 *   php ~/public_html/portal/quotation/combine_docs.php
 */
// Show all errors in CLI
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// No time/memory limit from CLI
set_time_limit(0);
ini_set('memory_limit', '1024M');
// Force immediate output (no buffering)
if (ob_get_level()) ob_end_flush();

define('BASE',        dirname(__FILE__) . '/');
define('FLYERS_DIR',  BASE . 'flyers/');
define('CURRIC_DIR',  BASE . 'curriculum/');
define('COMBINED_DIR',BASE . 'combined/');
define('MAPPINGS',    BASE . 'doc_mappings.json');
define('LOG_FILE',    BASE . 'combine_log.txt');

// ── LOAD FPDI ────────────────────────────────────────────────────────────
$autoload = BASE . 'vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "ERROR: vendor/autoload.php not found. Run: composer require setasign/fpdi\n";
    exit(1);
}
require_once $autoload;

// ── LOAD MAPPINGS ─────────────────────────────────────────────────────────
if (!file_exists(MAPPINGS)) {
    echo "ERROR: doc_mappings.json not found. Save mappings in the Doc Manager first.\n";
    exit(1);
}
$mappings = json_decode(file_get_contents(MAPPINGS), true) ?: [];
if (empty($mappings)) {
    echo "ERROR: No mappings found in doc_mappings.json.\n";
    exit(1);
}

// ── CREATE COMBINED DIR ───────────────────────────────────────────────────
if (!is_dir(COMBINED_DIR)) {
    mkdir(COMBINED_DIR, 0755, true);
    echo "Created: combined/\n";
}

// ── PROCESS ───────────────────────────────────────────────────────────────
$total   = count($mappings);
$ok      = 0;
$skipped = 0;
$errors  = [];
$log     = [];
$i       = 0;

echo "Processing $total files...\n\n"; flush();

foreach ($mappings as $flyer => $curric) {
    $i++;
    $flyer  = basename($flyer);
    $curric = basename($curric);

    // Extract model from flyer filename (first token before _)
    $model   = strtoupper(preg_replace('/[^A-Z0-9\-]/i', '', explode('_', $flyer)[0]));
    $outFile = COMBINED_DIR . $model . '_combined.pdf';

    // Skip if already combined (comment out to force re-merge)
    if (file_exists($outFile) && filesize($outFile) > 1000) {
        echo "[$i/$total] SKIP  $model (already exists)\n"; flush();
        $skipped++;
        continue;
    }

    $flyerPath  = FLYERS_DIR . $flyer;
    $curricPath = $curric ? CURRIC_DIR . $curric : null;

    if (!file_exists($flyerPath)) {
        $msg = "Flyer not found: $flyer";
        echo "[$i/$total] ERROR $model — $msg\n";
        $errors[] = "$flyer: $msg";
        $log[] = ['file'=>$flyer,'status'=>'error','msg'=>$msg];
        continue;
    }

    // Attempt FPDI merge
    $tmpOut = $outFile . '.tmp';
    try {
        $pdf = new \setasign\Fpdi\Fpdi('P', 'pt');

        // Add flyer pages
        $flyerPages = 0;
        try {
            $flyerPages = $pdf->setSourceFile($flyerPath);
            for ($p = 1; $p <= $flyerPages; $p++) {
                $tpl = $pdf->importPage($p);
                $sz  = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($sz['width'] > $sz['height'] ? 'L' : 'P', [$sz['width'], $sz['height']]);
                $pdf->useTemplate($tpl, 0, 0, $sz['width'], $sz['height']);
            }
        } catch (\setasign\Fpdi\PdfReader\PdfReaderException $e) {
            echo "[$i/$total] WARN  $model — flyer unreadable by FPDI ({$e->getMessage()}), copying as-is\n";
            copy($flyerPath, $outFile);
            $ok++;
            $log[] = ['file'=>$flyer,'status'=>'flyer_only','msg'=>$e->getMessage()];
            unset($pdf); gc_collect_cycles();
            continue;
        }

        // Add curriculum pages (if mapped and exists)
        $curricPages = 0;
        if ($curricPath && file_exists($curricPath)) {
            try {
                $curricPages = $pdf->setSourceFile($curricPath);
                for ($p = 1; $p <= $curricPages; $p++) {
                    $tpl = $pdf->importPage($p);
                    $sz  = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($sz['width'] > $sz['height'] ? 'L' : 'P', [$sz['width'], $sz['height']]);
                    $pdf->useTemplate($tpl, 0, 0, $sz['width'], $sz['height']);
                }
            } catch (\setasign\Fpdi\PdfReader\PdfReaderException $e) {
                // Curriculum can't be read — include flyer pages only
                echo "[$i/$total] WARN  $model — curriculum unreadable ({$e->getMessage()})\n";
            }
        }

        $pdf->Output($tmpOut, 'F');
        unset($pdf); gc_collect_cycles();

        if (file_exists($tmpOut) && filesize($tmpOut) > 500) {
            rename($tmpOut, $outFile);
            $flyerStr = $flyerPages > 0 ? "{$flyerPages}pg flyer" : "flyer";
            $curricStr = $curricPages > 0 ? " + {$curricPages}pg curriculum" : ($curricPath ? " (curric unreadable)" : " (no curriculum)");
            echo "[$i/$total] OK    $model — $flyerStr$curricStr → " . round(filesize($outFile)/1024) . "KB\n";
            $ok++;
            $log[] = ['file'=>$flyer,'status'=>'ok','flyer_pages'=>$flyerPages,'curric_pages'=>$curricPages,'out'=>basename($outFile)];
        } else {
            @unlink($tmpOut);
            // Fallback: copy flyer
            copy($flyerPath, $outFile);
            echo "[$i/$total] WARN  $model — FPDI produced empty output, copied flyer only\n";
            $ok++;
            $log[] = ['file'=>$flyer,'status'=>'flyer_only_fpdi_empty'];
        }
    } catch (Exception $e) {
        @unlink($tmpOut ?? '');
        $msg = $e->getMessage();
        echo "[$i/$total] ERROR $model — $msg\n";
        $errors[] = "$flyer: $msg";
        $log[] = ['file'=>$flyer,'status'=>'error','msg'=>$msg];
    }
}

// ── SUMMARY ───────────────────────────────────────────────────────────────
echo "\n────────────────────────────────\n";
echo "DONE:    $ok / $total files combined\n";
echo "SKIPPED: $skipped (already existed)\n";
echo "ERRORS:  " . count($errors) . "\n";
if ($errors) {
    echo "\nErrors:\n";
    foreach ($errors as $e) echo "  - $e\n";
}

// Write log
file_put_contents(LOG_FILE, json_encode([
    'run_at'  => date('c'),
    'total'   => $total,
    'ok'      => $ok,
    'skipped' => $skipped,
    'errors'  => $errors,
    'details' => $log,
], JSON_PRETTY_PRINT));
echo "\nLog written to: combine_log.txt\n";
