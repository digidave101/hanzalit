<?php
// invoice-api.php v4 — Technologies International, LLC
// Uses shared db.php from quotation engine — no hardcoded credentials

error_reporting(0);
ini_set('display_errors', 0);

// Include db.php FIRST before any headers so session_start() inside it can run
$db_php = realpath(__DIR__ . '/../quotation/db.php');
if ($db_php && file_exists($db_php)) {
    require_once $db_php;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

function send_json($data) {
    echo json_encode($data);
    exit;
}
function ok($data = [])  { send_json(array_merge(['success' => true], $data)); }
function err($msg)       { send_json(['success' => false, 'error' => $msg]); }

// Handle GET health check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ok(['msg' => 'invoice-api v4 online']);
}

// Parse input
$raw = file_get_contents('php://input');
if (empty(trim($raw))) { ok(['msg' => 'invoice-api v4 online', 'note' => 'empty body']); }
$in = json_decode($raw, true);
if (!is_array($in)) { err('Invalid JSON: ' . json_last_error_msg()); }

$action = $in['action'] ?? '';
$u = $in['auth_user'] ?? '';
$p = $in['auth_pass'] ?? '';

$USERS = ['dhanzal' => 'aA292199', 'fdegheidy' => 'Winner#1'];
if ($action !== 'ping' && (!isset($USERS[$u]) || $USERS[$u] !== $p)) { err('Unauthorized'); }

// ── DB via shared db.php ───────────────────────────────────
if (!function_exists('getDB')) { err('Cannot locate db.php or getDB()'); }
try {
    $pdo = getDB();
} catch (Exception $e) {
    err('DB connection failed: ' . $e->getMessage());
}

// PDO helper — escapes a value for safe interpolation
function esc($pdo, $v) {
    $q = $pdo->quote((string)($v ?? ''));
    // quote() wraps in single quotes — strip them for SET clause building
    return substr($q, 1, -1);
}

