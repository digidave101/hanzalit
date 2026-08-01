<?php
/**
 * vendor_import.php  —  TI-Kitmeer Vendor Quote Import API
 * Drop this file in:  ti-kitmeer.com/portal/quotation/vendor_import.php
 *
 * Handles three import formats:
 *   • Amatrol Excel  — flat rows: col A = Model#, col B = Description, col C = Qty, col D = Unit Price
 *   • Lucas-Nülle PDF — parsed via text extraction (pos/order-no/qty/price table)
 *   • Aramco Spec DOCX — ITEM (N) blocks with (MODEL-NUM) in parentheses
 *
 * Actions (POST JSON or multipart/form-data):
 *   parse_vendor_doc   — parse uploaded file, returns structured items + DB comparison
 *   import_to_catalog  — upsert new/changed items to products table (mode A)
 *   import_to_quote    — create a new quote pre-loaded with items (mode B)
 *   import_both        — both A + B in one call
 *   list_vendor_configs — return vendor profile list
 */

ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Fatal: ' . $e['message']]);
    }
});

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Vendor profile registry ──────────────────────────────────────────────────
// Each vendor defines how its document is structured.
// 'col_*' indices are 0-based for Excel; PDF/DOCX use named parsers.
$VENDOR_CONFIGS = [
    'amatrol' => [
        'name'         => 'Amatrol, Inc.',
        'short'        => 'Amatrol',
        'manufacturer' => 'Amatrol',
        'logo'         => 'Amatrol-logo.png',
        'formats'      => ['xlsx', 'xls', 'csv'],
        'parser'       => 'amatrol_excel',
        // Column indices (0-based) in Excel sheet
        'col_model'    => 0,   // col A
        'col_desc'     => 1,   // col B
        'col_qty'      => 2,   // col C
        'col_price'    => 3,   // col D
        'header_row_hint' => 'Model Number',   // text in the header row
        'currency'     => 'USD',
        'notes'        => 'International Distributor Price (USD). Rows with "(Optional)" suffix in model field are flagged optional.',
    ],
    'lucas_nuelle' => [
        'name'         => 'Lucas-Nülle GmbH',
        'short'        => 'Lucas-Nülle',
        'manufacturer' => 'Lucas-Nuelle',
        'logo'         => 'lucas-nuelle-logo.png',
        'formats'      => ['pdf', 'csv', 'xlsx'],
        'parser'       => 'ln_pdf',
        // PDF columns: Pos | Description | Order No. | Qty | Unit price EUR | Total EUR
        'currency'     => 'EUR',
        'notes'        => 'Prices in Euro. "Item N:" grouping headings map to proposal sections.',
    ],
    'aramco_spec' => [
        'name'         => 'Aramco Spec (DOCX)',
        'short'        => 'Aramco Spec',
        'manufacturer' => '',   // Mixed — determined per item
        'formats'      => ['docx', 'txt'],
        'parser'       => 'aramco_docx',
        'notes'        => 'Aramco tender spec DOCX. ITEM (N) blocks, model numbers in (PARENTHESES), QTY-N lines.',
    ],
];

try {
    $db = getDB();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    _ensureImportTables($db);

    switch ($action) {

        // ── Return vendor config list ─────────────────────────────────────
        case 'list_vendor_configs':
            ob_clean();
            $list = [];
            foreach ($VENDOR_CONFIGS as $key => $cfg) {
                $list[] = [
                    'key'      => $key,
                    'name'     => $cfg['name'],
                    'short'    => $cfg['short'],
                    'formats'  => $cfg['formats'],
                    'currency' => $cfg['currency'] ?? 'USD',
                    'notes'    => $cfg['notes'] ?? '',
                ];
            }
            echo json_encode(['ok' => true, 'vendors' => $list]);
            break;

        // ── Parse uploaded vendor document ────────────────────────────────
        case 'parse_vendor_doc':
            ob_clean();
            $vendorKey = $_POST['vendor'] ?? '';
            if (!$vendorKey || !isset($VENDOR_CONFIGS[$vendorKey])) {
                echo json_encode(['ok' => false, 'error' => 'Unknown vendor key: ' . $vendorKey]);
                break;
            }
            $cfg = $VENDOR_CONFIGS[$vendorKey];
            $f   = $_FILES['file'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['ok' => false, 'error' => 'No file uploaded or upload error ' . ($f['error'] ?? '?')]);
                break;
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $cfg['formats'])) {
                echo json_encode(['ok' => false, 'error' => "Format .$ext not supported for {$cfg['name']}. Expected: " . implode(', ', $cfg['formats'])]);
                break;
            }

            // Parse the document
            $parsed = _parseVendorDoc($f['tmp_name'], $ext, $cfg, $vendorKey);
            if (!$parsed['ok']) {
                echo json_encode($parsed);
                break;
            }

            // Compare each item against the products DB
            $items      = $parsed['items'];
            $comparison = _compareWithDB($db, $items, $cfg);

            echo json_encode([
                'ok'        => true,
                'vendor'    => $cfg['name'],
                'vendor_key'=> $vendorKey,
                'currency'  => $cfg['currency'] ?? 'USD',
                'meta'      => $parsed['meta'] ?? [],
                'items'     => $comparison['items'],
                'summary'   => $comparison['summary'],
                'sections'  => $parsed['sections'] ?? [],
            ]);
            break;

        // ── Import mode A: update catalog only ───────────────────────────
        case 'import_to_catalog':
            ob_clean();
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $result  = _importToCatalog($db, $payload);
            echo json_encode($result);
            break;

        // ── Import mode B: create quote pre-loaded with items ─────────────
        case 'import_to_quote':
            ob_clean();
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $result  = _importToQuote($db, $payload);
            echo json_encode($result);
            break;

        // ── Import mode A+B ───────────────────────────────────────────────
        case 'import_both':
            ob_clean();
            $payload   = json_decode(file_get_contents('php://input'), true) ?? [];
            $catResult = _importToCatalog($db, $payload);
            $qtResult  = _importToQuote($db, $payload);
            echo json_encode([
                'ok'      => $catResult['ok'] && $qtResult['ok'],
                'catalog' => $catResult,
                'quote'   => $qtResult,
            ]);
            break;

        default:
            ob_clean();
            echo json_encode(['ok' => false, 'error' => 'Unknown action: ' . $action]);
    }
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}


