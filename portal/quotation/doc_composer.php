<?php
/**
 * doc_composer.php — TI / Kitmeer Document Composer v3
 */
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);
@ini_set('pcre.backtrack_limit', '10000000');
@ini_set('pcre.recursion_limit', '10000000');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../admin/auth_check.php';   // SECURITY RETROFIT

define('DC_TEAL', '#0DB8A8');
define('DC_DEEP', '#0D2137');
define('DC_MED',  '#5A8CB4');
define('DC_BGLT', '#EEF4F9');
define('DC_LOGO', 'https://ti-kitmeer.com/images/Proposal%20Combined.png');

auth_cors();                       // CORS locked to ti-kitmeer.com
if (!auth_user()) {                // PDF generation requires an admin session
    while (ob_get_level()) ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not signed in — open /portal/admin/login.php, sign in, then return here.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'render_pdf') {
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $title     = trim($body['title']     ?? 'Document');
        $subtitle  = trim($body['subtitle']  ?? '');
        $docDate   = trim($body['docDate']   ?? date('m/d/Y'));
        $recipient = trim($body['recipient'] ?? '');
        $ref       = trim($body['ref']       ?? '');
        $html      = $body['html']           ?? '';
        $template  = trim($body['template']  ?? 'standard');
        $links     = is_array($body['links'] ?? null) ? $body['links'] : [];

        $fullHtml = buildFullHtml($html, $template, $docDate, $links);
        $pdf      = buildDocPdf($fullHtml, $title, $subtitle, $docDate, $recipient, $template);
        outputPdf($pdf, $recipient, $ref, $template);
    }
    elseif ($action === 'convert_docx') {
        $title     = trim($_POST['title']     ?? 'Document');
        $subtitle  = trim($_POST['subtitle']  ?? '');
        $docDate   = trim($_POST['docDate']   ?? date('m/d/Y'));
        $recipient = trim($_POST['recipient'] ?? '');
        $ref       = trim($_POST['ref']       ?? '');
        $template  = trim($_POST['template']  ?? 'standard');
        $links     = json_decode($_POST['links'] ?? '[]', true) ?: [];

        if (empty($_FILES['file']['tmp_name'])) jsonError('No file uploaded.');
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'docx') jsonError('Only .docx files are supported.');

        $html     = docxToHtml($_FILES['file']['tmp_name']);
        $fullHtml = buildFullHtml($html, $template, $docDate, $links);
        $pdf      = buildDocPdf($fullHtml, $title, $subtitle, $docDate, $recipient, $template);
        outputPdf($pdf, $recipient, $ref, $template);
    }
    else {
        jsonError('Unknown action: ' . htmlspecialchars($action));
    }
}
catch (\Throwable $e) {
    jsonError('PDF build error: ' . $e->getMessage());
}