// ── ENSURE TABLE + COLUMNS ────────────────────────────────
try {
$pdo->exec("CREATE TABLE IF NOT EXISTS ti_invoices (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    inv_number     VARCHAR(50)  NOT NULL DEFAULT '',
    inv_date       DATE,
    po_number      VARCHAR(100) DEFAULT '',
    quote_id       INT          DEFAULT NULL,
    quote_number   VARCHAR(100) DEFAULT '',
    client_name    VARCHAR(255) DEFAULT '',
    client_addr    TEXT,
    ship_to        TEXT,
    end_user       TEXT,
    carrier        VARCHAR(255) DEFAULT '',
    country_of_origin VARCHAR(50) DEFAULT 'USA',
    incoterms      VARCHAR(100) DEFAULT '',
    payment_terms  VARCHAR(100) DEFAULT '',
    currency       VARCHAR(10)  DEFAULT 'USD',
    notes          TEXT,
    status         VARCHAR(20)  DEFAULT 'draft',
    discount_pct   DECIMAL(5,2) DEFAULT 0,
    pay_method     VARCHAR(100) DEFAULT '',
    bank_name      VARCHAR(100) DEFAULT '',
    bank_acct_name VARCHAR(255) DEFAULT '',
    bank_aba       VARCHAR(50)  DEFAULT '',
    bank_acct      VARCHAR(100) DEFAULT '',
    bank_swift     VARCHAR(50)  DEFAULT '',
    inv_email      VARCHAR(255) DEFAULT '',
    line_items     LONGTEXT,
    main_q_data    LONGTEXT,
    pl_data        LONGTEXT,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add missing columns safely
$need = [
    'po_number'=>"VARCHAR(100) DEFAULT ''", 'end_user'=>"TEXT",
    'carrier'=>"VARCHAR(255) DEFAULT ''", 'country_of_origin'=>"VARCHAR(50) DEFAULT 'USA'",
    'incoterms'=>"VARCHAR(100) DEFAULT ''", 'payment_terms'=>"VARCHAR(100) DEFAULT ''",
    'currency'=>"VARCHAR(10) DEFAULT 'USD'", 'discount_pct'=>"DECIMAL(5,2) DEFAULT 0",
    'pay_method'=>"VARCHAR(100) DEFAULT ''", 'bank_name'=>"VARCHAR(100) DEFAULT ''",
    'bank_acct_name'=>"VARCHAR(255) DEFAULT ''", 'bank_aba'=>"VARCHAR(50) DEFAULT ''",
    'bank_acct'=>"VARCHAR(100) DEFAULT ''", 'bank_swift'=>"VARCHAR(50) DEFAULT ''",
    'inv_email'=>"VARCHAR(255) DEFAULT ''", 'main_q_data'=>"LONGTEXT",
    'pl_data'=>"LONGTEXT", 'client_addr'=>"TEXT", 'ship_to'=>"TEXT",
    'notes'=>"TEXT", 'line_items'=>"LONGTEXT", 'quote_number'=>"VARCHAR(100) DEFAULT ''",
    'manufacturer'=>"TEXT",
    'freight_mode'=>"VARCHAR(20) DEFAULT 'ocean'",
    'port_loading'=>"VARCHAR(255) DEFAULT ''",
    'port_unloading'=>"VARCHAR(255) DEFAULT ''",
    'type_of_move'=>"VARCHAR(255) DEFAULT ''",
];
$cols = $pdo->query("SHOW COLUMNS FROM ti_invoices")->fetchAll(PDO::FETCH_COLUMN);
$existing = array_map('strtolower', $cols);
foreach ($need as $col => $def) {
    if (!in_array(strtolower($col), $existing)) {
        $pdo->exec("ALTER TABLE ti_invoices ADD COLUMN `$col` $def");
    }
}
} catch (Exception $e) { /* table already exists and is configured */ }

// Manufacturer address registry
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ti_manufacturer_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name_key VARCHAR(120) NOT NULL,
        display_name VARCHAR(200) NOT NULL,
        address TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_name_key (name_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* ok */ }

function mfr_norm_key($name) {
    return strtolower(preg_replace('/[^a-z0-9]/', '', (string)$name));
}

function mfr_is_full_address($addr, $name) {
    $addr = trim((string)$addr);
    $name = trim((string)$name);
    if (!$addr) return false;
    if (strpos($addr, "\n") !== false) return true;
    if ($name && strlen($addr) > strlen($name) + 12) return true;
    if (preg_match('/\d{3,}/', $addr)) return true;
    if (preg_match('/\b(st|street|ave|avenue|dr|drive|road|rd|suite|blvd|zip|usa)\b/i', $addr)) return true;
    return false;
}

function mfr_build_vendor_address($v) {
    $lines = [];
    if (!empty($v['company_name'])) $lines[] = trim($v['company_name']);
    if (!empty($v['address_line1'])) $lines[] = trim($v['address_line1']);
    if (!empty($v['address_line2'])) $lines[] = trim($v['address_line2']);
    $city = trim($v['city'] ?? '');
    $state = trim($v['state_province'] ?? '');
    $zip = trim($v['postal_code'] ?? '');
    $cityLine = trim(implode(', ', array_filter([$city, $state]))) . ($zip ? ' ' . $zip : '');
    if (trim($cityLine)) $lines[] = trim($cityLine);
    if (!empty($v['country'])) $lines[] = trim($v['country']);
    if (!empty($v['phone'])) $lines[] = 'Tel: ' . trim($v['phone']);
    return implode("\n", array_filter($lines));
}

function mfr_merge(&$map, $name, $address) {
    $name = trim((string)$name);
    $address = trim((string)$address);
    if (!$name) return;
    $key = mfr_norm_key($name);
    if (!$key) return;
    if (!isset($map[$key])) {
        $map[$key] = ['name' => $name, 'address' => $address ?: $name];
        return;
    }
    $curAddr = $map[$key]['address'];
    $curName = $map[$key]['name'];
    if (mfr_is_full_address($address, $name) && !mfr_is_full_address($curAddr, $curName)) {
        $map[$key]['address'] = $address;
    } elseif (mfr_is_full_address($address, $name) && mfr_is_full_address($curAddr, $curName) && strlen($address) > strlen($curAddr)) {
        $map[$key]['address'] = $address;
    }
    if (strlen($name) >= strlen($curName)) {
        $map[$key]['name'] = $name;
    }
}

function mfr_preset_list() {
    return [
        ['name' => 'Amatrol Inc.', 'address' => "Amatrol Inc.\n9701 Industrial Dr\nJeffersonville, IN 47130, USA"],
        ['name' => 'Amatrol', 'address' => "Amatrol Inc.\n9701 Industrial Dr\nJeffersonville, IN 47130, USA"],
        ['name' => 'DAC Worldwide', 'address' => "DAC Worldwide\n1300 South State Street\nHarrisonburg, VA 22801, USA"],
        ['name' => 'Bayport Technical', 'address' => "Bayport Technical\nHouston, TX, USA"],
        ['name' => 'Bayport International', 'address' => "Bayport International\nHouston, TX, USA"],
        ['name' => 'Lucas-Nülle', 'address' => "Lucas-Nülle GmbH\nSüdliche Münchner Str. 1\n82031 Grünwald, Germany"],
        ['name' => 'Minds-I-Education', 'address' => "Minds-I Education\nRochester, NY, USA"],
        ['name' => 'Minds-I Education', 'address' => "Minds-I Education\nRochester, NY, USA"],
    ];
}

function mfr_load_map($pdo) {
    $map = [];
    foreach (mfr_preset_list() as $p) {
        mfr_merge($map, $p['name'], $p['address']);
    }
    try {
        $rows = $pdo->query("SELECT display_name, address FROM ti_manufacturer_addresses ORDER BY display_name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            mfr_merge($map, $r['display_name'], $r['address']);
        }
    } catch (Exception $e) { /* ok */ }
    try {
        $vrows = $pdo->query("SELECT company_name,address_line1,address_line2,city,state_province,postal_code,country,phone FROM vendors WHERE status='active' OR status IS NULL ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($vrows as $v) {
            $addr = mfr_build_vendor_address($v);
            mfr_merge($map, $v['company_name'], $addr);
        }
    } catch (Exception $e) { /* vendors table optional */ }
    try {
        $names = $pdo->query("SELECT DISTINCT manufacturer FROM products WHERE manufacturer IS NOT NULL AND TRIM(manufacturer) != '' ORDER BY manufacturer")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($names as $name) {
            $name = trim($name);
            $key = mfr_norm_key($name);
            if (!isset($map[$key])) {
                // Try fuzzy match against existing map keys
                $matched = false;
                foreach ($map as $k => $entry) {
                    $levOk = (strlen($key) <= 64 && strlen($k) <= 64 && levenshtein($key, $k) <= 3);
                    if (stripos($name, $entry['name']) !== false || stripos($entry['name'], $name) !== false || $levOk) {
                        mfr_merge($map, $name, $entry['address']);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    mfr_merge($map, $name, $name);
                }
            } else {
                // name exists — ensure display name variant is stored
                mfr_merge($map, $name, $map[$key]['address']);
            }
        }
    } catch (Exception $e) { /* ok */ }
    return $map;
}

function mfr_lookup($pdo, $name) {
    $name = trim((string)$name);
    if (!$name) return '';
    $map = mfr_load_map($pdo);
    $key = mfr_norm_key($name);
    if (isset($map[$key])) return $map[$key]['address'];
    foreach ($map as $entry) {
        if (strcasecmp($entry['name'], $name) === 0) return $entry['address'];
        if (stripos($entry['name'], $name) !== false || stripos($name, $entry['name']) !== false) return $entry['address'];
    }
    return $name;
}

function mfr_save_registry($pdo, $fullText) {
    $fullText = trim((string)$fullText);
    if (!$fullText) return;
    $lines = preg_split('/\r?\n/', $fullText);
    $display = trim($lines[0] ?? '');
    if (!$display || !mfr_is_full_address($fullText, $display)) return;
    $key = mfr_norm_key($display);
    if (!$key) return;
    try {
        $s = $pdo->prepare("INSERT INTO ti_manufacturer_addresses (name_key, display_name, address) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), address=VALUES(address)");
        $s->execute([$key, $display, $fullText]);
    } catch (Exception $e) { /* ok */ }
}

function get_inv($pdo, $id) {
    $s = $pdo->prepare("SELECT * FROM ti_invoices WHERE id=? LIMIT 1");
    $s->execute([(int)$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── ROUTER ────────────────────────────────────────────────
switch ($action) {

case 'ping':
    ok(['msg'=>'pong','user'=>$u,'version'=>'v4']);
    break;

case 'get_stats':
    $tot    = (int)$pdo->query("SELECT COUNT(*) FROM ti_invoices")->fetchColumn();
    $draft  = (int)$pdo->query("SELECT COUNT(*) FROM ti_invoices WHERE status='draft'")->fetchColumn();
    $issued = (int)$pdo->query("SELECT COUNT(*) FROM ti_invoices WHERE status='issued'")->fetchColumn();
    $paid   = (int)$pdo->query("SELECT COUNT(*) FROM ti_invoices WHERE status='paid'")->fetchColumn();
    ok(['total'=>$tot,'draft'=>$draft,'issued'=>$issued,'paid'=>$paid]);
    break;

case 'list_invoices':
    $rows = $pdo->query("SELECT id,inv_number,inv_date,client_name,status,po_number FROM ti_invoices ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    ok(['invoices'=>$rows]);
    break;

case 'get_invoice':
    $id = (int)($in['id'] ?? 0);
    if (!$id) err('No ID');
    $row = get_inv($pdo, $id);
    if (!$row) err('Invoice not found: '.$id);
    // Resolve manufacturer name-only to full address for COO
    if (!empty($row['manufacturer'])) {
        $mfr = trim($row['manufacturer']);
        $lines = preg_split('/\r?\n/', $mfr);
        $first = trim($lines[0] ?? '');
        if (!mfr_is_full_address($mfr, $first)) {
            $resolved = mfr_lookup($pdo, $mfr);
            $rLines = preg_split('/\r?\n/', $resolved);
            if (mfr_is_full_address($resolved, trim($rLines[0] ?? ''))) {
                $row['manufacturer'] = $resolved;
            }
        }
    }
    // Unwrap double-encoded JSON fields
    foreach (['line_items', 'main_q_data', 'pl_data'] as $f2) {
        if (!empty($row[$f2]) && is_string($row[$f2])) {
            $d1 = json_decode($row[$f2], true);
            if (is_string($d1)) { $d1 = json_decode($d1, true); }
            if ($d1 !== null) { $row[$f2] = json_encode($d1); }
        }
    }
    ok(['invoice'=>$row]);
    break;

case 'create_invoice':
    $num    = $in['inv_number']   ?? '';
    $quote  = $in['quote_number'] ?? '';
    $client = $in['client_name']  ?? '';
    $status = $in['status']       ?? 'draft';
    if (!$num) err('Invoice number required');
    $s = $pdo->prepare("INSERT INTO ti_invoices (inv_number,inv_date,quote_number,client_name,status) VALUES (?,?,?,?,?)");
    if (!$s->execute([$num, date('Y-m-d'), $quote, $client, $status])) err('Create failed');
    ok(['id'=>(int)$pdo->lastInsertId(),'inv_number'=>$num]);
    break;

case 'save_invoice':
    $id = (int)($in['id'] ?? 0);
    if (!$id) err('No invoice ID');
    $s = $pdo->prepare("UPDATE ti_invoices SET
        inv_number=?, inv_date=?, po_number=?, quote_number=?,
        client_name=?, client_addr=?, ship_to=?, end_user=?,
        carrier=?, country_of_origin=?, incoterms=?, payment_terms=?,
        currency=?, notes=?, status=?, discount_pct=?,
        pay_method=?, bank_name=?, bank_acct_name=?,
        bank_aba=?, bank_acct=?, bank_swift=?, inv_email=?,
        line_items=?, main_q_data=?, manufacturer=?,
        freight_mode=?, port_loading=?, port_unloading=?, type_of_move=?
        WHERE id=?");
    $ok2 = $s->execute([
        $in['inv_number']        ?? '',
        $in['inv_date']          ?? date('Y-m-d'),
        $in['po_number']         ?? '',
        $in['quote_number']      ?? '',
        $in['client_name']       ?? '',
        $in['client_addr']       ?? '',
        $in['ship_to']           ?? '',
        $in['end_user']          ?? '',
        $in['carrier']           ?? '',
        $in['country_of_origin'] ?? 'USA',
        $in['incoterms']         ?? '',
        $in['payment_terms']     ?? '',
        $in['currency']          ?? 'USD',
        $in['notes']             ?? '',
        $in['status']            ?? 'draft',
        (float)($in['discount_pct'] ?? 0),
        $in['pay_method']        ?? '',
        $in['bank_name']         ?? '',
        $in['bank_acct_name']    ?? '',
        $in['bank_aba']          ?? '',
        $in['bank_acct']         ?? '',
        $in['bank_swift']        ?? '',
        $in['inv_email']         ?? '',
        $in['line_items']        ?? '[]',
        $in['main_q_data']       ?? '{}',
        $in['manufacturer']      ?? '',
        $in['freight_mode']      ?? 'ocean',
        $in['port_loading']      ?? '',
        $in['port_unloading']    ?? '',
        $in['type_of_move']      ?? '',
        $id
    ]);
    if (!$ok2) err('Save failed: '.implode(' ',$s->errorInfo()));
    mfr_save_registry($pdo, $in['manufacturer'] ?? '');
    ok(['updated'=>$id]);
    break;

case 'delete_invoice':
    $id = (int)($in['id'] ?? 0);
    if (!$id) err('No ID');
    $pdo->prepare("DELETE FROM ti_invoices WHERE id=?")->execute([$id]);
    ok(['deleted'=>$id]);
    break;

case 'save_pl':
    $id = (int)($in['id'] ?? 0);
    if (!$id) err('No ID');
    $s = $pdo->prepare("UPDATE ti_invoices SET pl_data=? WHERE id=?");
    if (!$s->execute([$in['pl_data'] ?? '{}', $id])) err('PL save failed');
    ok(['updated'=>$id]);
    break;

case 'get_hs_codes':
    ok(['codes'=>[
        ['code'=>'9023.00.00','description'=>'Educational/training instruments and apparatus'],
        ['code'=>'8543.70.96','description'=>'Electronic trainers and simulators'],
        ['code'=>'8537.10.90','description'=>'Boards, panels, control panels (electrical)'],
        ['code'=>'8501.10.40','description'=>'Electric motors under 37.5W'],
        ['code'=>'8501.20.00','description'=>'Universal AC/DC motors over 37.5W'],
        ['code'=>'8536.50.90','description'=>'Switches for electrical circuits'],
        ['code'=>'8544.42.90','description'=>'Electric conductors, voltage 80-1000V'],
        ['code'=>'9030.89.00','description'=>'Other instruments for measuring/testing'],
        ['code'=>'8471.50.00','description'=>'Processing units, computers'],
        ['code'=>'9031.80.80','description'=>'Measuring/checking instruments NES'],
        ['code'=>'8479.89.94','description'=>'Machines and mechanical appliances NES'],
        ['code'=>'EAR99',     'description'=>'No ECCN required — subject to EAR99'],
    ]]);
    break;

case 'list_quotes':
    $rows = $pdo->query("SELECT quote_number, client_name, country FROM quotes ORDER BY id DESC, revision DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
    ok(['quotes' => $rows]);
    break;

case 'debug_quote':
    $qnum = $in['quote_number'] ?? '';
    $s = $pdo->prepare("SELECT id, quote_number, client_name, sections_json FROM quotes WHERE quote_number=? LIMIT 1");
    $s->execute([$qnum]);
    $q = $s->fetch(PDO::FETCH_ASSOC);
    if (!$q) { ok(['found'=>false,'searched'=>$qnum]); break; }
    $raw = json_decode($q['sections_json'] ?? '[]', true);
    $preview = [];
    if (is_array($raw)) {
        $sections = isset($raw['sections']) ? $raw['sections'] : (isset($raw[0]) ? $raw : []);
        foreach (array_slice($sections,0,2) as $sec) {
            $items = $sec['items'] ?? [];
            $preview[] = ['section_keys'=>array_keys($sec), 'item_count'=>count($items), 'first_item_keys'=>$items ? array_keys($items[0]) : []];
        }
    }
    ok(['found'=>true,'quote_number'=>$q['quote_number'],'sections_type'=>gettype($raw),'sections_keys'=>is_array($raw)?array_keys($raw):[],'preview'=>$preview]);
    break;

case 'get_quote_items':
    $qnum = $in['quote_number'] ?? '';
    if (!$qnum) err('No quote number');
    $s = $pdo->prepare("SELECT sections_json,client_name FROM quotes WHERE quote_number=? LIMIT 1");
    $s->execute([$qnum]);
    $q = $s->fetch(PDO::FETCH_ASSOC);
    if (!$q) err('Quote not found: '.$qnum);
    $raw = json_decode($q['sections_json'] ?? '[]', true) ?: [];
    $sections = isset($raw['sections']) ? $raw['sections'] : (isset($raw[0]) ? $raw : []);

    // Collect all model_ids to batch-fetch full specs from products table
    $modelIds = [];
    foreach ($sections as $sec) {
        foreach ((array)($sec['items'] ?? []) as $item) {
            $mid = trim((string)($item['model_id'] ?? ''));
            if ($mid) $modelIds[] = $mid;
        }
    }
    // Fetch full title_description from products table
    $productData = [];
    if (!empty($modelIds)) {
        $ph = implode(',', array_fill(0, count($modelIds), '?'));
        $ps = $pdo->prepare("SELECT model_id, title_only, title_description, manufacturer FROM products WHERE model_id IN ($ph)");
        $ps->execute($modelIds);
        foreach ($ps->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $productData[$row['model_id']] = $row;
        }
    }

    $mfrCounts = [];
    $items = [];
    foreach ($sections as $sec) {
        foreach ((array)($sec['items'] ?? []) as $item) {
            $item_num = trim((string)($item['spec_item_num'] ?? $item['custom_num'] ?? ''));
            $part_no  = trim((string)($item['model_id'] ?? $item['model'] ?? $item['part_no'] ?? ''));
            $qty      = (int)($item['qty'] ?? 1);
            $price    = (float)($item['intl_dist_net'] ?? $item['unit_price'] ?? $item['price'] ?? 0);
            if (!$part_no) continue;

            // Build full description by combining title + full spec from products DB
            $dbProd   = $productData[$part_no] ?? [];
            $mfr      = trim((string)($dbProd['manufacturer'] ?? $item['manufacturer'] ?? ''));
            if ($mfr) { $mfrCounts[$mfr] = ($mfrCounts[$mfr] ?? 0) + $qty; }
            $title    = trim((string)($dbProd['title_only']      ?? $item['title_only'] ?? $item['title'] ?? $part_no));
            $fullSpec = trim((string)($dbProd['title_description'] ?? $item['title_description'] ?? ''));
            // full_description = title + full technical spec (for invoice print)
            $fullDesc = $title . ($fullSpec ? "
" . $fullSpec : '');

            $items[] = [
                'item_num'         => $item_num,
                'part_no'          => $part_no,
                'description'      => $title,     // short title for editor display
                'full_description' => $fullDesc,  // full spec for invoice print
                'qty'              => $qty,
                'unit'             => 'EA',
                'unit_price'       => $price,
                'eccn'             => (string)($item['eccn'] ?? 'EAR99'),
                'origin'           => 'USA',
                'manufacturer'     => $mfr,
            ];
        }
    }
    $suggestedMfr = '';
    if ($mfrCounts) {
        arsort($mfrCounts);
        $suggestedMfr = array_key_first($mfrCounts);
    }
    ok(['items'=>$items,'client_name'=>$q['client_name'] ?? '','suggested_manufacturer'=>$suggestedMfr]);
    break;

case 'save_line_items':
    $id = (int)($in['id'] ?? 0);
    if (!$id) err('No ID');
    $li = $in['line_items'] ?? '[]';
    $s = $pdo->prepare("UPDATE ti_invoices SET line_items=? WHERE id=?");
    if (!$s->execute([$li, $id])) err('Save line items failed');
    ok(['updated' => $id]);
    break;

case 'list_manufacturers':
    $map = mfr_load_map($pdo);
    $list = array_values($map);
    usort($list, function($a, $b) {
        $af = mfr_is_full_address($a['address'], $a['name']) ? 0 : 1;
        $bf = mfr_is_full_address($b['address'], $b['name']) ? 0 : 1;
        if ($af !== $bf) return $af - $bf;
        return strcasecmp($a['name'], $b['name']);
    });
    ok(['manufacturers' => $list, 'presets' => mfr_preset_list(), 'from_db' => $list]);
    break;

case 'get_manufacturer_address':
    $name = trim($in['name'] ?? '');
    if (!$name) err('No manufacturer name');
    $addr = mfr_lookup($pdo, $name);
    ok(['name' => $name, 'address' => $addr, 'has_full_address' => mfr_is_full_address($addr, strtok($addr, "\n"))]);
    break;

case 'save_manufacturer_address':
    $name = trim($in['name'] ?? '');
    $address = trim($in['address'] ?? '');
    if (!$name || !$address) err('Name and address required');
    mfr_save_registry($pdo, $address);
    ok(['saved' => true]);
    break;

case 'getSignature':
    $sigPaths = [
        dirname(__DIR__) . '/admin/secure_assets/signature.png',
        dirname(__DIR__) . '/secure_assets/signature.png',
        __DIR__ . '/../admin/secure_assets/signature.png',
        (isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '') . '/portal/admin/secure_assets/signature.png',
        (isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '') . '/admin/secure_assets/signature.png',
    ];
    foreach ($sigPaths as $path) {
        if ($path && file_exists($path)) {
            $data = base64_encode(file_get_contents($path));
            ok(['dataUrl' => 'data:image/png;base64,' . $data, 'url' => '/portal/admin/secure_assets/signature.png']);
        }
    }
    err('Signature file not found — check portal/admin/secure_assets/signature.png');
    break;

default:
    err('Unknown action: '.htmlspecialchars($action));
}