// ════════════════════════════════════════════════════════════════════════════
//  PARSERS
// ════════════════════════════════════════════════════════════════════════════

function _parseVendorDoc(string $tmpPath, string $ext, array $cfg, string $vendorKey): array {
    switch ($cfg['parser']) {
        case 'amatrol_excel': return _parseAmatrolExcel($tmpPath, $cfg);
        case 'ln_pdf':        return _parseLNPdf($tmpPath);
        case 'aramco_docx':   return _parseAramcoDocx($tmpPath, $ext);
        default:
            // Generic CSV/Excel fallback
            if (in_array($ext, ['xlsx','xls','csv'])) return _parseGenericExcel($tmpPath, $ext, $cfg);
            return ['ok' => false, 'error' => 'No parser available for this vendor/format'];
    }
}

// ── Amatrol Excel parser ─────────────────────────────────────────────────────
function _parseAmatrolExcel(string $path, array $cfg): array {
    // Read file as ZIP (xlsx) and parse with PHP's ZipArchive + SimpleXML
    // Fallback: read raw CSV if that fails
    $items    = [];
    $sections = []; // section groupings from blank-row separators
    $meta     = [];
    $rows     = _xlsxToRows($path);
    if ($rows === false) return ['ok' => false, 'error' => 'Could not read Excel file'];

    // Find header row
    $headerRow = -1;
    $hint      = strtoupper($cfg['header_row_hint'] ?? 'MODEL NUMBER');
    foreach ($rows as $i => $row) {
        $flat = strtoupper(implode(' ', array_map('strval', $row)));
        if (strpos($flat, $hint) !== false) { $headerRow = $i; break; }
    }

    // Extract metadata from rows 0..headerRow
    foreach (array_slice($rows, 0, max(0, $headerRow)) as $row) {
        $a = trim(strval($row[0] ?? ''));
        if (!$a) continue;
        if (!$meta && strlen($a) > 3 && strlen($a) < 60) {
            // First non-empty rows: dealer name, end-user, country, quote#, date
            static $metaKeys = ['dealer','end_user','country','quote_num','date'];
            static $metaIdx  = 0;
            if ($metaIdx < count($metaKeys)) {
                $meta[$metaKeys[$metaIdx]] = $a;
                $metaIdx++;
            }
        }
    }

    $cM = $cfg['col_model'] ?? 0;
    $cD = $cfg['col_desc']  ?? 1;
    $cQ = $cfg['col_qty']   ?? 2;
    $cP = $cfg['col_price'] ?? 3;

    $currentSection = 'Section 1';
    $sectionItems   = [];
    $sectionStart   = 0;

    foreach (array_slice($rows, $headerRow + 1) as $idx => $row) {
        $modelRaw = trim(strval($row[$cM] ?? ''));
        $descRaw  = trim(strval($row[$cD] ?? ''));
        $qtyRaw   = trim(strval($row[$cQ] ?? ''));
        $priceRaw = trim(strval($row[$cP] ?? ''));

        // Detect section total rows → new section
        if (!$modelRaw && stripos($descRaw, 'total') !== false && stripos($descRaw, 'section') !== false) {
            if (!empty($sectionItems)) {
                $sections[] = ['name' => $currentSection, 'items' => $sectionItems];
                $sectionItems = [];
            }
            // Extract section name from "Section N Total"
            if (preg_match('/section\s+(\d+)/i', $descRaw, $m)) {
                $currentSection = 'Section ' . ($m[1] + 1);
            }
            continue;
        }

        // Skip non-item rows
        if (!$modelRaw) continue;
        if (stripos($modelRaw, 'model') !== false && stripos($modelRaw, 'number') !== false) continue;
        if (stripos($modelRaw, 'customer') !== false) continue;
        if (stripos($modelRaw, 'amatrol') !== false && stripos($modelRaw, 'signature') !== false) continue;

        // Clean model — strip newlines and "(Optional)" suffix
        $isOptional = false;
        if (stripos($modelRaw, 'optional') !== false) { $isOptional = true; }
        $modelId = preg_replace('/\s*\(optional\)\s*/i', '', $modelRaw);
        $modelId = trim(preg_replace('/\s+/', '', $modelId)); // remove all whitespace from model#

        if (!$modelId || strlen($modelId) < 2) continue;

        // Clean description — first line only as title, rest as description
        $descLines  = array_map('trim', explode("\n", $descRaw));
        $titleOnly  = $descLines[0] ?? $modelId;
        $titleDesc  = implode(' ', array_slice($descLines, 1));
        $titleDesc  = trim(preg_replace('/\s+/', ' ', $titleDesc));

        // Parse price
        $price = null;
        if ($priceRaw && $priceRaw !== 'TBD' && is_numeric(str_replace([',', '$'], '', $priceRaw))) {
            $price = (float) str_replace([',', '$'], '', $priceRaw);
        }

        $qty = max(1, (int) $qtyRaw ?: 1);

        $item = [
            'model_id'          => $modelId,
            'title_only'        => $titleOnly,
            'title_description' => $titleDesc,
            'manufacturer'      => 'Amatrol',
            'product_class'     => 'learning-system',
            'qty'               => $qty,
            'vendor_price'      => $price,       // the price as quoted by vendor
            'vendor_currency'   => 'USD',
            'is_optional'       => $isOptional,
            'section'           => $currentSection,
        ];

        $items[]       = $item;
        $sectionItems[] = $item;
    }

    // Flush last section
    if (!empty($sectionItems)) {
        $sections[] = ['name' => $currentSection, 'items' => $sectionItems];
    }
    if (empty($sections) && !empty($items)) {
        $sections[] = ['name' => 'Section 1', 'items' => $items];
    }

    return ['ok' => true, 'items' => $items, 'sections' => $sections, 'meta' => $meta];
}

