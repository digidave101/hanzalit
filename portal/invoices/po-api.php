<?php
// po-api.php â€” TI Kitmeer Purchase Order Module Backend
// Mirrors invoice-api.php patterns exactly

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

define('DB_HOST', 'localhost');
define('DB_NAME', 'u538922476_mainamatrol');
define('DB_USER', 'u538922476_admin');
define('DB_PASS', '$Lovewins1');

$VALID_USERS = [
    'dhanzal'   => ['Kitmeer2024!', 'aA292199'],
    'fdegheidy' => ['Kitmeer2024!', 'aA292199'],
];

function checkAuth($body) {
    global $VALID_USERS;
    $u = $body['auth_user'] ?? $body['user'] ?? '';
    $p = $body['auth_pass'] ?? $body['pass'] ?? '';
    if (!isset($VALID_USERS[$u])) return false;
    return in_array($p, (array)$VALID_USERS[$u]);
}

function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}

function ensureTables() {
    $db = getDB();
    // Purchase Orders table
    $db->exec("CREATE TABLE IF NOT EXISTS `purchase_orders` (
        `id`            INT AUTO_INCREMENT PRIMARY KEY,
        `po_number`     VARCHAR(40) NOT NULL UNIQUE,
        `status`        ENUM('draft','issued','acknowledged','received','closed') DEFAULT 'draft',
        `vendor_id`     INT DEFAULT NULL,
        `vendor_name`   VARCHAR(200),
        `vendor_address`TEXT,
        `vendor_contact`VARCHAR(200),
        `vendor_email`  VARCHAR(200),
        `bill_to`       TEXT,
        `ship_to`       TEXT,
        `ship_via`      VARCHAR(100),
        `incoterms`     VARCHAR(60),
        `payment_terms` VARCHAR(100),
        `currency`      VARCHAR(10) DEFAULT 'USD',
        `items_json`    LONGTEXT,
        `subtotal`      DECIMAL(14,2) DEFAULT 0,
        `tax`           DECIMAL(14,2) DEFAULT 0,
        `shipping`      DECIMAL(14,2) DEFAULT 0,
        `total`         DECIMAL(14,2) DEFAULT 0,
        `notes`         TEXT,
        `linked_invoice_id` INT DEFAULT NULL,
        `linked_quote_id`   INT DEFAULT NULL,
        `created_by`    VARCHAR(60),
        `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Vendors table
    $db->exec("CREATE TABLE IF NOT EXISTS `vendors` (
        `id`            INT AUTO_INCREMENT PRIMARY KEY,
        `vendor_code`   VARCHAR(40) UNIQUE,
        `company_name`  VARCHAR(200) NOT NULL,
        `company_name_ar` VARCHAR(200),
        `category`      VARCHAR(100),
        `contact_name`  VARCHAR(200),
        `contact_title` VARCHAR(100),
        `email`         VARCHAR(200),
        `phone`         VARCHAR(80),
        `fax`           VARCHAR(80),
        `website`       VARCHAR(200),
        `address_line1` VARCHAR(200),
        `address_line2` VARCHAR(200),
        `city`          VARCHAR(100),
        `state_province`VARCHAR(100),
        `postal_code`   VARCHAR(30),
        `country`       VARCHAR(100) DEFAULT 'United States',
        `payment_terms` VARCHAR(100),
        `currency`      VARCHAR(10) DEFAULT 'USD',
        `bank_name`     VARCHAR(200),
        `bank_account`  VARCHAR(100),
        `bank_routing`  VARCHAR(100),
        `bank_swift`    VARCHAR(40),
        `bank_iban`     VARCHAR(60),
        `tax_id`        VARCHAR(60),
        `notes`         TEXT,
        `is_preferred`  TINYINT(1) DEFAULT 0,
        `status`        ENUM('active','inactive') DEFAULT 'active',
        `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

if (!checkAuth($body)) {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit;
}

try {
    ensureTables();
    $db = getDB();

    // â”€â”€ PO ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if ($action === 'listPOs') {
        $status = $body['status'] ?? '';
        $sql = "SELECT id,po_number,status,vendor_name,total,currency,created_at,updated_at,linked_invoice_id,linked_quote_id FROM purchase_orders";
        if ($status && $status !== 'all') $sql .= " WHERE status='".addslashes($status)."'";
        $sql .= " ORDER BY created_at DESC";
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        // Stats
        $stats = $db->query("SELECT status, COUNT(*) c, COALESCE(SUM(total),0) tot FROM purchase_orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'pos'=>$rows,'stats'=>$stats]); exit;
    }

    if ($action === 'getPO') {
        $id = (int)($body['id'] ?? 0);
        $row = $db->query("SELECT * FROM purchase_orders WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
        $row['items'] = json_decode($row['items_json'] ?? '[]', true);
        echo json_encode(['success'=>true,'po'=>$row]); exit;
    }

    if ($action === 'savePO') {
        $po = $body['po'] ?? [];
        // Auto-generate PO number if new
        if (empty($po['id'])) {
            $year = date('Y');
            $row  = $db->query("SELECT MAX(CAST(SUBSTRING_INDEX(po_number,'-',-1) AS UNSIGNED)) AS mx FROM purchase_orders WHERE po_number LIKE 'PO-$year-%'")->fetch(PDO::FETCH_ASSOC);
            $next = max(($row['mx'] ?? 0) + 1, 320);
            $po['po_number'] = "PO-$year-".str_pad($next,4,'0',STR_PAD_LEFT);
        }
        $items_json = json_encode($po['items'] ?? []);
        // Totals
        $subtotal = 0;
        foreach (($po['items'] ?? []) as $it) $subtotal += floatval($it['qty']??1)*floatval($it['unit_price']??0);
        $tax      = floatval($po['tax']     ?? 0);
        $shipping = floatval($po['shipping']?? 0);
        $total    = $subtotal + $tax + $shipping;

        if (empty($po['id'])) {
            $stmt = $db->prepare("INSERT INTO purchase_orders
                (po_number,status,vendor_id,vendor_name,vendor_address,vendor_contact,vendor_email,
                 bill_to,ship_to,ship_via,incoterms,payment_terms,currency,
                 items_json,subtotal,tax,shipping,total,notes,
                 linked_invoice_id,linked_quote_id,created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $po['po_number'], $po['status']??'draft',
                $po['vendor_id']??null, $po['vendor_name']??'', $po['vendor_address']??'',
                $po['vendor_contact']??'', $po['vendor_email']??'',
                $po['bill_to']??'', $po['ship_to']??'', $po['ship_via']??'', $po['incoterms']??'',
                $po['payment_terms']??'', $po['currency']??'USD',
                $items_json, $subtotal, $tax, $shipping, $total,
                $po['notes']??'', $po['linked_invoice_id']??null, $po['linked_quote_id']??null,
                $body['auth_user']??$body['user']??''
            ]);
            $po['id'] = $db->lastInsertId();
        } else {
            $id = (int)$po['id'];
            $stmt = $db->prepare("UPDATE purchase_orders SET
                status=?,vendor_id=?,vendor_name=?,vendor_address=?,vendor_contact=?,vendor_email=?,
                bill_to=?,ship_to=?,ship_via=?,incoterms=?,payment_terms=?,currency=?,
                items_json=?,subtotal=?,tax=?,shipping=?,total=?,notes=?,
                linked_invoice_id=?,linked_quote_id=?
                WHERE id=?");
            $stmt->execute([
                $po['status']??'draft', $po['vendor_id']??null,
                $po['vendor_name']??'', $po['vendor_address']??'', $po['vendor_contact']??'', $po['vendor_email']??'',
                $po['bill_to']??'', $po['ship_to']??'', $po['ship_via']??'', $po['incoterms']??'',
                $po['payment_terms']??'', $po['currency']??'USD',
                $items_json, $subtotal, $tax, $shipping, $total,
                $po['notes']??'', $po['linked_invoice_id']??null, $po['linked_quote_id']??null,
                $id
            ]);
        }
        $po['subtotal']=$subtotal; $po['tax']=$tax; $po['shipping']=$shipping; $po['total']=$total;
        echo json_encode(['success'=>true,'po'=>$po]); exit;
    }

    if ($action === 'deletePO') {
        $id = (int)($body['id'] ?? 0);
        $db->exec("DELETE FROM purchase_orders WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'updatePOStatus') {
        $id = (int)($body['id'] ?? 0);
        $st = addslashes($body['status'] ?? 'draft');
        $db->exec("UPDATE purchase_orders SET status='$st' WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }

    // â”€â”€ VENDOR ACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if ($action === 'listVendors') {
        $search = addslashes($body['search'] ?? '');
        $cat    = addslashes($body['category'] ?? '');
        $sql = "SELECT * FROM vendors WHERE 1=1";
        if ($search) $sql .= " AND (company_name LIKE '%$search%' OR vendor_code LIKE '%$search%' OR email LIKE '%$search%' OR contact_name LIKE '%$search%')";
        if ($cat)    $sql .= " AND category='$cat'";
        $sql .= " ORDER BY is_preferred DESC, company_name ASC";
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $cats = $db->query("SELECT DISTINCT category FROM vendors WHERE category IS NOT NULL AND category!='' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success'=>true,'vendors'=>$rows,'categories'=>$cats]); exit;
    }

    if ($action === 'getVendor') {
        $id = (int)($body['id'] ?? 0);
        $row = $db->query("SELECT * FROM vendors WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'vendor'=>$row]); exit;
    }

    if ($action === 'saveVendor') {
        $v = $body['vendor'] ?? [];
        $fields = ['company_name','company_name_ar','category','contact_name','contact_title',
                   'email','phone','fax','website','address_line1','address_line2','city',
                   'state_province','postal_code','country','payment_terms','currency',
                   'bank_name','bank_account','bank_routing','bank_swift','bank_iban',
                   'tax_id','notes','is_preferred','status'];
        // Auto vendor code
        if (empty($v['id']) && empty($v['vendor_code'])) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Z]/i','', $v['company_name']??'VND'), 0, 4));
            $row2 = $db->query("SELECT COUNT(*) c FROM vendors")->fetch(PDO::FETCH_ASSOC);
            $v['vendor_code'] = $prefix.'-'.str_pad(($row2['c']??0)+1,3,'0',STR_PAD_LEFT);
        }
        if (empty($v['id'])) {
            $cols = implode(',', array_merge(['vendor_code'], $fields));
            $vals = implode(',', array_fill(0, count($fields)+1, '?'));
            $stmt = $db->prepare("INSERT INTO vendors ($cols) VALUES ($vals)");
            $params = [$v['vendor_code']??''];
            foreach ($fields as $f) $params[] = $v[$f] ?? (in_array($f,['is_preferred'])? 0 : '');
            $stmt->execute($params);
            $v['id'] = $db->lastInsertId();
        } else {
            $id = (int)$v['id'];
            $sets = implode(',', array_map(fn($f)=>"`$f`=?", $fields));
            $stmt = $db->prepare("UPDATE vendors SET $sets WHERE id=?");
            $params = [];
            foreach ($fields as $f) $params[] = $v[$f] ?? (in_array($f,['is_preferred'])? 0 : '');
            $params[] = $id;
            $stmt->execute($params);
        }
        echo json_encode(['success'=>true,'vendor'=>$v]); exit;
    }

    if ($action === 'deleteVendor') {
        $id = (int)($body['id'] ?? 0);
        $db->exec("DELETE FROM vendors WHERE id=$id");
        echo json_encode(['success'=>true]); exit;
    }

    // Import quotes into PO
    if ($action === 'listQuotes') {
        // Discover actual columns to avoid SQL errors
        $cols = [];
        foreach ($db->query("DESCRIBE quotes")->fetchAll(PDO::FETCH_ASSOC) as $col) $cols[] = $col['Field'];
        $want = ['id','quote_number','client_name','country','currency','created_at','updated_at','sections_json'];
        $sel  = array_values(array_intersect($want, $cols));
        if (!in_array('id',$sel)) $sel[] = 'id';
        if (!in_array('quote_number',$sel)) $sel[] = 'quote_number';
        $orderCol = in_array('updated_at',$cols) ? 'updated_at' : (in_array('created_at',$cols) ? 'created_at' : 'id');
        $search = trim($body['search'] ?? '');
        $sql = 'SELECT '.implode(',',$sel).' FROM quotes';
        if ($search) {
            $s = addslashes($search);
            $norm = strtoupper(preg_replace('/\s+/', '', $search));
            $revAlt = preg_replace('/(\d+)-?R(\d*)$/i', '$1-R$2', $norm);
            $sql .= " WHERE (quote_number LIKE '%$s%' OR client_name LIKE '%$s%'";
            $sql .= " OR UPPER(REPLACE(quote_number,' ','')) LIKE '%$norm%'";
            if ($revAlt !== $norm) $sql .= " OR UPPER(REPLACE(quote_number,' ','')) LIKE '%".addslashes($revAlt)."%'";
            $sql .= ")";
        }
        $sql .= " ORDER BY $orderCol DESC LIMIT 200";
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        // Compute display total from sections_json if no total column
        $hasTotalCol = in_array('total',$cols) || in_array('total_price',$cols);
        foreach ($rows as &$row) {
            if (!$hasTotalCol && !empty($row['sections_json'])) {
                $data = json_decode($row['sections_json'], true);
                $secs = $data['sections'] ?? (is_array($data) && isset($data[0]) ? $data : []);
                $t = 0;
                foreach ($secs as $sec) {
                    $div = floatval($sec['divisor'] ?? 0.65);
                    foreach ($sec['items'] ?? [] as $it) {
                        if (!empty($it['included'])) continue;
                        $net = floatval($it['intl_dist_net'] ?? 0);
                        $t += $div > 0 ? round(($net / $div) * floatval($it['qty'] ?? 1), 2) : 0;
                    }
                }
                $row['computed_total'] = $t;
            }
            unset($row['sections_json']); // don't send full JSON in list
        }
        unset($row);
        echo json_encode(['success'=>true,'quotes'=>$rows]); exit;
    }

    if ($action === 'getQuoteItems') {
        $id   = (int)($body['id'] ?? 0);
        $qnum = trim($body['quote_number'] ?? $body['quoteNumber'] ?? '');
        if (!$id && $qnum) {
            $stmt = $db->prepare("SELECT id, sections_json, quote_number FROM quotes WHERE quote_number = ? OR quote_number LIKE ? ORDER BY revision DESC, id DESC LIMIT 1");
            $stmt->execute([$qnum, $qnum.'%']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $row = $db->query("SELECT id, sections_json, quote_number FROM quotes WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        }
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Quote not found in main quotation database. Try the full number e.g. INTL2026-340-R1']); exit; }
        $data = json_decode($row['sections_json']??'[]', true);
        $sections  = isset($data['sections']) ? $data['sections'] : (is_array($data) && isset($data[0]) ? $data : []);
        $globalDiv = floatval($data['divisors'][0] ?? $data['divisor'] ?? 0.65);

        // Collect all model IDs first so we can batch-fetch title_description from products
        $modelIds = [];
        foreach ($sections as $sec) {
            foreach ($sec['items'] ?? [] as $it) {
                $mid = trim($it['model_id'] ?? '');
                if ($mid) $modelIds[] = $mid;
            }
        }
        // Batch-fetch title_description + manufacturer from products table
        $productData = [];
        if (!empty($modelIds)) {
            $uniq = array_unique($modelIds);
            $ph   = implode(',', array_fill(0, count($uniq), '?'));
            $stmt = $db->prepare("SELECT model_id, title_only, title_description, manufacturer FROM products WHERE model_id IN ($ph)");
            $stmt->execute($uniq);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $productData[$p['model_id']] = $p;
            }
        }

        $items = [];
        foreach ($sections as $sec) {
            $div = floatval($sec['divisor'] ?? $globalDiv ?: 0.65);
            foreach ($sec['items'] ?? [] as $it) {
                if (!empty($it['included'])) continue;
                $mid   = trim($it['model_id'] ?? '');
                $qty   = floatval($it['qty'] ?? 1);
                $net   = floatval($it['intl_dist_net'] ?? $it['list_price'] ?? 0);
                $price = $div > 0 ? round($net / $div, 2) : $net;

                // Pull from products DB if available â€” authoritative spec data
                $dbProd = $productData[$mid] ?? null;
                $title  = $dbProd['title_only']        ?? $it['title_only'] ?? $it['name'] ?? $it['description'] ?? $mid;
                $spec   = $dbProd['title_description']  ?? '';
                $mfr    = $dbProd['manufacturer']       ?? $it['manufacturer'] ?? '';
                if (!$mfr && stripos($mid, '41') === 0) $mfr = 'Amatrol';

                $items[] = [
                    'description'       => $title,
                    'spec_description'  => $spec,   // full technical specification text
                    'part_number'       => $mid,
                    'qty'               => $qty,
                    'unit'              => 'EA',
                    'unit_price'        => $price,
                    'manufacturer'      => $mfr,
                    'notes'             => ''
                ];
            }
        }
        echo json_encode(['success'=>true,'items'=>$items,'quote_number'=>$row['quote_number']]); exit;
    }


    // â”€â”€ Address book: clients-db.json + vendors + personal contacts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if ($action === 'listClients') {
        $search = strtolower(trim($body['search'] ?? ''));
        $results = [];

        // â”€â”€ 1. Personal / Internal (always pinned at top) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $personal = [
            ['id'=>'p1','source'=>'internal','company_name'=>'Technologies International, LLC',
             'contact_name'=>'David Hanzal','email'=>'david@tieducational.com',
             'city'=>'New York','country'=>'United States',
             'address'=>"Technologies International, LLC\n3149 Broadway, Suite 16\nNew York, NY 10027\nUnited States"],
            ['id'=>'p2','source'=>'internal','company_name'=>'Kitmeer FZ',
             'contact_name'=>'Fady Degheidy','email'=>'fady@kitmeer.com',
             'city'=>'Dubai','country'=>'United Arab Emirates',
             'address'=>"Kitmeer FZ\nDubai, United Arab Emirates"],
            ['id'=>'p3','source'=>'internal','company_name'=>'Saudi Aramco Americas (AACO)',
             'contact_name'=>'Alejandra Rivas','email'=>'InvoicesASC@aramcoamericas.com',
             'city'=>'Houston','country'=>'United States',
             'address'=>"Saudi Aramco Americas (AACO)\n9009 West Loop South\nHouston, TX 77096\nUnited States"],
            ['id'=>'p4','source'=>'internal','company_name'=>'Kuehne+Nagel Houston',
             'contact_name'=>'Karla Baez','email'=>'',
             'city'=>'Houston','country'=>'United States',
             'address'=>"Kuehne+Nagel\n10777 Westheimer Road, Suite 1100\nHouston, TX 77042\nUnited States"],
        ];
        foreach ($personal as $p) {
            if (!$search || strpos(strtolower($p['company_name'].$p['contact_name'].$p['city']), $search) !== false)
                $results[] = $p;
        }

        // â”€â”€ 2. clients-db.json  (same source as quotation engine) â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // Try multiple possible paths
        $jsonPaths = [
            dirname(__DIR__) . '/clients-db.json',
            dirname(__DIR__) . '/quotation/clients-db.json',
            dirname(__DIR__) . '/admin/clients-db.json',
            __DIR__ . '/clients-db.json',
            __DIR__ . '/../clients-db.json',
        ];
        $rawClients = [];
        foreach ($jsonPaths as $path) {
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path), true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $rawClients = $decoded;
                    break;
                }
            }
        }
        foreach ($rawClients as $cl) {
            $name = trim($cl['name'] ?? $cl['company_name'] ?? '');
            if (!$name) continue;
            // Get primary contact
            $primary = null;
            foreach ($cl['contacts'] ?? [] as $ct) {
                if ($ct['isPrimary'] ?? false) { $primary = $ct; break; }
            }
            if (!$primary && !empty($cl['contacts'])) $primary = $cl['contacts'][0];
            $contactName = trim($primary['name']  ?? $cl['contact_name'] ?? '');
            $email       = trim($primary['email'] ?? $cl['email']        ?? '');
            $country     = trim($cl['country'] ?? '');
            $city        = trim($cl['city']    ?? '');
            // Build address
            $addrParts = array_filter([$name,
                trim($cl['address_line1'] ?? $cl['address'] ?? ''),
                trim($cl['address_line2'] ?? ''),
                trim($city . ($cl['postal_code'] ?? '')),
                $country
            ]);
            $addr = implode("\n", $addrParts) ?: "$name\n$country";
            // Search filter
            $haystack = strtolower($name.$contactName.$city.$country);
            if ($search && strpos($haystack, $search) === false) continue;
            $results[] = [
                'id'           => 'q'.$cl['id'],
                'source'       => 'client',
                'company_name' => $name,
                'contact_name' => $contactName,
                'email'        => $email,
                'city'         => $city ?: $country,
                'country'      => $country,
                'address'      => $addr,
            ];
        }

        // â”€â”€ 3. Vendors table (for buy-from use case) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if (($body['include_vendors'] ?? false)) {
            try {
                $vrows = $db->query("SELECT * FROM vendors WHERE status='active' ORDER BY company_name ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($vrows as $v) {
                    $addrParts = array_filter([
                        $v['company_name'],
                        $v['address_line1'] ?? '',
                        $v['address_line2'] ?? '',
                        trim(($v['city']??'').' '.($v['postal_code']??'')),
                        $v['country'] ?? '',
                    ]);
                    $haystack = strtolower(($v['company_name']??'').($v['category']??'').($v['city']??''));
                    if ($search && strpos($haystack, $search) === false) continue;
                    $results[] = [
                        'id'           => 'v'.$v['id'],
                        'source'       => 'vendor',
                        'company_name' => $v['company_name'] ?? '',
                        'contact_name' => $v['contact_name'] ?? '',
                        'email'        => $v['email'] ?? '',
                        'city'         => $v['city'] ?? '',
                        'country'      => $v['country'] ?? '',
                        'address'      => implode("\n", array_filter($addrParts)),
                        'payment_terms'=> $v['payment_terms'] ?? '',
                        'currency'     => $v['currency'] ?? 'USD',
                    ];
                }
            } catch (Exception $vex) { /* vendors table may not exist yet */ }
        }

        echo json_encode(['success'=>true,'clients'=>$results]); exit;
    }

    // â”€â”€ Serve signature image as base64 (authenticated) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if ($action === 'getSignature') {
        $sigPaths = [
            dirname(__DIR__) . '/admin/secure_assets/signature.png',
            dirname(__DIR__) . '/secure_assets/signature.png',
            __DIR__ . '/../admin/secure_assets/signature.png',
        ];
        foreach ($sigPaths as $path) {
            if (file_exists($path)) {
                $data = base64_encode(file_get_contents($path));
                echo json_encode(['success'=>true,'dataUrl'=>'data:image/png;base64,'.$data]);
                exit;
            }
        }
        echo json_encode(['success'=>false,'error'=>'Signature file not found']); exit;
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action: '.$action]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