// ── Template content injection ────────────────────────────────────────────────
function buildFullHtml(string $bodyHtml, string $template, string $docDate, array $links): string {
    $linksHtml = '';
    if (!empty($links)) {
        $linksHtml .= '<h2>References &amp; Links</h2><ul>';
        foreach ($links as $l) {
            $url   = htmlspecialchars($l['url']   ?? '', ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($l['label'] ?? $url, ENT_QUOTES, 'UTF-8');
            if ($url) $linksHtml .= '<li><a href="'.$url.'">'.$label.'</a></li>';
        }
        $linksHtml .= '</ul>';
    }

    if ($template === 'elearning') {
        // Intro is on the COVER PAGE — do NOT repeat it here.
        // Only append the outro + links at end of body.
        $outro = '
<hr style="border:none;border-top:0.5px solid #0DB8A8;margin:20px 0;">
<p>Should you have any questions about the content, licensing options, or how Amatrol\'s e-learning platform can support your training program, please don\'t hesitate to reach out. We\'re happy to assist.</p>
<p>For more information about our full catalog of learning titles, visit us at:<br>
&#127760; <a href="https://www.learnamatrol.com">www.learnamatrol.com</a> &nbsp;|&nbsp; <a href="https://www.amatrol.com">www.amatrol.com</a></p>
';
        return $bodyHtml . $outro . $linksHtml;
    }

    return $bodyHtml . $linksHtml;
}

// ── DOCX → HTML via PhpWord ───────────────────────────────────────────────────
function docxToHtml(string $tmpPath): string {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($tmpPath, 'Word2007');
    $writer  = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
    $tmpHtml = tempnam(sys_get_temp_dir(), 'dcx_') . '.html';
    $writer->save($tmpHtml);
    $raw = file_get_contents($tmpHtml);
    @unlink($tmpHtml);
    if (preg_match('/<body[^>]*>(.*?)<\/body>/si', $raw, $m)) $html = $m[1];
    else $html = $raw;
    $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
    $html = preg_replace('/\s*class="[^"]*"/i', '', $html);
    $html = preg_replace('/\s*style="[^"]*mso-[^"]*"/i', '', $html);
    $html = preg_replace('/<o:[^>]*>.*?<\/o:[^>]*>/si', '', $html);
    $html = preg_replace('/<\/?(?:o|w|m):[^>]*>/i', '', $html);
    $html = strip_tags($html,
        '<p><h1><h2><h3><h4><h5><h6><ul><ol><li>'
       .'<table><thead><tbody><tr><th><td>'
       .'<strong><b><em><i><u><a><img><br><hr>'
       .'<blockquote><pre><code><span><div>'
    );
    return trim($html) ?: '<p>(No content could be extracted.)</p>';
}

// ── Split HTML into manageable chunks ────────────────────────────────────────
function splitHtmlChunks(string $html, int $maxLen): array {
    if (strlen($html) <= $maxLen) return [$html];
    $chunks  = [];
    $pattern = '/(<\/(?:p|li|tr|div|h[1-6]|blockquote|pre|table)>)/i';
    $parts   = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $current = '';
    foreach ($parts as $part) {
        if (strlen($current) + strlen($part) > $maxLen && $current !== '') {
            $chunks[] = $current;
            $current  = $part;
        } else {
            $current .= $part;
        }
    }
    if ($current !== '') $chunks[] = $current;
    return $chunks ?: [$html];
}

// ── Build PDF ─────────────────────────────────────────────────────────────────
function buildDocPdf(string $bodyHtml, string $title, string $subtitle, string $docDate, string $recipient, string $template): string {
    $tmpDir = sys_get_temp_dir() . '/mpdf_dc_' . uniqid();
    @mkdir($tmpDir, 0755, true);

    $mpdf = new \Mpdf\Mpdf([
        'mode'              => 'utf-8',
        'format'            => 'A4',
        'margin_left'       => 14,
        'margin_right'      => 14,
        'margin_top'        => 30,
        'margin_bottom'     => 32,
        'margin_header'     => 6,
        'margin_footer'     => 4,
        'default_font'      => 'dejavusans',
        'tempDir'           => $tmpDir,
        'allow_charset_conversion' => false,
    ]);

    // Allow remote image (combined logo URL)
    $mpdf->SetCreator('TI-Kitmeer Document Composer');
    $mpdf->SetTitle($title);
    $mpdf->SetAuthor('Technologies International LLC');

    // Write CSS first
    $mpdf->WriteHTML(_dcCss(), \Mpdf\HTMLParserMode::HEADER_CSS);

    $hdrLabel = $recipient ?: $title;

    // ── Cover page: blank header/footer, ZERO margins so height:297mm fills perfectly ──
    $mpdf->SetHTMLHeader('', 'O'); $mpdf->SetHTMLHeader('', 'E');
    $mpdf->SetHTMLFooter('', 'O'); $mpdf->SetHTMLFooter('', 'E');
    // AddPage(orient, type, resetnum, pagestyle, suppress, mgl, mgr, mgt, mgb, mgh, mgf)
    $mpdf->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0);
    $mpdf->WriteHTML(_dcCover($title, $subtitle, $docDate, $recipient, $template),
        \Mpdf\HTMLParserMode::HTML_BODY);

    // ── Content pages: restore header/footer and proper margins ───────────────
    $mpdf->SetHTMLHeader(_dcHeader($hdrLabel, $docDate), 'O');
    $mpdf->SetHTMLHeader(_dcHeader($hdrLabel, $docDate), 'E');
    $mpdf->SetHTMLFooter(_dcFooter(), 'O');
    $mpdf->SetHTMLFooter(_dcFooter(), 'E');
    // Restore original margins: left=14, right=14, top=30, bottom=32, header=6, footer=4
    $mpdf->AddPage('P', '', '', '', '', 14, 14, 30, 32, 6, 4);

    $wrapped   = '<div class="doc-body">' . $bodyHtml . '</div>';
    $chunkSize = 50000;
    if (strlen($wrapped) <= $chunkSize) {
        $mpdf->WriteHTML($wrapped, \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        foreach (splitHtmlChunks($wrapped, $chunkSize) as $chunk) {
            $mpdf->WriteHTML($chunk, \Mpdf\HTMLParserMode::HTML_BODY);
        }
    }

    $pdf = $mpdf->Output('', 'S');
    foreach (glob($tmpDir . '/*') as $f) @unlink($f);
    @rmdir($tmpDir);
    return $pdf;
}

// ── HTML helpers ──────────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function _dcCss(): string { return '
body    { font-family:dejavusans,sans-serif; font-size:10pt; color:'.DC_DEEP.'; line-height:1.75; }
.doc-body { padding:0; }
h1 { font-size:15pt; color:'.DC_DEEP.'; font-weight:bold; margin:8mm 0 3mm; border-bottom:0.4mm solid '.DC_TEAL.'; padding-bottom:2mm; }
h2 { font-size:12pt; color:'.DC_TEAL.'; font-weight:bold; margin:6mm 0 2mm; }
h3 { font-size:11pt; color:'.DC_MED.'; font-weight:bold; margin:5mm 0 2mm; }
h4,h5,h6 { font-size:10.5pt; color:'.DC_DEEP.'; font-weight:bold; margin:4mm 0 1.5mm; }
p  { margin:0 0 3mm; }
a  { color:'.DC_TEAL.'; text-decoration:underline; }
ul,ol { margin:0 0 3mm 6mm; padding:0; }
li { margin-bottom:1.5mm; }
table { width:100%; border-collapse:collapse; margin:3mm 0; font-size:9.5pt; }
th { background:'.DC_DEEP.'; color:#fff; padding:2mm 3mm; font-weight:bold; text-align:left; }
td { padding:1.8mm 3mm; border-bottom:0.2mm solid #d0dde8; vertical-align:top; }
tr:nth-child(even) td { background:'.DC_BGLT.'; }
img { max-width:100%; height:auto; }
blockquote { margin:3mm 0 3mm 6mm; padding-left:3mm; border-left:1mm solid '.DC_TEAL.'; color:'.DC_MED.'; font-style:italic; }
hr { border:none; border-top:0.3mm solid '.DC_TEAL.'; margin:5mm 0; }
pre,code { font-family:monospace; font-size:8.5pt; background:'.DC_BGLT.'; padding:1mm 2mm; }
'; }

function _dcHeader(string $label, string $date): string {
    $logo = DC_LOGO;
    return '
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:0.4mm solid '.DC_TEAL.';">
<tr>
  <td width="55%" valign="bottom" style="padding-bottom:2mm;">
    <img src="'.$logo.'" style="max-height:14mm;max-width:72mm;" alt="TI-Kitmeer">
  </td>
  <td width="30%" align="center" valign="bottom" style="font-size:7pt;font-weight:bold;color:'.DC_DEEP.';padding-bottom:2mm;">'.h($label).'</td>
  <td width="15%" align="right" valign="bottom" style="font-size:7pt;color:'.DC_MED.';padding-bottom:2mm;">'.h($date).'</td>
</tr>
</table>';}

function _dcFooter(): string { return '
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:0.3mm solid '.DC_TEAL.';">
<tr>
  <td align="center" style="font-size:6.5pt;font-weight:bold;color:'.DC_TEAL.';padding-top:1.5mm;">
    Technologies International / Kitmeer / Amatrol
  </td>
</tr>
<tr>
  <td align="center" style="font-size:5.5pt;color:'.DC_MED.';padding-top:0.5mm;line-height:1.5;">
    North America: Office: 3149 Broadway, STE 16, New York, NY 10027 USA &nbsp; Ph: +1 646.216.9043 &nbsp; Fax: +1 646.233.0647<br>
    Middle East: P.O. Box 500211, Dubai Internet City, United Arab Emirates &nbsp; Ph: +971.4391.0970 &nbsp; Fax: +971.4391.8759<br>
    North America: Headquarters: 2400 Centennial Blvd., Jeffersonville, IN, 47130
  </td>
</tr>
</table>';}

function _dcCover(string $title, string $subtitle, string $date, string $recipient, string $template): string {
    $logo = DC_LOGO;

    $recipLine = $recipient
        ? '<div style="font-size:10pt;color:#7aaac8;margin-bottom:3mm;">PREPARED FOR: <strong style="color:#ffffff;">'.h($recipient).'</strong></div>'
        : '';
    $subLine = $subtitle
        ? '<div style="font-size:11pt;color:#a0c4da;margin-top:3mm;font-style:italic;">'.h($subtitle).'</div>'
        : '';
    $badge = ($template === 'elearning')
        ? '<div style="display:inline-block;background:'.DC_TEAL.';color:#fff;font-size:8pt;font-weight:bold;padding:2mm 5mm;border-radius:3mm;margin-bottom:4mm;letter-spacing:1px;">E-LEARNING DEMO</div><br>'
        : '';

    $coverExtra = '';
    if ($template === 'elearning') {
        $base = \DateTime::createFromFormat('m/d/Y', $date);
        if (!$base) $base = new \DateTime();
        $expiry    = (clone $base)->modify('+21 days');
        $expiryStr = $expiry->format('F j, Y');
        $coverExtra = '
  <div style="margin:8mm 18mm 0 18mm;background:rgba(13,184,168,.12);border-left:4mm solid '.DC_TEAL.';border-radius:0 4mm 4mm 0;padding:8mm 10mm;">
    <p style="margin:0 0 4mm 0;font-size:9.5pt;color:#e0f4f2;line-height:1.7;">
      Thank you for your interest in Amatrol\'s industry-leading e-learning solutions.
      Your complimentary demo course access has been activated and is fully available through
      <strong style="color:#ffffff;">'.$expiryStr.'</strong>.
      Please review system requirements before launching your course(s):
    </p>
    <p style="margin:0 0 4mm 0;font-size:9pt;">
      <a href="https://demo.learnamatrol.com/common/sysreq.php" style="color:'.DC_TEAL.';text-decoration:underline;">&#128279; https://demo.learnamatrol.com/common/sysreq.php</a>
    </p>
    <p style="margin:0;font-size:9pt;color:#c0ddd8;">
      Your demo course link(s) are on the following pages. Click to begin exploring Amatrol\'s world-class technical training content.
    </p>
  </div>';
    }

    return '
<div style="width:210mm;height:297mm;background:'.DC_DEEP.';margin:0;padding:0;position:relative;">
  <div style="width:210mm;height:4mm;background:'.DC_TEAL.';"></div>
  <div style="padding:12mm 18mm 0 18mm;text-align:left;">
    <div style="background:#fff;display:inline-block;padding:4mm 6mm;">
      <img src="'.$logo.'" style="max-height:28mm;max-width:130mm;" alt="Technologies International / Kitmeer">
    </div>
  </div>
  <div style="margin:8mm 18mm 0;height:0.4mm;background:rgba(13,184,168,.4);"></div>
  <div style="padding:10mm 18mm 0 18mm;">
    '.$recipLine.$badge.'
    <div style="font-size:24pt;font-weight:bold;color:#ffffff;line-height:1.2;letter-spacing:-.02em;">'.h($title).'</div>
    '.$subLine.'
  </div>
  '.$coverExtra.'
  <div style="position:absolute;bottom:14mm;left:18mm;right:18mm;">
    <div style="height:0.4mm;background:rgba(13,184,168,.4);margin-bottom:5mm;"></div>
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="font-size:9pt;color:#ffffff;vertical-align:top;">
        <div style="font-size:7pt;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:1mm;">Date</div>
        <div style="font-weight:bold;">'.h($date).'</div>
      </td>
      <td align="right" style="font-size:6.5pt;color:#4a6a82;vertical-align:bottom;line-height:1.6;">
        Technologies International LLC &amp; Kitmeer, Fz &amp; Amatrol<br>
        3149 Broadway STE 16, New York NY 10027 &nbsp;|&nbsp; +1 646.216.9043<br>
        Dubai Internet City, UAE &nbsp;|&nbsp; +971.4391.0970 &nbsp;|&nbsp; ti-kitmeer.com
      </td>
    </tr>
    </table>
  </div>
  <div style="position:absolute;bottom:0;left:0;width:210mm;height:3mm;background:'.DC_TEAL.';"></div>
</div>';}

// ── Output ────────────────────────────────────────────────────────────────────
function outputPdf(string $bytes, string $recipient='', string $ref='', string $template='standard'): void {
    // Clear ALL output buffer levels
    while (ob_get_level()) ob_end_clean();

    // Smart filename: recipient → ref → template default
    if ($recipient !== '') {
        $base = $recipient;
    } elseif ($ref !== '') {
        $base = $ref;
    } elseif ($template === 'elearning') {
        $base = 'E-Learning_Demo';
    } else {
        $base = 'TI-Kitmeer_Document';
    }
    $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $base);
    $filename = preg_replace('/_+/', '_', trim($filename, '_')) ?: 'Document';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $bytes;
    exit;
}

function jsonError(string $msg): void {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}