// ── Lucas-Nülle PDF parser ──────────────────────────────────────────────────
function _parseLNPdf(string $path): array {
    // Extract text from PDF using pdftotext if available
    $text = '';
    if (function_exists('exec')) {
        $escaped = escapeshellarg($path);
        @exec("pdftotext -layout $escaped - 2>/dev/null", $lines, $ret);
        if ($ret === 0) $text = implode("\n", $lines);
    }
    if (!$text) {
        // Try reading raw (limited — works for simple PDFs)
        $raw = file_get_contents($path);
        // Extract readable text from PDF stream
        preg_match_all('/BT\s+.*?ET/s', $raw, $m);
        foreach ($m[0] as $block) {
            preg_match_all('/\((.*?)\)\s*Tj/s', $block, $t);
            $text .= implode(' ', $t[1]) . "\n";
        }
    }

    $items    = [];
    $sections = [];
    $meta     = ['vendor' => 'Lucas-Nülle Middle East FZE', 'currency' => 'EUR'];

    // Extract quote number and date from header
    if (preg_match('/Quotation[:\s]+([A-Z0-9]+)/i', $text, $m)) $meta['quote_num'] = $m[1];
    if (preg_match('/Date[:\s]+(\d{1,2}\.\d{2}\.\d{4})/i', $text, $m))  $meta['date']      = $m[1];

    // Parse line items — LN format: "N  Description text  ORDER-NO  QTY  PRICE  TOTAL"
    // Pattern matches lines like: "1  Course Power electronics 4...  CO4204-7Q  32  1.269,00  40.608,00"
    // We parse by looking for lines containing a known order-number pattern
    $lines = explode("\n", $text);

    // Detect section headings like "Item 1-3:", "Item 4:", etc.
    $currentSection   = 'Section 1';
    $currentSectionLN = 'Item 1';
    $sectionMap       = [];   // LN item number → section name for quote builder
    $sectionItems     = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) continue;

        // Section heading: "Item N:" or "Item N-M:"
        if (preg_match('/^Item\s+([\d\-]+)\s*:/i', $line, $sm)) {
            if (!empty($sectionItems)) {
                $sections[] = ['name' => $currentSectionLN, 'items' => $sectionItems];
                $sectionItems = [];
            }
            $currentSectionLN = 'Item ' . $sm[1];
            $currentSection   = $currentSectionLN;
            continue;
        }

        // Line item: starts with number, ends with price pattern
        // Flexible regex: pos-num + description + order-no + qty + unit-price + total
        // Order No looks like: CO4204-7Q, SO2800-3U, SE2683-1R, LA-E2TR, LM9057, ST7200-4C
        if (preg_match(
            '/^\s*(\d+)\s+(.+?)\s+([A-Z]{2}[\w\-]{3,12})\s+(\d+)\s+([\d\.,]+)\s+([\d\.,]+)\s*$/u',
            $line, $lm
        )) {
            $titleOnly = trim($lm[2]);
            $orderNo   = trim($lm[3]);
            $qty       = (int) $lm[4];
            $unitPrice = (float) str_replace(['.', ','], ['', '.'], $lm[5]);
            // Handle European decimal format: 1.269,00 → 1269.00
            if (substr_count($lm[5], ',') === 1 && substr_count($lm[5], '.') >= 1) {
                $unitPrice = (float) str_replace(['.', ','], ['', '.'], $lm[5]);
            }

            $item = [
                'model_id'          => $orderNo,
                'title_only'        => $titleOnly,
                'title_description' => '',
                'manufacturer'      => 'Lucas-Nuelle',
                'product_class'     => 'learning-system',
                'qty'               => $qty,
                'vendor_price'      => $unitPrice,
                'vendor_currency'   => 'EUR',
                'is_optional'       => false,
                'section'           => $currentSection,
            ];
            $items[]       = $item;
            $sectionItems[] = $item;
        }
    }

    // Flush last section
    if (!empty($sectionItems)) {
        $sections[] = ['name' => $currentSectionLN, 'items' => $sectionItems];
    }
    if (empty($sections) && !empty($items)) {
        $sections[] = ['name' => 'Section 1', 'items' => $items];
    }

    // If PDF text extraction failed and we got no items, return structured error
    if (empty($items)) {
        return [
            'ok'   => false,
            'error'=> 'Could not extract line items from PDF. Ensure pdftotext is installed on the server, or upload as CSV/Excel instead.',
        ];
    }

    return ['ok' => true, 'items' => $items, 'sections' => $sections, 'meta' => $meta];
}

