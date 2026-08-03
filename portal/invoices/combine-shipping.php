<?php
/**
 * combine-shipping.php
 * Accepts Invoice + Packing List + COO HTML bodies, renders each with mPDF
 * (page numbers reset per document), merges with FPDI, returns one PDF.
 */
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(120);

$autoload = realpath(__DIR__ . '/../quotation/vendor/autoload.php');
if (!$autoload || !file_exists($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'PDF libraries not found (quotation/vendor).']);
    exit;
}
require_once $autoload;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$USERS = ['dhanzal' => 'aA292199', 'fdegheidy' => 'Winner#1'];
$u = $in['auth_user'] ?? '';
$p = $in['auth_pass'] ?? '';
if (!isset($USERS[$u]) || $USERS[$u] !== $p) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$docs = $in['docs'] ?? [];
if (!is_array($docs) || count($docs) < 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No documents provided']);
    exit;
}

$filename = preg_replace('/[^A-Za-z0-9._\-]+/', '_', $in['filename'] ?? 'Shipping_Documents.pdf');
if (!preg_match('/\.pdf$/i', $filename)) $filename .= '.pdf';

$baseCss = '
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #333; }
table { border-collapse: collapse; }
img { max-width: 100%; }
@media print { * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
';

$tmpFiles = [];
try {
    foreach ($docs as $i => $doc) {
        $html = (string)($doc['html'] ?? '');
        $label = trim((string)($doc['label'] ?? ('Doc ' . ($i + 1))));
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        if ($html === '') {
            throw new Exception('Document ' . ($i + 1) . ' is empty');
        }

        // Strip @page rules — mPDF footer handles page numbers per document
        $html = preg_replace('/@page\s*\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/is', '', $html);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 16,
            'margin_header' => 0,
            'margin_footer' => 8,
            'tempDir' => sys_get_temp_dir(),
        ]);
        $mpdf->SetTitle($label);
        $mpdf->SetAuthor('Technologies International LLC');
        $mpdf->SetHTMLFooter(
            '<div style="width:100%;font-family:Arial,sans-serif;font-size:8px;color:#888;text-align:right;padding-top:2px;">'
            . 'Page {PAGENO} of {nbpg}  ·  ' . $safeLabel
            . '</div>'
        );
        $mpdf->WriteHTML('<style>' . $baseCss . '</style>', \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        $tmp = tempnam(sys_get_temp_dir(), 'ti_ship_') . '.pdf';
        $mpdf->Output($tmp, \Mpdf\Output\Destination::FILE);
        $tmpFiles[] = $tmp;
        unset($mpdf);
    }

    $pdf = new \setasign\Fpdi\Fpdi();
    foreach ($tmpFiles as $tmp) {
        $pageCount = $pdf->setSourceFile($tmp);
        for ($n = 1; $n <= $pageCount; $n++) {
            $tpl = $pdf->importPage($n);
            $size = $pdf->getTemplateSize($tpl);
            $orient = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orient, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
        }
    }

    foreach ($tmpFiles as $tmp) { @unlink($tmp); }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    $pdf->Output('I', $filename);
    exit;
} catch (Throwable $e) {
    foreach ($tmpFiles as $tmp) { @unlink($tmp); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Combine failed: ' . $e->getMessage()]);
    exit;
}