// ── Aramco Spec DOCX parser ──────────────────────────────────────────────────
function _parseAramcoDocx(string $path, string $ext): array {
    // For DOCX: unzip and read word/document.xml
    // For TXT: read directly
    $text = '';
    if ($ext === 'docx') {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xmlContent) {
                // Strip XML tags, decode entities
                $text = html_entity_decode(strip_tags($xmlContent), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
    } else {
        $text = file_get_contents($path);
    }

    if (!$text) return ['ok' => false, 'error' => 'Could not read document content'];

    $items    = [];
    $sections = [];
    $meta     = [];

    // Split on ITEM blocks: "ITEM (N)" or "ITEM (N.N)"
    // Pattern: ITEM followed by (number or number.number)
    $parts = preg_split('/\bITEM\s*\(\s*([\d\.]+)\s*\)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 1; $i < count($parts); $i += 2) {
        $itemNum = trim($parts[$i]);
        $body    = isset($parts[$i + 1]) ? $parts[$i + 1] : '';

        // First line of body = title/model line, e.g.:
        // "COURSE ELECTRICAL ENGINEERING 1: DC TECHNOLOGY (CO4204-4D)"
        $firstLine = trim(preg_replace('/\s+/', ' ', strtok($body, "\n")));

        // Extract model number in parentheses — last occurrence of (UPPER-NUM) pattern
        $modelId = '';
        if (preg_match_all('/\(([A-Z][A-Z0-9\-]{2,20})\)/', $firstLine, $mm)) {
            // Take the last match (most likely to be the model number vs section refs)
            $modelId = end($mm[1]);
        }
        // If no model found in first line, search first 3 lines
        if (!$modelId) {
            $firstThree = implode(' ', array_slice(explode("\n", $body), 0, 3));
            if (preg_match('/\(([A-Z][A-Z0-9\-]{3,20})\)/', $firstThree, $mm)) {
                $modelId = $mm[1];
            }
        }

        // Extract QTY: look for QTY-N or Qty - N or QTY-N
        $qty = 1;
        if (preg_match('/QTY[-\s]*[\-–]?\s*(\d+)/i', $body, $qm)) {
            $qty = (int) $qm[1];
        }

        // Title: everything before the model number parenthesis, trimmed
        $titleOnly = $firstLine;
        if ($modelId) {
            $titleOnly = trim(preg_replace('/\s*\(' . preg_quote($modelId, '/') . '\)\s*/i', '', $firstLine));
        }
        if (!$titleOnly) $titleOnly = 'Item ' . $itemNum;

        // Description: everything after the first line, cleaned
        $bodyLines  = array_slice(explode("\n", $body), 1);
        $cleanLines = [];
        foreach ($bodyLines as $bl) {
            $bl = trim(preg_replace('/\s+/', ' ', $bl));
            if ($bl && !preg_match('/^QTY[-\s]/i', $bl) && !preg_match('/^ITEM\s+\(/i', $bl)) {
                $cleanLines[] = $bl;
                if (count($cleanLines) >= 8) break; // cap description length
            }
        }
        $titleDesc = implode(' ', $cleanLines);

        // Determine manufacturer from model prefix patterns
        $mfr = _guessMfrFromModel($modelId);

        // Section: group by top-level item number (e.g. items 1, 1.1, 1.2 → same section)
        $topLevel = (int) $itemNum;
        $secName  = 'Item Group ' . $topLevel;

        $item = [
            'model_id'          => $modelId ?: ('ITEM-' . str_replace('.', '-', $itemNum)),
            'title_only'        => $titleOnly,
            'title_description' => trim($titleDesc),
            'manufacturer'      => $mfr,
            'product_class'     => 'learning-system',
            'qty'               => $qty,
            'vendor_price'      => null,
            'vendor_currency'   => 'USD',
            'is_optional'       => false,
            'section'           => $secName,
            'spec_item_num'     => $itemNum,   // preserve the Aramco spec item number
        ];

        $items[] = $item;
    }

    if (empty($items)) {
        return ['ok' => false, 'error' => 'No ITEM (N) blocks found in document. Ensure it matches the Aramco spec format.'];
    }

    // Build sections from item groupings
    $secGroups = [];
    foreach ($items as $it) {
        $secGroups[$it['section']][] = $it;
    }
    foreach ($secGroups as $name => $sitems) {
        $sections[] = ['name' => $name, 'items' => $sitems];
    }

    return ['ok' => true, 'items' => $items, 'sections' => $sections, 'meta' => $meta];
}

// ── Generic Excel/CSV fallback ────────────────────────────────────────────────
function _parseGenericExcel(string $path, string $ext, array $cfg): array {
    $rows = _xlsxToRows($path);
    if ($rows === false) return ['ok' => false, 'error' => 'Could not parse file'];

    $items = [];
    $cM = $cfg['col_model'] ?? 0;
    $cD = $cfg['col_desc']  ?? 1;
    $cQ = $cfg['col_qty']   ?? 2;
    $cP = $cfg['col_price'] ?? 3;
    $hint = strtoupper($cfg['header_row_hint'] ?? 'MODEL');
    $started = false;

    foreach ($rows as $row) {
        $flat = strtoupper(implode(' ', array_map('strval', $row)));
        if (!$started && strpos($flat, $hint) !== false) { $started = true; continue; }
        if (!$started) continue;
        $mid = trim(strval($row[$cM] ?? ''));
        if (!$mid) continue;
        $desc  = trim(strval($row[$cD] ?? ''));
        $lines = explode("\n", $desc);
        $price = (float) str_replace([',','$',' '], '', strval($row[$cP] ?? ''));
        $items[] = [
            'model_id'          => preg_replace('/\s+/', '', $mid),
            'title_only'        => trim($lines[0] ?? $mid),
            'title_description' => trim(implode(' ', array_slice($lines,1))),
            'manufacturer'      => $cfg['manufacturer'] ?? '',
            'product_class'     => 'learning-system',
            'qty'               => max(1, (int) strval($row[$cQ] ?? 1)),
            'vendor_price'      => $price ?: null,
            'vendor_currency'   => $cfg['currency'] ?? 'USD',
            'is_optional'       => false,
            'section'           => 'Section 1',
        ];
    }
    return ['ok' => true, 'items' => $items, 'sections' => [['name'=>'Section 1','items'=>$items]], 'meta'=>[]];
}


// ════════════════════════════════════════════════════════════════════════════
//  DB COMPARISON
// ════════════════════════════════════════════════════════════════════════════

function _compareWithDB(PDO $db, array $items, array $cfg): array {
    $summary = ['new' => 0, 'match' => 0, 'conflict' => 0, 'no_model' => 0];
    $out     = [];

    foreach ($items as $item) {
        $mid = $item['model_id'] ?? '';
        if (!$mid || strlen($mid) < 2) {
            $item['import_status'] = 'no_model';
            $item['db_record']     = null;
            $item['conflict_fields'] = [];
            $summary['no_model']++;
            $out[] = $item;
            continue;
        }

        $stmt = $db->prepare("SELECT model_id, title_only, title_description, manufacturer, product_class, intl_dist_net FROM products WHERE model_id = :m");
        $stmt->execute([':m' => $mid]);
        $dbRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dbRow) {
            $item['import_status']   = 'new';
            $item['db_record']       = null;
            $item['conflict_fields'] = [];
            $summary['new']++;
        } else {
            // Compare descriptions — normalise whitespace for comparison
            $conflicts = [];
            $norm = fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', $s ?? '')));
            if ($norm($item['title_only']) !== $norm($dbRow['title_only'] ?? '')) {
                $conflicts[] = [
                    'field'    => 'title_only',
                    'import'   => $item['title_only'],
                    'database' => $dbRow['title_only'],
                ];
            }
            // Only flag description conflicts if DB has a meaningful description
            $dbDesc = trim($dbRow['title_description'] ?? '');
            $itDesc = trim($item['title_description'] ?? '');
            if ($dbDesc && $itDesc && $norm($itDesc) !== $norm($dbDesc)) {
                // Check similarity — if >80% same tokens, not a real conflict
                $similarity = _tokenSimilarity($itDesc, $dbDesc);
                if ($similarity < 0.80) {
                    $conflicts[] = [
                        'field'      => 'title_description',
                        'import'     => substr($itDesc, 0, 200),
                        'database'   => substr($dbDesc, 0, 200),
                        'similarity' => round($similarity * 100),
                    ];
                }
            }

            if (empty($conflicts)) {
                $item['import_status']   = 'match';
                $summary['match']++;
            } else {
                $item['import_status']   = 'conflict';
                $summary['conflict']++;
            }
            $item['db_record']       = $dbRow;
            $item['conflict_fields'] = $conflicts;
        }

        $out[] = $item;
    }

    return ['items' => $out, 'summary' => $summary];
}


// ════════════════════════════════════════════════════════════════════════════
//  IMPORT MODES
// ════════════════════════════════════════════════════════════════════════════

function _importToCatalog(PDO $db, array $payload): array {
    $items    = $payload['items']    ?? [];
    $vendorKey= $payload['vendor_key'] ?? '';
    $added    = 0; $updated = 0; $skipped = 0; $log = [];

    foreach ($items as $item) {
        $mid    = trim($item['model_id'] ?? '');
        $status = $item['import_status'] ?? 'new';
        $action = $item['import_action'] ?? 'auto'; // 'add','skip','use_db','overwrite'

        if (!$mid) { $skipped++; continue; }

        // Determine what to do
        if ($action === 'skip' || ($status === 'conflict' && $action !== 'overwrite')) {
            $skipped++;
            $log[] = ['model_id' => $mid, 'result' => 'skipped', 'reason' => $status === 'conflict' ? 'conflict-kept-db' : 'user-skip'];
            continue;
        }
        if ($status === 'match' && $action !== 'overwrite') {
            // Match with no overwrite — still update vendor price if new
            $vendorPrice = isset($item['vendor_price']) ? (float)$item['vendor_price'] : null;
            if ($vendorPrice !== null) {
                $db->prepare("UPDATE products SET intl_dist_net = COALESCE(intl_dist_net, :vp) WHERE model_id = :m AND intl_dist_net IS NULL")
                   ->execute([':vp' => $vendorPrice, ':m' => $mid]);
            }
            $skipped++;
            $log[] = ['model_id' => $mid, 'result' => 'skipped', 'reason' => 'match-no-change'];
            continue;
        }

        // Upsert
        $title  = trim($item['title_only']        ?? $mid);
        $desc   = trim($item['title_description'] ?? '');
        $mfr    = trim($item['manufacturer']      ?? '');
        $cls    = trim($item['product_class']     ?? 'learning-system');
        $price  = isset($item['vendor_price']) && $item['vendor_price'] ? (float)$item['vendor_price'] : null;

        $exists = $db->prepare("SELECT model_id FROM products WHERE model_id=:m");
        $exists->execute([':m' => $mid]);

        if ($exists->fetch()) {
            if ($action === 'overwrite' || $status === 'new') {
                $db->prepare("UPDATE products SET title_only=:t, title_description=:d, manufacturer=:mfr, product_class=:cls, intl_dist_net=COALESCE(:p, intl_dist_net) WHERE model_id=:m")
                   ->execute([':t'=>$title,':d'=>$desc,':mfr'=>$mfr,':cls'=>$cls,':p'=>$price,':m'=>$mid]);
                $updated++;
                $log[] = ['model_id'=>$mid,'result'=>'updated'];
            }
        } else {
            $db->prepare("INSERT INTO products (model_id,title_only,title_description,manufacturer,product_class,intl_dist_net,data_source_row) VALUES(:m,:t,:d,:mfr,:cls,:p,:src)")
               ->execute([':m'=>$mid,':t'=>$title,':d'=>$desc,':mfr'=>$mfr,':cls'=>$cls,':p'=>$price,':src'=>'vendor_import_'.$vendorKey]);
            $added++;
            $log[] = ['model_id'=>$mid,'result'=>'added'];
        }
    }

    return ['ok'=>true,'added'=>$added,'updated'=>$updated,'skipped'=>$skipped,'log'=>$log];
}

function _importToQuote(PDO $db, array $payload): array {
    $items       = $payload['items']       ?? [];
    $sections    = $payload['sections']    ?? [];
    $vendorKey   = $payload['vendor_key']  ?? 'unknown';
    $meta        = $payload['meta']        ?? [];
    $clientName  = trim($payload['client_name'] ?? $meta['end_user'] ?? $meta['dealer'] ?? '');
    $country     = trim($payload['country'] ?? $meta['country'] ?? '');
    $currency    = trim($payload['currency']    ?? 'USD');
    $incoterm    = trim($payload['incoterm']    ?? '');
    $customItemNums = $payload['custom_item_nums'] ?? []; // map model_id → custom number

    // Build sections_json
    // Use provided sections if available, otherwise group all items into one section
    $quoteSections = [];
    if (!empty($sections)) {
        foreach ($sections as $sec) {
            $secItems = [];
            foreach ($sec['items'] as $it) {
                $mid = trim($it['model_id'] ?? '');
                if (!$mid) continue;
                $specNum = $customItemNums[$mid] ?? ($it['spec_item_num'] ?? null);
                $secItems[] = [
                    'lid'          => 'lid_' . $mid . '_' . uniqid(),
                    'model_id'     => $mid,
                    'title_only'   => $it['title_only'] ?? $mid,
                    'qty'          => max(1, (int)($it['qty'] ?? 1)),
                    'isSubOf'      => null,
                    'subPricing'   => 'included',
                    'isOptional'   => !empty($it['is_optional']),
                    'intl_dist_net'=> isset($it['vendor_price']) ? (float)$it['vendor_price'] : null,
                    'manufacturer' => $it['manufacturer'] ?? '',
                    'product_class'=> $it['product_class'] ?? 'learning-system',
                    'spec_item_num'=> $specNum,   // stored for display/invoice reference
                    'divisorKey'   => null,
                ];
            }
            if (!empty($secItems)) {
                $quoteSections[] = [
                    'id'    => 's' . uniqid(),
                    'name'  => $sec['name'],
                    'items' => $secItems,
                ];
            }
        }
    } else {
        // Flat import — one section
        $secItems = [];
        foreach ($items as $it) {
            $mid = trim($it['model_id'] ?? '');
            if (!$mid) continue;
            $specNum = $customItemNums[$mid] ?? ($it['spec_item_num'] ?? null);
            $secItems[] = [
                'lid'          => 'lid_' . $mid . '_' . uniqid(),
                'model_id'     => $mid,
                'title_only'   => $it['title_only'] ?? $mid,
                'qty'          => max(1,(int)($it['qty'] ?? 1)),
                'isSubOf'      => null,
                'subPricing'   => 'included',
                'isOptional'   => !empty($it['is_optional']),
                'intl_dist_net'=> isset($it['vendor_price']) ? (float)$it['vendor_price'] : null,
                'manufacturer' => $it['manufacturer'] ?? '',
                'product_class'=> $it['product_class'] ?? 'learning-system',
                'spec_item_num'=> $specNum,
                'divisorKey'   => null,
            ];
        }
        $quoteSections = [['id'=>'s'.uniqid(),'name'=>ucfirst($vendorKey).' Import','items'=>$secItems]];
    }

    // Generate quote number
    $year   = (int) date('Y');
    $s      = $db->prepare("SELECT COALESCE(MAX(base_seq),0) FROM quotes WHERE year=:y");
    $s->execute([':y' => $year]);
    $maxSeq = (int) $s->fetchColumn();
    $newSeq = max($maxSeq + 1, 320);
    $qnum   = 'INTL' . $year . '-' . $newSeq;

    $sectJ = json_encode([
        'sections'       => $quoteSections,
        'divisors'       => [],
        'ship_estimates' => [],
        'install_train_amt'   => 0,
        'install_train_label' => 'INSTALLATION AND COMMISSIONING',
        '_import_source' => $vendorKey,
        '_import_date'   => date('Y-m-d'),
    ]);

    $db->prepare(
        "INSERT INTO quotes (seq_num,year,base_seq,revision,quote_number,
             client_id,client_name,country,quote_date,currency,
             incoterm,inco_location,divisor,discount_pct,sections_json)
         VALUES (:sn,:yr,:bs,:rv,:qn,:ci,:cn,:co,:qd,:cu,:it,:il,:dv,:dp,:sj)"
    )->execute([
        ':sn' => $newSeq, ':yr' => $year, ':bs' => $newSeq, ':rv' => 0,
        ':qn' => $qnum,
        ':ci' => '', ':cn' => $clientName, ':co' => $country,
        ':qd' => date('Y-m-d'), ':cu' => $currency,
        ':it' => $incoterm, ':il' => '', ':dv' => 0.65, ':dp' => 0,
        ':sj' => $sectJ,
    ]);
    $newId = (int) $db->lastInsertId();

    return ['ok' => true, 'id' => $newId, 'quote_number' => $qnum, 'sections_count' => count($quoteSections)];
}


// ════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════════════════════

// Read XLSX file to array of rows using ZipArchive + SimpleXML
// Returns array of rows (each row = array of cell values) or false on error
function _xlsxToRows(string $path): array|false {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return false;

    // Read shared strings
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    $shared = [];
    if ($ssXml) {
        $ss = @simplexml_load_string($ssXml);
        if ($ss) {
            foreach ($ss->si as $si) {
                $t = '';
                if (isset($si->t)) { $t .= (string)$si->t; }
                foreach ($si->r ?? [] as $r) { if (isset($r->t)) $t .= (string)$r->t; }
                $shared[] = $t;
            }
        }
    }

    // Find first sheet
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $sheetFile = 'xl/worksheets/sheet1.xml';
    if ($wbXml) {
        $wb = @simplexml_load_string($wbXml);
        if ($wb) {
            $wb->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $sheets = $wb->xpath('//ns:sheet');
            if ($sheets && isset($sheets[0])) {
                // Get rId from sheet
                $attrs = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                if ($attrs && isset($attrs['id'])) {
                    $rId = (string)$attrs['id'];
                    // Look up in workbook rels
                    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                    if ($relsXml) {
                        $rels = @simplexml_load_string($relsXml);
                        if ($rels) {
                            foreach ($rels->Relationship as $rel) {
                                if ((string)$rel['Id'] === $rId) {
                                    $target = (string)$rel['Target'];
                                    $sheetFile = strpos($target,'xl/') === 0 ? $target : 'xl/'.$target;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $shXml = $zip->getFromName($sheetFile);
    $zip->close();
    if (!$shXml) return false;

    $sh = @simplexml_load_string($shXml);
    if (!$sh) return false;
    $sh->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $rows = [];
    foreach ($sh->sheetData->row ?? [] as $row) {
        $rowArr  = [];
        $rowIdx  = (int)($row['r'] ?? count($rows) + 1);
        $maxCol  = 0;

        foreach ($row->c as $cell) {
            $ref = (string)($cell['r'] ?? '');
            // Convert column ref (A,B,AA…) to 0-based index
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $cm);
            $colStr = $cm[1] ?? '';
            $col    = 0;
            foreach (str_split($colStr) as $ch) { $col = $col * 26 + (ord($ch) - ord('A') + 1); }
            $col--; // 0-based
            $maxCol = max($maxCol, $col);

            $t = (string)($cell['t'] ?? '');
            $v = (string)($cell->v ?? '');
            if ($t === 's') { $v = $shared[(int)$v] ?? ''; }     // shared string
            elseif ($t === 'b') { $v = $v === '1' ? true : false; }
            elseif ($v !== '' && is_numeric($v)) { $v = strpos($v, '.') !== false ? (float)$v : (int)$v; }
            $rowArr[$col] = $v;
        }

        // Pad row to maxCol
        for ($c = 0; $c <= $maxCol; $c++) { if (!array_key_exists($c, $rowArr)) $rowArr[$c] = null; }
        ksort($rowArr);
        // Pad rows array to rowIdx-1
        while (count($rows) < $rowIdx - 1) $rows[] = array_fill(0, $maxCol + 1, null);
        $rows[] = array_values($rowArr);
    }

    return $rows;
}

// Token-based similarity between two strings (0-1)
function _tokenSimilarity(string $a, string $b): float {
    $tokA = array_unique(preg_split('/\W+/', strtolower($a), -1, PREG_SPLIT_NO_EMPTY));
    $tokB = array_unique(preg_split('/\W+/', strtolower($b), -1, PREG_SPLIT_NO_EMPTY));
    if (!$tokA || !$tokB) return 0.0;
    $inter = count(array_intersect($tokA, $tokB));
    return $inter / max(count($tokA), count($tokB));
}

// Guess manufacturer from model number prefix patterns
function _guessMfrFromModel(string $mid): string {
    if (!$mid) return '';
    if (preg_match('/^(CO|SE|SO|LA|LM|ST)\d/i', $mid)) return 'Lucas-Nuelle';
    if (preg_match('/^(85|86|87|88|89|890|990|950|82|83|41|T55|90-)/i', $mid)) return 'Amatrol';
    if (preg_match('/^(TI-)/i', $mid)) return 'Technologies International';
    return '';
}

// Ensure the import_log table exists
function _ensureImportTables(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS vendor_import_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_key VARCHAR(40) NOT NULL,
        import_mode ENUM('catalog','quote','both') NOT NULL,
        items_added INT DEFAULT 0,
        items_updated INT DEFAULT 0,
        items_skipped INT DEFAULT 0,
        quote_id INT UNSIGNED DEFAULT NULL,
        quote_number VARCHAR(40) DEFAULT NULL,
        imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
