<?php
// ti-kitmeer.com/portal/quotation/api.php
ob_start();

// ── FATAL ERROR SHUTDOWN HANDLER ─────────────────────────────────────────────
// Catches memory exhaustion, timeouts, parse errors that kill the script before
// ob_clean() runs — ensures browser always gets valid JSON, not empty response.
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'ok'    => false,
            'error' => 'Server fatal: ' . $err['message'] . ' (line ' . $err['line'] . ')'
        ]);
    }
});

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/payment_terms.php';

$raw    = file_get_contents('php://input');
$body   = ($raw && strlen($raw) > 2) ? (json_decode($raw, true) ?? []) : [];
$action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

define('CLIENTS_JSON',    dirname(__DIR__) . '/clients-db.json');
define('QUOTE_SEQ_FLOOR', 319);

// ── GOOGLE PLACES API ─────────────────────────────────────────────────────────
// Paste your key here. Get one free at: console.cloud.google.com
// Enable: "Places API" · Restrict key to your server IP for security.
define('GOOGLE_PLACES_KEY', 'AIzaSyA4maSz8_JY9AX-xCrU5PK-RmAzQgzouCw');   // ← YOUR KEY HERE

try {
    $db = getDB();
    // Allow named parameters to appear more than once in a single statement.
    // Required by ProposalBuilder.php and protects against HY093 in any query.
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    try { ensureQuotesTable($db); } catch (Throwable $e) {}

    switch ($action) {

        // ── DIAGNOSTIC ───────────────────────────────────────────────────────
        case 'diag':
            ob_clean();
            // Basic server diagnostics
            $docCount = 0;
            try { $docCount = (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn(); } catch(Throwable $e){}
            echo json_encode([
                'ok'       => true,
                'php'      => PHP_VERSION,
                'products' => (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'docs'     => $docCount,
            ]);
            break;

        case 'diag_docs':
            ob_clean();
            $checkIds = $body['ids'] ?? [];
            $result = [];
            foreach($checkIds as $mid){
                $mid = trim($mid);
                if(!$mid) continue;
                $s = $db->prepare("SELECT model_id, combined_pdf FROM product_documents WHERE model_id=? LIMIT 1");
                $s->execute([$mid]);
                $row = $s->fetch(PDO::FETCH_ASSOC);
                $dbPath  = $row ? $row['combined_pdf'] : null;
                $absPath1 = $dbPath ? __DIR__.'/'.$dbPath : null;
                $absPath2 = $dbPath ? __DIR__.'/product_docs/'.basename($dbPath) : null;
                $safe = preg_replace('/[^A-Za-z0-9_\-]/','', $mid);
                $directFile = __DIR__.'/product_docs/'.$safe.'.pdf';
                $result[$mid] = [
                    'db_path'        => $dbPath,
                    'db_abs_ok'      => $absPath1 ? file_exists($absPath1) : false,
                    'db_basename_ok' => $absPath2 ? file_exists($absPath2) : false,
                    'direct_path'    => 'product_docs/'.$safe.'.pdf',
                    'direct_ok'      => file_exists($directFile),
                    'direct_size'    => file_exists($directFile) ? filesize($directFile) : 0,
                ];
            }
            // List ALL files in product_docs/
            $allFiles = [];
            $pdDir = __DIR__.'/product_docs/';
            if(is_dir($pdDir)){
                foreach(scandir($pdDir) as $f){
                    if($f==='.'||$f==='..') continue;
                    $allFiles[] = $f.' ('.filesize($pdDir.$f).' bytes)';
                }
            }
            // Test exec() availability
            $execAvail = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
            $gsVer = ''; $gsPath = '';
            if($execAvail){
                foreach(['/usr/bin/gs','/usr/local/bin/gs','gs'] as $gsBin){
                    $gv=[]; $gr=null; @exec($gsBin.' --version 2>&1',$gv,$gr);
                    if($gr===0){ $gsVer=implode('',$gv); $gsPath=$gsBin; break; }
                }
            }
            echo json_encode([
                'ok'=>true,
                'results'=>$result,
                'product_docs_dir'=>$pdDir,
                'product_docs_dir_exists'=>is_dir($pdDir),
                'all_files_in_product_docs'=>$allFiles,
                'exec_available'=>$execAvail,
                'gs_found'=>$gsPath,
                'gs_version'=>$gsVer,
                '__DIR__'=>__DIR__,
            ]);
            break;

        // ── ARABIC NAME LOOKUP — website scan proxy ───────────────────────
        case 'fetch_arabic_name':
            $rawUrl  = trim($_GET['url'] ?? '');
            $company = trim($_GET['company'] ?? '');
            if(!$rawUrl){ ob_clean(); echo json_encode(['ok'=>false,'error'=>'No URL']); break; }

            // Normalise URL
            if(!preg_match('/^https?:\/\//i', $rawUrl)) $rawUrl = 'https://' . $rawUrl;
            // Safety: only fetch public HTTP(S) URLs, no local IPs
            $host = parse_url($rawUrl, PHP_URL_HOST);
            if(!$host || preg_match('/^(localhost|127\.|192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/i', $host)){
                ob_clean(); echo json_encode(['ok'=>false,'error'=>'Blocked']); break;
            }

            $ctx = stream_context_create(['http'=>[
                'timeout'        => 8,
                'ignore_errors'  => true,
                'follow_location'=> true,
                'max_redirects'  => 3,
                'user_agent'     => 'Mozilla/5.0 (compatible; TI-Kitmeer/1.0)',
                'header'         => "Accept-Language: ar,en\r\n",
            ]]);
            $html = @file_get_contents($rawUrl, false, $ctx);
            if(!$html){ ob_clean(); echo json_encode(['ok'=>false,'error'=>'Could not fetch']); break; }

            // Try to find Arabic company name in the page
            $arName = '';

            // 1. og:site_name or og:title in Arabic script
            if(preg_match('/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']*[\x{0600}-\x{06FF}][^"\']*)["\'][^>]*>/ui', $html, $m)){
                $arName = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
            }
            // 2. <title> tag containing Arabic
            if(!$arName && preg_match('/<title[^>]*>([^<]*[\x{0600}-\x{06FF}][^<]*)<\/title>/ui', $html, $m)){
                $arName = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
                // Strip separators like " | " or " - " and take the Arabic part
                $parts = preg_split('/[\|\-–—]/', $arName);
                foreach($parts as $part){
                    if(preg_match('/[\x{0600}-\x{06FF}]/u', $part)){
                        $arName = trim($part); break;
                    }
                }
            }
            // 3. <html lang="ar"> page — grab a prominent heading
            if(!$arName && stripos($html, 'lang="ar"') !== false){
                if(preg_match('/<h1[^>]*>([\s\S]{1,200}?)<\/h1>/i', $html, $m)){
                    $t = strip_tags($m[1]);
                    if(preg_match('/[\x{0600}-\x{06FF}]/u', $t)) $arName = trim($t);
                }
            }

            ob_clean();
            if($arName){
                echo json_encode(['ok'=>true,'arabic_name'=> mb_substr($arName, 0, 100, 'UTF-8')]);
            } else {
                echo json_encode(['ok'=>false,'error'=>'No Arabic text found on page']);
            }
            break;
            ob_clean();
            echo json_encode([
                'ok'       => true,
                'php'      => PHP_VERSION,
                'memory'   => ini_get('memory_limit'),
                'max_exec' => ini_get('max_execution_time'),
                'zip'      => class_exists('ZipArchive') ? 'YES' : 'NO',
                'products' => (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'docs'     => (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn(),
                'fpdi'     => file_exists(__DIR__.'/vendor/autoload.php') ? 'YES' : 'NO',
            ]);
            break;

        // ── PRODUCT SEARCH ──────────────────────────────────────────────────
        case 'search':
            $q     = trim($_GET['q']   ?? '');
            $mfr   = trim($_GET['mfr'] ?? '');
            $cls   = trim($_GET['cls'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 100), 200);
            $where = ['1=1']; $params = [];
            if ($q !== '') {
                $where[] = '(model_id LIKE :q OR title_only LIKE :q2 OR key_topic LIKE :q3)';
                $like = '%'.$q.'%';
                $params[':q']=$params[':q2']=$params[':q3']=$like;
            }
            if ($mfr !== '') { $where[] = 'manufacturer = :mfr'; $params[':mfr'] = $mfr; }
            if ($cls !== '') { $where[] = 'product_class = :cls'; $params[':cls'] = $cls; }
            $sql = "SELECT model_id,title_only,manufacturer,product_class,key_topic,
                           intl_market_price,intl_market_price_note,
                           intl_dist_net,intl_comm_pct,requires_models,recommended_models,mfr_lead_time,dimensions
                    FROM products WHERE ".implode(' AND ',$where)."
                    ORDER BY CASE WHEN model_id LIKE :exact THEN 0 ELSE 1 END, model_id
                    LIMIT :lim";
            $stmt = $db->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
            $stmt->bindValue(':exact',$q!==''?$q.'%':'%',PDO::PARAM_STR);
            $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['requires_models']    = $r['requires_models']    ? json_decode($r['requires_models'],true)    : [];
                $r['recommended_models'] = $r['recommended_models'] ? json_decode($r['recommended_models'],true) : [];
                $r['intl_market_price'] = $r['intl_market_price']!==null?(float)$r['intl_market_price']:null;
                $r['intl_dist_net']     = $r['intl_dist_net']!==null?(float)$r['intl_dist_net']:null;
                $r['intl_comm_pct']     = $r['intl_comm_pct']!==null?(float)$r['intl_comm_pct']:null;
            }
            echo json_encode(['ok'=>true,'results'=>$rows,'count'=>count($rows)]);
            break;

        // ── UPDATE PRODUCT NET PRICE ──────────────────────────────────────
        case 'update_product_price':
            $mid    = trim($_POST['model_id'] ?? '');
            $newNet = isset($_POST['intl_dist_net']) ? round((float)$_POST['intl_dist_net'], 2) : null;
            $reason = trim($_POST['reason'] ?? 'Manual override');
            $quotes = trim($_POST['quotes'] ?? '');
            ob_clean();
            if(!$mid || $newNet === null || $newNet < 0){
                echo json_encode(['ok'=>false,'error'=>'Missing params']); break;
            }
            $stmt = $db->prepare("UPDATE products SET
                intl_dist_net = :net,
                intl_market_price_note = CONCAT_WS(' | ',
                    IF(intl_market_price_note IS NULL OR intl_market_price_note='' OR intl_market_price_note IS NULL,
                        '', CONCAT(intl_market_price_note, ' | ')),
                    CONCAT('★ Updated ', CURDATE(), ': ', :reason,
                        IF(:quotes2!='', CONCAT(' [',  :quotes2, ']'), ''))
                )
                WHERE model_id = :mid");
            $stmt->execute([':net'=>$newNet,':reason'=>$reason,':quotes2'=>$quotes,':mid'=>$mid]);
            echo json_encode(['ok'=>true,'updated'=>$stmt->rowCount()]);
            break;

        // ── MANUAL PRODUCT ADD / UPDATE ───────────────────────────────────
        case 'add_manual_product':
            // Insert or update a custom product record
            ob_clean();
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $mid   = trim($input['model_id'] ?? '');
            if(!$mid){ echo json_encode(['ok'=>false,'error'=>'model_id required']); break; }
            $title  = trim($input['title_only']        ?? '');
            $desc   = trim($input['title_description'] ?? '');
            $mfr    = trim($input['manufacturer']      ?? '');
            $cls    = trim($input['product_class']     ?? '');
            $net    = isset($input['intl_dist_net'])  ? round((float)$input['intl_dist_net'],  2) : null;
            $list   = isset($input['intl_market_price']) ? round((float)$input['intl_market_price'], 2) : null;
            $lead   = trim($input['mfr_lead_time']     ?? '');
            $dims   = trim($input['dimensions']        ?? '');
            $reqs   = isset($input['requires_models'])  ? json_encode($input['requires_models'])  : '[]';
            $recs   = isset($input['recommended_models'])? json_encode($input['recommended_models']): '[]';
            $addedOn = date('Y-m-d');
            $note   = trim($input['notes'] ?? '');
            // Check if exists
            $exists = $db->prepare("SELECT model_id FROM products WHERE model_id=:m");
            $exists->execute([':m'=>$mid]);
            if($exists->fetch()){
                $stmt=$db->prepare("UPDATE products SET title_only=:t,title_description=:d,manufacturer=:mfr,
                    product_class=:cls,intl_dist_net=:net,intl_market_price=:list,mfr_lead_time=:lead,
                    dimensions=:dims,requires_models=:req,recommended_models=:rec,
                    intl_market_price_note=:note
                    WHERE model_id=:m");
            } else {
                $stmt=$db->prepare("INSERT INTO products (model_id,title_only,title_description,manufacturer,
                    product_class,intl_dist_net,intl_market_price,mfr_lead_time,dimensions,
                    requires_models,recommended_models,intl_market_price_note,data_source_row)
                    VALUES(:m,:t,:d,:mfr,:cls,:net,:list,:lead,:dims,:req,:rec,:note,NULL)");
            }
            $stmt->execute([':m'=>$mid,':t'=>$title,':d'=>$desc,':mfr'=>$mfr,':cls'=>$cls,
                ':net'=>$net,':list'=>$list,':lead'=>$lead,':dims'=>$dims,
                ':req'=>$reqs,':rec'=>$recs,':note'=>$note?$note:'Manual entry '.$addedOn]);
            echo json_encode(['ok'=>true,'model_id'=>$mid,'action'=>$exists->rowCount()?'updated':'inserted']);
            break;

        // ── UPLOAD / UPDATE PRODUCT PDF ────────────────────────────────────
        case 'upload_product_pdf':
            ob_clean();
            $mid = trim($_POST['model_id'] ?? '');
            if(!$mid){ echo json_encode(['ok'=>false,'error'=>'No model_id']); break; }
            $f = $_FILES['pdf'] ?? null;
            if(!$f || $f['error']!==UPLOAD_ERR_OK){ echo json_encode(['ok'=>false,'error'=>'No file or upload error']); break; }
            $ext = strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            if($ext !== 'pdf'){ echo json_encode(['ok'=>false,'error'=>'Only PDF files accepted']); break; }
            $dir = __DIR__ . '/product_docs/';
            if(!is_dir($dir)) mkdir($dir,0755,true);
            $safe = preg_replace('/[^a-zA-Z0-9_\-]/','',$mid);
            $fname = $safe . '.pdf';
            $destPath = $dir.$fname;
            $mode = $_POST['mode'] ?? 'replace';
            // Always replace (merge requires server-side tools not available on shared hosting)
            if(!move_uploaded_file($f['tmp_name'], $destPath)){
                echo json_encode(['ok'=>false,'error'=>'Could not save file']); break;
            }
            // Upsert product_documents
            $db->exec("CREATE TABLE IF NOT EXISTS product_documents (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                model_id VARCHAR(50) NOT NULL,
                combined_pdf VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uidx_mid (model_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt=$db->prepare("INSERT INTO product_documents (model_id,combined_pdf) VALUES(:m,:p)
                ON DUPLICATE KEY UPDATE combined_pdf=:p2");
            $stmt->execute([':m'=>$mid,':p'=>'product_docs/'.$fname,':p2'=>'product_docs/'.$fname]);
            // Try to make the PDF compatible with FPDI (which only handles PDF 1.4 and below
            // without the paid parser). Use Imagick if available to re-render each page.
            _ensureFpdiCompatible($destPath);

            $msg = 'replaced';
            echo json_encode(['ok'=>true,'path'=>'product_docs/'.$fname,'action'=>$msg]);
            break;

        case 'places_search':
            $q = trim($_GET['q'] ?? '');
            ob_clean();
            if(!$q){ echo json_encode(['ok'=>false,'predictions'=>[]]); break; }
            if(!GOOGLE_PLACES_KEY){
                echo json_encode(['ok'=>false,'error'=>'no_key','predictions'=>[]]);
                break;
            }
            $ctx = stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true,
                'user_agent'=>'TI-Kitmeer/1.0']]);
            $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json'
                 . '?input='         . urlencode($q)
                 . '&types=establishment|geocode'
                 . '&language=en'
                 . '&key='           . GOOGLE_PLACES_KEY;
            $raw = @file_get_contents($url, false, $ctx);
            if(!$raw){ echo json_encode(['ok'=>false,'error'=>'fetch_failed','predictions'=>[]]); break; }
            $data = json_decode($raw, true);
            echo json_encode([
                'ok'          => ($data['status'] ?? '') === 'OK' || !empty($data['predictions']),
                'status'      => $data['status'] ?? 'UNKNOWN',
                'predictions' => array_slice($data['predictions'] ?? [], 0, 6),
            ]);
            break;

        case 'places_detail':
            $pid = trim($_GET['place_id'] ?? '');
            ob_clean();
            if(!$pid){ echo json_encode(['ok'=>false,'error'=>'Missing place_id']); break; }
            if(!GOOGLE_PLACES_KEY){ echo json_encode(['ok'=>false,'error'=>'no_key']); break; }
            $ctx = stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);
            $url = 'https://maps.googleapis.com/maps/api/place/details/json'
                 . '?place_id='   . urlencode($pid)
                 . '&fields=name,formatted_address,address_components,website,international_phone_number'
                 . '&language=en'
                 . '&key='        . GOOGLE_PLACES_KEY;
            $raw = @file_get_contents($url, false, $ctx);
            if(!$raw){ echo json_encode(['ok'=>false,'error'=>'fetch_failed']); break; }
            $data = json_decode($raw, true);
            echo json_encode([
                'ok'     => ($data['status'] ?? '') === 'OK',
                'status' => $data['status'] ?? 'UNKNOWN',
                'result' => $data['result'] ?? null,
            ]);
            break;
            $q     = trim($_GET['q']   ?? '');
            $mfr   = trim($_GET['mfr'] ?? '');
            $cls   = trim($_GET['cls'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 100), 200);
            $where = ['1=1']; $params = [];
            if ($q !== '') {
                $where[] = '(model_id LIKE :q OR title_only LIKE :q2 OR key_topic LIKE :q3)';
                $like = '%'.$q.'%';
                $params[':q']=$params[':q2']=$params[':q3']=$like;
            }
            if ($mfr !== '') { $where[] = 'manufacturer = :mfr'; $params[':mfr'] = $mfr; }
            if ($cls !== '') { $where[] = 'product_class = :cls'; $params[':cls'] = $cls; }
            $sql = "SELECT model_id,title_only,manufacturer,product_class,key_topic,
                           intl_market_price,intl_market_price_note,
                           intl_dist_net,intl_comm_pct,requires_models,recommended_models,mfr_lead_time,dimensions
                    FROM products WHERE ".implode(' AND ',$where)."
                    ORDER BY CASE WHEN model_id LIKE :exact THEN 0 ELSE 1 END, model_id
                    LIMIT :lim";
            $stmt = $db->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
            $stmt->bindValue(':exact',$q!==''?$q.'%':'%',PDO::PARAM_STR);
            $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['requires_models']    = $r['requires_models']    ? json_decode($r['requires_models'],true)    : [];
                $r['recommended_models'] = $r['recommended_models'] ? json_decode($r['recommended_models'],true) : [];
                $r['intl_market_price'] = $r['intl_market_price']!==null?(float)$r['intl_market_price']:null;
                $r['intl_dist_net']     = $r['intl_dist_net']!==null?(float)$r['intl_dist_net']:null;
                $r['intl_comm_pct']     = $r['intl_comm_pct']!==null?(float)$r['intl_comm_pct']:null;
            }
            echo json_encode(['ok'=>true,'results'=>$rows,'count'=>count($rows)]);
            break;

        case 'product':
            $mid = trim($_GET['id'] ?? '');
            if (!$mid) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
            $stmt = $db->prepare("SELECT * FROM products WHERE model_id=:id");
            $stmt->execute([':id'=>$mid]);
            $row = $stmt->fetch();
            if (!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); break; }
            $row['requires_models']    = $row['requires_models']    ? json_decode($row['requires_models'],true)    : [];
            $row['recommended_models'] = $row['recommended_models'] ? json_decode($row['recommended_models'],true) : [];
            $row['intl_dist_net']      = $row['intl_dist_net']!==null?(float)$row['intl_dist_net']:null;
            $stmt2 = $db->prepare("SELECT child_model_id FROM product_dependencies WHERE parent_model_id=:id");
            $stmt2->execute([':id'=>$mid]);
            $row['required_by'] = $stmt2->fetchAll(PDO::FETCH_COLUMN,0);
            echo json_encode(['ok'=>true,'product'=>$row]);
            break;

        case 'get_required':
            $ids = $body['ids'] ?? [];
            if (!is_array($ids)||empty($ids)) { echo json_encode(['ok'=>false,'error'=>'No ids']); break; }
            $ph = implode(',',array_fill(0,count($ids),'?'));
            $stmt = $db->prepare("SELECT model_id,title_only,manufacturer,product_class,
                                         intl_market_price,intl_market_price_note,
                                         intl_dist_net,intl_comm_pct,requires_models,recommended_models,mfr_lead_time
                                  FROM products WHERE model_id IN ($ph)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['requires_models']    = $r['requires_models']    ? json_decode($r['requires_models'],true)    : [];
                $r['recommended_models'] = $r['recommended_models'] ? json_decode($r['recommended_models'],true) : [];
                $r['intl_dist_net']      = $r['intl_dist_net']!==null?(float)$r['intl_dist_net']:null;
            }
            echo json_encode(['ok'=>true,'products'=>$rows]);
            break;

        case 'get_recommended':
            $ids = $body['ids'] ?? [];
            if (!is_array($ids)||empty($ids)) { echo json_encode(['ok'=>false,'error'=>'No ids']); break; }
            $ph = implode(',',array_fill(0,count($ids),'?'));
            $stmt = $db->prepare("SELECT model_id,title_only,manufacturer,product_class,
                                         intl_market_price,intl_market_price_note,
                                         intl_dist_net,intl_comm_pct,requires_models,recommended_models,mfr_lead_time
                                  FROM products WHERE model_id IN ($ph)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['requires_models']    = $r['requires_models']    ? json_decode($r['requires_models'],true)    : [];
                $r['recommended_models'] = $r['recommended_models'] ? json_decode($r['recommended_models'],true) : [];
                $r['intl_dist_net']      = $r['intl_dist_net']!==null?(float)$r['intl_dist_net']:null;
            }
            echo json_encode(['ok'=>true,'products'=>$rows]);
            break;

        case 'filters':
            $mfrs   = $db->query("SELECT DISTINCT manufacturer FROM products WHERE manufacturer IS NOT NULL ORDER BY manufacturer")->fetchAll(PDO::FETCH_COLUMN);
            $classes= $db->query("SELECT DISTINCT product_class FROM products WHERE product_class IS NOT NULL ORDER BY product_class")->fetchAll(PDO::FETCH_COLUMN);
            $total  = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
            echo json_encode(['ok'=>true,'manufacturers'=>$mfrs,'classes'=>$classes,'total'=>(int)$total]);
            break;

        case 'clients':
            $rawClients = file_exists(CLIENTS_JSON)
                ? (json_decode(file_get_contents(CLIENTS_JSON), true) ?: [])
                : [];
            $result = [];
            foreach ($rawClients as $c) {
                if (empty($c['id']) || empty($c['name'])) continue;
                $primary = null;
                foreach ($c['contacts'] ?? [] as $ct) {
                    if ($ct['isPrimary'] ?? false) { $primary = $ct; break; }
                }
                if (!$primary && !empty($c['contacts'])) $primary = $c['contacts'][0];
                // Resolve logo path relative to quotation folder
                $rawLogo = trim($c['logo'] ?? '');
                $logoUrl = '';
                if ($rawLogo) {
                    // Could be stored as 'proposal_logos/foo.png' or '../quotation/proposal_logos/foo.png'
                    $clean = ltrim(str_replace('../quotation/', '', $rawLogo), '/');
                    $logoUrl = $clean; // relative to api.php in /portal/quotation/
                }
                $result[] = [
                    'id'           => $c['id'],
                    'company_name' => trim($c['name']),
                    'country'      => trim($c['country'] ?? ''),
                    'contact_name' => $primary ? trim($primary['name']  ?? '') : '',
                    'email'        => $primary ? trim($primary['email'] ?? '') : '',
                    'logo'         => $logoUrl,
                ];
            }
            usort($result, fn($a,$b) => strcasecmp($a['company_name'], $b['company_name']));
            echo json_encode(['ok'=>true,'clients'=>$result]);
            break;

        case 'next_quote_num':
            $year = (int)date('Y');
            $s = $db->prepare("SELECT COALESCE(MAX(base_seq),0) FROM quotes WHERE year=:y");
            $s->execute([':y'=>$year]);
            $maxSeq  = (int)$s->fetchColumn();
            $nextSeq = max($maxSeq + 1, QUOTE_SEQ_FLOOR + 1);
            echo json_encode(['ok'=>true,'quote_number'=>'INTL'.$year.'-'.$nextSeq,'seq'=>$nextSeq,'year'=>$year]);
            break;

        case 'list_quotes':
            $rows = $db->query(
                "SELECT id, quote_number, base_seq, revision, client_name, country,
                        quote_date, currency, updated_at
                 FROM quotes ORDER BY year DESC, base_seq DESC, revision DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['id']       = (int)$r['id'];
                $r['base_seq'] = (int)$r['base_seq'];
                $r['revision'] = (int)$r['revision'];
            }
            echo json_encode(['ok'=>true,'quotes'=>$rows]);
            break;

        case 'load_quote':
            $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
            if (!$id) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
            $stmt = $db->prepare("SELECT * FROM quotes WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['ok'=>false,'error'=>'Quote not found']); break; }
            $row['id']           = (int)$row['id'];
            $row['base_seq']     = (int)$row['base_seq'];
            $row['revision']     = (int)$row['revision'];
            $row['divisor']      = (float)$row['divisor'];
            $row['discount_pct'] = (float)$row['discount_pct'];
            $decoded = $row['sections_json'] ? json_decode($row['sections_json'], true) : null;
            $row['sections_json'] = $decoded;
            // Hoist extras stored inside sections_json back to top-level
            if ($decoded) {
                if (!empty($decoded['divisors']))            $row['divisors']            = $decoded['divisors'];
                if (!empty($decoded['ship_estimates']))      $row['ship_estimates']      = $decoded['ship_estimates'];
                if (isset($decoded['install_train_amt']))    $row['install_train_amt']   = $decoded['install_train_amt'];
                if (!empty($decoded['install_train_label'])) $row['install_train_label'] = $decoded['install_train_label'];
                if (!empty($decoded['payment_wire']))         $row['payment_wire']        = $decoded['payment_wire'];
                if (isset($decoded['payment_include_lc']))   $row['payment_include_lc']  = $decoded['payment_include_lc'];
                if (isset($decoded['payment_lc_terms']))     $row['payment_lc_terms']    = $decoded['payment_lc_terms'];
            }
            echo json_encode(['ok'=>true,'quote'=>$row]);
            break;

        case 'save_quote':
            $year = (int)date('Y');
            $s    = $db->prepare("SELECT COALESCE(MAX(base_seq),0) FROM quotes WHERE year=:y");
            $s->execute([':y'=>$year]);
            $maxSeq  = (int)$s->fetchColumn();
            $newSeq  = max($maxSeq + 1, QUOTE_SEQ_FLOOR + 1);
            $qnum    = 'INTL'.$year.'-'.$newSeq;
            $pay = tiNormalizePaymentTerms($body);
            $sectJ   = json_encode([
                'sections'        => ($body['sections']         ?? []),
                'divisors'        => ($body['divisors']         ?? []),
                'ship_estimates'  => ($body['ship_estimates']   ?? []),
                'install_train_amt'   => $body['install_train_amt']   ?? 0,
                'install_train_label' => $body['install_train_label'] ?? '',
                'payment_wire'        => $pay['wire'],
                'payment_include_lc'  => $pay['include_lc'],
                'payment_lc_terms'    => $pay['lc_terms'],
            ]);
            $stmt = $db->prepare(
                "INSERT INTO quotes (seq_num,year,base_seq,revision,quote_number,
                     client_id,client_name,country,quote_date,currency,
                     incoterm,inco_location,divisor,discount_pct,sections_json)
                 VALUES (:sn,:yr,:bs,:rv,:qn,:ci,:cn,:co,:qd,:cu,:it,:il,:dv,:dp,:sj)"
            );
            $stmt->execute(buildQuoteParams($newSeq,$year,$newSeq,0,$qnum,$body,$sectJ));
            $newId = (int)$db->lastInsertId();
            echo json_encode(['ok'=>true,'id'=>$newId,'quote_number'=>$qnum]);
            break;

        case 'update_quote':
            $id = (int)($body['id'] ?? 0);
            if (!$id) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
            $qnum = $db->query("SELECT quote_number FROM quotes WHERE id=$id")->fetchColumn();
            if (!$qnum) { echo json_encode(['ok'=>false,'error'=>'Not found']); break; }
            $pay = tiNormalizePaymentTerms($body);
            $sectJ = json_encode([
                'sections'        => ($body['sections']         ?? []),
                'divisors'        => ($body['divisors']         ?? []),
                'ship_estimates'  => ($body['ship_estimates']   ?? []),
                'install_train_amt'   => $body['install_train_amt']   ?? 0,
                'install_train_label' => $body['install_train_label'] ?? '',
                'payment_wire'        => $pay['wire'],
                'payment_include_lc'  => $pay['include_lc'],
                'payment_lc_terms'    => $pay['lc_terms'],
            ]);
            $stmt = $db->prepare(
                "UPDATE quotes SET client_id=:ci, client_name=:cn, country=:co,
                    quote_date=:qd, currency=:cu, incoterm=:it,
                    inco_location=:il, divisor=:dv, discount_pct=:dp, sections_json=:sj
                 WHERE id=:id"
            );
            $p = buildQuoteParams(0,0,0,0,'',$body,$sectJ);
            $p[':id'] = $id;
            unset($p[':sn'],$p[':yr'],$p[':bs'],$p[':rv'],$p[':qn']);
            $stmt->execute($p);
            echo json_encode(['ok'=>true,'id'=>$id,'quote_number'=>$qnum]);
            break;

        case 'save_revision':
            $baseId = (int)($body['base_id'] ?? 0);
            if (!$baseId) { echo json_encode(['ok'=>false,'error'=>'Missing base_id']); break; }
            $base = $db->query("SELECT year,base_seq FROM quotes WHERE id=$baseId")->fetch(PDO::FETCH_ASSOC);
            if (!$base) { echo json_encode(['ok'=>false,'error'=>'Base quote not found']); break; }
            $bYear = (int)$base['year']; $bSeq = (int)$base['base_seq'];
            $maxRev= (int)$db->query("SELECT COALESCE(MAX(revision),0) FROM quotes WHERE base_seq=$bSeq AND year=$bYear")->fetchColumn();
            $newRev= $maxRev + 1;
            $qnum  = 'INTL'.$bYear.'-'.$bSeq.'-R'.$newRev;
            $pay = tiNormalizePaymentTerms($body);
            $sectJ = json_encode([
                'sections'        => ($body['sections']         ?? []),
                'divisors'        => ($body['divisors']         ?? []),
                'ship_estimates'  => ($body['ship_estimates']   ?? []),
                'install_train_amt'   => $body['install_train_amt']   ?? 0,
                'install_train_label' => $body['install_train_label'] ?? '',
                'payment_wire'        => $pay['wire'],
                'payment_include_lc'  => $pay['include_lc'],
                'payment_lc_terms'    => $pay['lc_terms'],
            ]);
            $stmt = $db->prepare(
                "INSERT INTO quotes (seq_num,year,base_seq,revision,quote_number,
                     client_id,client_name,country,quote_date,currency,
                     incoterm,inco_location,divisor,discount_pct,sections_json)
                 VALUES (:sn,:yr,:bs,:rv,:qn,:ci,:cn,:co,:qd,:cu,:it,:il,:dv,:dp,:sj)"
            );
            $stmt->execute(buildQuoteParams($bSeq,$bYear,$bSeq,$newRev,$qnum,$body,$sectJ));
            $newId = (int)$db->lastInsertId();
            echo json_encode(['ok'=>true,'id'=>$newId,'quote_number'=>$qnum,'revision'=>$newRev]);
            break;

        case 'delete_quote':
            $id = (int)($body['id'] ?? 0);
            if (!$id) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
            $db->prepare("DELETE FROM quotes WHERE id=:id")->execute([':id'=>$id]);
            echo json_encode(['ok'=>true]);
            break;

        case 'export_csv':
            $client  = $body['client_name'] ?? 'Client';
            $qnum    = preg_replace('/[^A-Za-z0-9\-_]/','', $body['quote_num'] ?? 'QUOTE');
            $date    = $body['date']     ?? date('Y-m-d');
            $cur     = $body['currency'] ?? 'USD';
            $csvInco = trim((string)($body['incoterm'] ?? ''));
            $csvIsExw = tiIsExWorks($csvInco);
            $divisor = max(0.01,(float)($body['divisor'] ?? 0.65));
            $disc    = min(1,max(0,(float)($body['discount_pct']??0)/100));
            $secs    = $body['sections'] ?? [];
            $allIds  = [];
            foreach ($secs as $s) foreach ($s['items'] as $it) $allIds[] = $it['model_id'];
            $allIds  = array_values(array_unique($allIds));
            if (empty($allIds)) { echo json_encode(['ok'=>false,'error'=>'No items']); break; }
            $ph   = implode(',',array_fill(0,count($allIds),'?'));
            $stmt = $db->prepare("SELECT model_id,title_only,intl_dist_net,intl_market_price_note,mfr_lead_time FROM products WHERE model_id IN ($ph)");
            $stmt->execute($allIds);
            $dbP  = []; foreach ($stmt->fetchAll() as $p) $dbP[$p['model_id']] = $p;
            $csv=[]; $grand=0;
            $csv[]=['"TECHNOLOGIES INTERNATIONAL LLC"','','','','','',''];
            $csv[]=['"Authorized MENA Representative"','','','','','',''];
            $csv[]=['','','','','','',''];
            $csv[]=['"CLIENT NAME:"','"'.$client.'"','','"COUNTRY:"','"'.($body['country']??'').'"','','"QUOTATION NUMBER: '.$qnum.'"'];
            $csv[]=['"DATE:"','"'.$date.'"','','"INCOTERMS:"','"'.trim(($body['incoterm']??'').' '.($body['inco_location']??'')).'"','',''];
            $csv[]=['','','','','','',''];
            $csv[]=['"Package Number"','"Item Number"','"Model Number"','"Description"','"QTY"','"Unit Price ('.$cur.')"','"Total Price ('.$cur.')"'];
            foreach ($secs as $si=>$sec) {
                $pkgNum=$si+1;
                $csv[]=['"=== SECTION '.$pkgNum.': '.addslashes($sec['name']??'').'"','','','','','',''];
                $mi=0;$sc2=[];
                foreach ($sec['items'] as &$it) {
                    $isMain=empty($it['isSubOf']);
                    if($isMain){$mi++;$sc2[$mi]=0;$it['_mi']=$mi;$itemNum=$mi;}
                    else{$pi=0;foreach($sec['items'] as $p2){if($p2['model_id']===$it['isSubOf']&&isset($p2['_mi'])){$pi=$p2['_mi'];break;}}if(!$pi)$pi=$mi;$sc2[$pi]=($sc2[$pi]??0)+1;$itemNum=$pi.'.'.$sc2[$pi];}
                    $p=$dbP[$it['model_id']]??null;
                    $net=($p&&$p['intl_dist_net']!==null)?(float)$p['intl_dist_net']:(isset($it['intl_dist_net'])?(float)$it['intl_dist_net']:null);
                    $sell=$net?round($net*(1-$disc)/$divisor,2):null;
                    $qty=max(1,(int)($it['qty']??1));
                    $tot=$sell?round($sell*$qty,2):null;
                    $isIncl=!$isMain&&($it['subPricing']??'included')==='included';
                    if($tot&&!$isIncl)$grand+=$tot;
                    $desc=addslashes($it['title_only']??($p['title_only']??''));
                    $csv[]=[$pkgNum,$itemNum,'"'.($it['model_id']).'"','"'.$desc.'"',$qty,
                        $isIncl?'"Included"':($sell?$sell:'"See Amatrol"'),
                        $isIncl?'"Included"':($tot?$tot:'"TBD"')];
                }
                $stot=0;
                foreach ($sec['items'] as $it){
                    if(!empty($it['isSubOf'])&&($it['subPricing']??'included')==='included')continue;
                    $net2=($dbP[$it['model_id']]['intl_dist_net']??null)??(float)($it['intl_dist_net']??0);
                    if($net2)$stot+=round((float)$net2*(1-$disc)/$divisor,2)*max(1,(int)$it['qty']);
                }
                $csv[]=['','','"TOTAL QUOTATION '.strtoupper($sec['name']??'').':"','','','','"'.number_format($stot,2).'"'];
                if(!$csvIsExw){
                    $csv[]=['','','"ESTIMATED SHIPPING QUOTE (VALUE MAY CHANGE DUE TO FORCE MAJEURE):"','','','','"TBD"'];
                }
                $csv[]=['','','','','','',''];
            }
            $csv[]=['','',' ','"GRAND TOTAL:"','','','"'.number_format($grand,2).' '.$cur.'"'];
            $out=''; foreach($csv as $row) $out.=implode(',',$row)."\r\n";
            echo json_encode(['ok'=>true,'csv'=>"\xEF\xBB\xBF".$out,'filename'=>$qnum.'.csv']);
            break;

        case 'export_xlsx':
            // NOTE: server memory_limit is already 2048M — do NOT lower it here
            set_time_limit(300);

            $client  = $body['client_name'] ?? 'Client';
            $country = $body['country']     ?? '';
            $qnum    = preg_replace('/[^A-Za-z0-9\-_]/','', $body['quote_num'] ?? 'QUOTE');
            $date    = $body['date']        ?? date('Y-m-d');
            $cur     = $body['currency']    ?? 'USD';
            $incoRaw = trim((string)($body['incoterm']??''));
            $inco    = trim($incoRaw.' '.($body['inco_location']??''));
            $isExw   = tiIsExWorks($incoRaw !== '' ? $incoRaw : $inco);
            $payTerms = tiNormalizePaymentTerms($body);
            $divisor = max(0.01,(float)($body['divisor']??0.65));
            $disc    = min(1,max(0,(float)($body['discount_pct']??0)/100));
            // Build per-item divisor map (mirrors ProposalBuilder _lineDiv: divisorKey → value, inherits from parent)
            $divisors_arr = $body['divisors'] ?? [];
            $divMap = [];
            foreach($divisors_arr as $dv){ if(!empty($dv['key'])) $divMap[$dv['key']] = max(0.001,(float)($dv['value']??$divisor)); }
            if(!isset($divMap['D1'])) $divMap['D1'] = $divisor;
            $getItemDiv = function(array $it, array $secItems) use ($divMap,$divisor,&$getItemDiv): float {
                $dk = $it['divisorKey'] ?? null;
                if($dk !== null && isset($divMap[$dk])) return $divMap[$dk];
                if(!empty($it['isSubOf'])){
                    foreach($secItems as $par){ if(($par['model_id']??null)===$it['isSubOf']) return $getItemDiv($par,$secItems); }
                }
                return $divMap['D1'] ?? $divisor;
            };
            $secs    = $body['sections']    ?? [];
            $ships         = tiShipEstimatesForExport($body['ship_estimates'] ?? [], $incoRaw !== '' ? $incoRaw : $inco);
            $installAmt    = (float)($body['install_train_amt'] ?? 0);
            $installLabel  = trim($body['install_train_label'] ?? 'INSTALLATION AND COMMISSIONING');

            $allIds=[];
            foreach($secs as $s) foreach($s['items'] as $it) $allIds[]=$it['model_id'];
            $allIds=array_values(array_unique(array_filter($allIds, fn($x)=>$x&&$x!=='LEGACY')));
            $dbP=[];
            if(!empty($allIds)){
                $pstmt=$db->prepare("SELECT model_id,title_only,COALESCE(title_description,'') as title_description,manufacturer,product_class,intl_dist_net,intl_market_price_note,mfr_lead_time FROM products WHERE model_id IN (".implode(',',array_fill(0,count($allIds),'?')).")");
                $pstmt->execute($allIds);
                foreach($pstmt->fetchAll() as $p) $dbP[$p['model_id']]=$p;
            }

            $R=[]; $M=[]; $H=[];
            $c=function($v,$s=0,$n=false,$f=null){return['v'=>$v,'s'=>$s,'n'=>$n,'f'=>$f];};
            $rn=0;
            $addRow=function(array $cells)use(&$R,&$rn){
                $rn++;$row=[];
                for($i=0;$i<7;$i++)$row[$i]=$cells[$i]??['v'=>'','s'=>0,'n'=>false,'f'=>null];
                $R[$rn]=$row;return $rn;
            };
            $mergeAG=function($n)use(&$M){$M[]='A'.$n.':G'.$n;};
            $mergeBF=function($n)use(&$M){$M[]='B'.$n.':F'.$n;};
            $mergeCF=function($n)use(&$M){$M[]='C'.$n.':F'.$n;};

            // 6 thin spacer rows (logo is now in print header, not sheet body)
            for($lr=1;$lr<=6;$lr++){ $n=$addRow([]); $H[$n]=3; }

            $n=$addRow([$c('TECHNICAL SPECIFICATIONS AND FINANCIAL OFFER',1),$c('',1),$c('',1),$c('',1),$c('',1),$c('',1),$c('',1)]);
            $mergeAG($n);$H[$n]=24;
            $n=$addRow([$c('Authorized MENA Representative - Amatrol - DAC Worldwide - Bayport Technical Education',2),$c('',2),$c('',2),$c('',2),$c('',2),$c('',2),$c('',2)]);
            $mergeAG($n);$H[$n]=15;
            $addRow([]);
            foreach([
                'CLIENT NAME: '.$client,
                'COUNTRY: '.$country,
                'QUOTATION NUMBER: '.$qnum,
                'DATE: '.$date,
                'INCOTERMS: '.($inco?:'EXW (Ex Works)'),
            ] as $hdr){
                $n=$addRow([$c($hdr,18),$c('',18),$c('',18),$c('',18),$c('',18),$c('',18),$c('',18)]);
                $mergeAG($n);$H[$n]=15;
            }
            $addRow([]);

            $n=$addRow([
                $c("Package Number\n\xD8\xB1\xD9\x82\xD9\x85 \xD8\xA7\xD9\x84\xD8\xAD\xD8\xB2\xD9\x85\xD8\xA9",3),
                $c("Item Number\n\xD8\xB1\xD9\x82\xD9\x85 \xD8\xA7\xD9\x84\xD8\xB5\xD9\x86\xD9\x81",3),
                $c("Model Number\n\xD8\xB1\xD9\x82\xD9\x85 \xD8\xA7\xD9\x84\xD9\x85\xD9\x88\xD8\xAF\xD9\x8A\xD9\x84",3),
                $c("Description\n\xD8\xA7\xD8\xB3\xD9\x85 \xD8\xA7\xD9\x84\xD8\xB5\xD9\x86\xD9\x81",3),
                $c("QTY\n\xD8\xA7\xD9\x84\xD9\x83\xD9\x85\xD9\x8A\xD8\xA9",3),
                $c("Unit Price (".$cur.")\n\xD8\xB3\xD8\xB9\xD8\xB1 \xD8\xA7\xD9\x84\xD9\x88\xD8\xAD\xD8\xAF\xD8\xA9",3),
                $c("Total Price (".$cur.")\n\xD8\xA7\xD9\x84\xD8\xB3\xD8\xB9\xD8\xB1 \xD8\xA7\xD9\x84\xD8\xA5\xD8\xAC\xD9\x85\xD8\xA7\xD9\x84\xD9\x8A",3),
            ]);$H[$n]=58;
            $colHdrRow = $n;

            $grand=0;
            $mi=0; $sc2=[]; // global item counter — continuous across all sections (matches PDF numbering)
            foreach($secs as $si=>$sec){
                $pkgNum=$si+1;
                $secName=$sec['name']??'Section '.$pkgNum;
                $secUpper=strtoupper(trim($secName));
                // Section 0: use actual name (matches PDF). Subsequent sections: "SECTION N: NAME"
                $secHeader = ($si===0) ? $secUpper : 'SECTION '.$si.': '.$secUpper;
                $n=$addRow([$c($secHeader,4),$c('',4),$c('',4),$c('',4),$c('',4),$c('',4),$c('',4)]);
                $mergeAG($n);$H[$n]=21;

                foreach($sec['items'] as &$it){
                    $isMain=empty($it['isSubOf']);
                    if($isMain){$mi++;$sc2[$mi]=0;$it['_mi']=$mi;$it['_ns']=(string)$mi;}
                    else{
                        $pi=0;
                        foreach($sec['items'] as $p2){if($p2['model_id']===$it['isSubOf']&&isset($p2['_mi'])){$pi=$p2['_mi'];break;}}
                        if(!$pi)$pi=$mi;
                        $sc2[$pi]=($sc2[$pi]??0)+1;
                        $it['_ns']=$pi.'.'.$sc2[$pi];$it['_pi']=$pi;
                    }
                    // Honor user-edited item numbers from the quote builder verbatim
                    if(!empty($it['custom_num'])){ $it['_ns']=(string)$it['custom_num']; $it['_custom']=true; }
                }unset($it);

                foreach($sec['items'] as &$it){
                    $p=$dbP[$it['model_id']]??null;
                    $net=($p&&$p['intl_dist_net']!==null)?(float)$p['intl_dist_net']:(isset($it['intl_dist_net'])?(float)$it['intl_dist_net']:null);
                    $sell=$net?round($net*(1-$disc)/$getItemDiv($it,$sec['items']),2):null;
                    $it['_sell']=$sell;$it['_net']=$net;
                    $it['_pc']=strtolower($p['product_class']??$it['product_class']??'');
                    $title=trim($p['title_only']??$it['title_only']??'');
                    $desc=trim($p['title_description']??'');
                    $it['_desc']=xmlSafe($title.($desc?"\n".$desc:''));
                }unset($it);

                foreach($sec['items'] as &$it){
                    if(!empty($it['isSubOf']))continue;
                    $bundle=$it['_sell']??0;
                    foreach($sec['items'] as $sub){
                        // Include ALL included sub-items (not just Learning Systems) in parent bundle price
                        if(($sub['isSubOf']??null)===$it['model_id']
                            &&($sub['_sell']??null)
                            &&($sub['subPricing']??'included')==='included'
                            &&empty($sub['isOptional'])){
                            $bundle+=($sub['_sell']??0)*max(1,(int)($sub['qty']??1));
                        }
                    }
                    $it['_bundle']=$bundle ?: ($it['_sell'] ?: null);
                }unset($it);

                $secDataStart=$rn+1; // first item row of this section (for SUM formula)
                foreach($sec['items'] as &$it){
                    $isMain=empty($it['isSubOf']);
                    $isOpt=!empty($it['isOptional']);
                    $p=$dbP[$it['model_id']]??null;
                    $qty=max(0,(int)($it['qty']??1));
                    $sell=$it['_sell']??null;
                    $bundle=$it['_bundle']??$sell;
                    $isIncl=!$isMain&&($it['subPricing']??'included')==='included';
                    $itemNumStr=(!empty($it['_custom']))?$it['_ns']:(($isOpt?'Optional-':'').$it['_ns']);
                    $parentMi=$it['_pi']??($isMain?$it['_mi']:0);
                    $desc=$it['_desc']??($it['title_only']??$it['model_id']);

                    if($isOpt){
                        $fCell=$sell!==null?$c(round($sell,2),8,true):$c('See Amatrol',17);
                    }elseif($isIncl&&!$isMain){
                        $fCell=$c("Included with\nItem ".(int)$parentMi,22);
                    }elseif($isMain){
                        $fCell=$bundle!==null?$c(round($bundle,2),8,true):$c('See Amatrol',17);
                    }else{
                        $fCell=$sell!==null?$c(round($sell,2),8,true):$c('See Amatrol',17);
                    }

                    $eQty=($isOpt)?0:$qty;
                    // Show empty qty cell if zero (cleaner than showing 0 for non-optionals)
                    $qtyCell = ($eQty === 0 && !$isOpt) ? $c('',0) : $c((string)$eQty,23,true);
                    $n=$addRow([
                        $c((string)$pkgNum,21),
                        $c($itemNumStr,21),
                        $c($it['model_id'],6),
                        $c($desc,7),
                        $qtyCell,
                        $fCell,
                        $c('',0),
                    ]);
                    $H[$n]=220;

                    if(!($isIncl&&!$isOpt)){
                        $cachedVal=($isOpt)?0:($isMain?round(($bundle??0)*$eQty,2):round(($sell??0)*$eQty,2));
                        $R[$n][6]=$c((string)$cachedVal,9,true,'E'.$n.'*F'.$n);
                        if(!$isOpt) $grand+=$cachedVal;
                    }
                }unset($it);

                $secDataEnd=$n; // last item row of this section
                $stot=0;
                foreach($sec['items'] as $it){
                    if(!empty($it['isOptional']))continue;
                    if(empty($it['isSubOf'])){
                        $b=$it['_bundle']??$it['_sell']??0;
                        if($b)$stot+=round($b*max(1,(int)$it['qty']),2);
                    }
                }
                $secTotLabel = 'TOTAL '.$secUpper.':'; // uses actual section name (matches PDF)
                $secSumF = 'SUM(G'.$secDataStart.':G'.$secDataEnd.')';
                $n=$addRow([$c('',0),$c($secTotLabel,12),$c('',12),$c('',12),$c('',12),$c('',12),$c($stot,10,true,$secSumF)]);
                $mergeBF($n);$H[$n]=15;
                $sectionItemSubtotalRows[$si]=$n;
                $sectionTotals[$si]=$stot; // cached value for summary row reference
                $addRow([]);
            }

            // ── QUOTATION TOTALS SUMMARY TABLE ────────────────────────────
            $addRow([]);
            // Header row — teal fill (style 25 = teal/white/bold/left), teal right cell (style 24)
            $n=$addRow([$c('QUOTATION TOTALS:',25),$c('',25),$c('',25),$c('',25),$c('',25),$c('',25),$c('TOTALS',24)]);
            $mergeAG($n); // merge A:F for label, G stands alone — but mergeAG covers A:G, so split manually
            // Actually use mergeBF for label so G is separate
            unset($M[array_key_last($M)]); // undo mergeAG
            $M[]='A'.$n.':F'.$n;           // merge A:F only
            $H[$n]=20;

            // One row per section — references the per-section subtotal rows tracked above
            $sectionSummaryRows=[];
            foreach($secs as $si=>$sec){
                $secUpper2=strtoupper($sec['name']??'Section '.($si+1));
                $lbl = ($si===0) ? $secUpper2 : 'SECTION '.$si.': '.$secUpper2;
                // Alternating: even = BG_LIGHT (style 29), odd = BG_ALT (style 30)
                $rowStyle = ($si%2===0) ? 29 : 30;
                $n=$addRow([$c($lbl,$rowStyle),$c('',$rowStyle),$c('',$rowStyle),$c('',$rowStyle),$c('',$rowStyle),$c('',$rowStyle),$c($sectionTotals[$si]??0,26,true,'G'.$sectionItemSubtotalRows[$si])]);
                $M[]='A'.$n.':F'.$n; $H[$n]=15;
                $sectionSummaryRows[]=$n;
            }

            // SUBTOTAL row — light-teal (style 27 label, style 26 value)
            $subtotalFormula=implode('+',array_map(fn($r)=>'G'.$r,$sectionSummaryRows));
            $subtotalCached=array_sum($sectionTotals);
            $n=$addRow([$c('SUBTOTAL:',27),$c('',27),$c('',27),$c('',27),$c('',27),$c('',27),$c($subtotalCached,26,true,$subtotalFormula)]);
            $subtotalRow=$n; $M[]='A'.$n.':F'.$n; $H[$n]=15;

            // ESTIMATED OCEAN FREIGHT — omitted entirely for EXW (Ex Works); never show TBD
            $freightRow=0;
            if(!$isExw){
                if(!empty($ships)){
                    foreach($ships as $ship){
                        $amt=(float)($ship['amt']??0);
                        $shipLabel=strtoupper(xmlSafe($ship['desc']??'ESTIMATED OCEAN FREIGHT'));
                        $n=$addRow([$c($shipLabel.':',12),$c('',12),$c('',12),$c('',12),$c('',12),$c('',12),$c($amt?round($amt,2):0,28,$amt>0)]);
                        $M[]='A'.$n.':F'.$n; $H[$n]=22;
                        if($freightRow===0) $freightRow=$n;
                    }
                } else {
                    $defaultFreightLabel='ESTIMATED OCEAN FREIGHT AS OF QUOTATION DATE (SUBJECT TO CHANGE DUE TO MARKET CONDITIONS, INCLUDING GEOPOLITICAL FACTORS, UNTIL BOOKING CONFIRMATION):';
                    $n=$addRow([$c($defaultFreightLabel,31),$c('',31),$c('',31),$c('',31),$c('',31),$c('',31),$c(0,28,true)]);
                    $M[]='A'.$n.':F'.$n; $H[$n]=55; $freightRow=$n;
                }
            }

            // INSTALLATION & COMMISSIONING — always shown as editable yellow cell
            $installRowNum=0;
            $installVal = ($installAmt>0) ? round($installAmt,2) : 0;
            $n=$addRow([$c(strtoupper($installLabel).':',12),$c('',12),$c('',12),$c('',12),$c('',12),$c('',12),$c($installVal,28,$installVal>0)]);
            $M[]='A'.$n.':F'.$n; $H[$n]=18; $installRowNum=$n;

            $addRow([]); // blank spacer

            // TOTAL QUOTATION row — teal fill (style 25/24), formula references live cells
            if($freightRow){
                $totalFormula='G'.$subtotalRow.'+IFERROR(G'.$freightRow.',0)+IFERROR(G'.$installRowNum.',0)';
            } else {
                $totalFormula='G'.$subtotalRow.'+IFERROR(G'.$installRowNum.',0)';
            }
            $totalCached=round($subtotalCached+$installAmt,2);
            $n=$addRow([$c('TOTAL QUOTATION (USD):',25),$c('',25),$c('',25),$c('',25),$c('',25),$c('',25),$c($totalCached,24,true,$totalFormula)]);
            $M[]='A'.$n.':F'.$n; $H[$n]=22;

            $addRow([]); // spacer before terms

            $cc=strtolower(trim($country));
            $voltageCopy=($cc==='saudi arabia'||$cc==='ksa'||$cc==='kingdom of saudi arabia'||$cc==='the kingdom of saudi arabia (ksa)')
                ?"**** ALL MATERIALS WILL BE IN AMERICAN ENGLISH UNLESS OTHERWISE SPECIFIED\nEQUIPMENT WILL BE SUPPLIED IN KINGDOM OF SAUDI ARABIA (KSA) REQUIRED VOLTAGES, 220 VOLT / SINGLE PHASE / 60HZ OR 380 VOLT / 3 PHASE / 60 HZ"
                :"**** ALL MATERIALS WILL BE IN AMERICAN ENGLISH UNLESS OTHERWISE SPECIFIED\nEQUIPMENT WILL BE SUPPLIED IN ".strtoupper($country?:'DESTINATION COUNTRY')." REQUIRED VOLTAGES, 220 VOLT / SINGLE PHASE / 50HZ OR 380 VOLT / 3 PHASE / 50 HZ";
            $n=$addRow([$c('',0),$c('',0),$c($voltageCopy,14),$c('',14),$c('',14),$c('',14),$c('',0)]);$mergeCF($n);$H[$n]=70;
            $n=$addRow([$c('',0),$c('',0),$c('SHIPPING WILL BE 100-150 DAYS FROM DATE OF PURCHASE.  INCREASED DELIVERY TIME IS DUE TO CURRENT LEAD TIME ON RAW MATERIALS AND MANUFACTURED MATERIALS AS CORE COMPONENTS OF OUR LEARNING SYSTEMS.',14),$c('',14),$c('',14),$c('',14),$c('',0)]);$mergeCF($n);$H[$n]=70;
            $shipTermsText = 'SHIPMENT TERMS: '.($inco?:'EXW (Ex Works)');
            if($isExw){
                $shipTermsText .= ' — Shipment terms are Ex Works. Estimated ocean freight is not included and is not listed on this quotation.';
            }
            $n=$addRow([$c('',0),$c('',0),$c($shipTermsText,14),$c('',14),$c('',14),$c('',14),$c('',0)]);$mergeCF($n);$H[$n]=$isExw?40:20;
            $n=$addRow([$c('',0),$c('',0),$c('INTERNATIONAL PAYMENT OPTIONS',14),$c('',14),$c('',14),$c('',14),$c('',0)]);$mergeCF($n);$H[$n]=20;
            $n=$addRow([$c('',0),$c('',0),$c('THESE TERMS ARE NON-NEGOTIABLE',13),$c('',13),$c('',13),$c('',13),$c('',0)]);$mergeCF($n);$H[$n]=20;
            $addRow([]);
            $wireText = tiWirePaymentText($payTerms['wire']);
            if($wireText !== ''){
                $n=$addRow([$c('',0),$c('',0),$c('',0),$c('Payment via Wire Transfer:',15),$c('',0),$c('',0),$c('',0)]);$H[$n]=15;
                $n=$addRow([$c('',0),$c('',0),$c('',0),$c($wireText,15),$c('',0),$c('',0),$c('',0)]);$H[$n]=28;
                $addRow([]);
                $n=$addRow([$c('',0),$c('',0),$c('',0),$c('Incoming International Wire Payment Instructions:',16),$c('',0),$c('',0),$c('',0)]);$H[$n]=15;
                foreach([
                    'Bank SWIFT Code: NRTHUS33',
                    'ABA# 026013673',
                    'For Further credit to: TD BANK NA, Wilmington DE',
                    'In Favor of Beneficiary Account Number: 4247908415',
                    'In Favor of Beneficiary: Technologies International, LLC',
                    '3149 Broadway, Suite 16, New York, NY 10027    Ph: 646.216.9043'
                ] as $w){
                    $n=$addRow([$c('',0),$c('',0),$c('',0),$c($w,16),$c('',0),$c('',0),$c('',0)]);
                    // Last two rows (beneficiary name + address) get extra height for readability
                    $H[$n]= (strpos($w,'Technologies International')!==false||strpos($w,'3149 Broadway')!==false) ? 22 : 15;
                }
            }
            if(!empty($payTerms['include_lc'])){
                $addRow([]);
                $n=$addRow([$c('',0),$c('',0),$c('',0),$c('Payment via Letter of Credit:',15),$c('',0),$c('',0),$c('',0)]);$H[$n]=15;
                $lcBody = $payTerms['lc_terms'] !== '' ? $payTerms['lc_terms'] : TI_DEFAULT_LC_TERMS;
                foreach(preg_split("/\r\n|\n|\r/", $lcBody) as $lcLine){
                    $n=$addRow([$c('',0),$c('',0),$c('',0),$c($lcLine === '' ? ' ' : $lcLine,15),$c('',0),$c('',0),$c('',0)]);
                    $H[$n]=18;
                }
            }
            $addRow([]);
            $n=$addRow([$c('Prices subject to change. Valid 30 days from '.$date.'. All amounts in '.$cur.'.',20),$c('',20),$c('',20),$c('',20),$c('',20),$c('',20),$c('',20)]);$mergeAG($n);

            $xlsx=buildXLSX($R,$M,$H,$colHdrRow,$qnum,$date);
            if($xlsx===false){ob_clean();echo json_encode(['ok'=>false,'error'=>'ZipArchive not available. Use CSV instead.']);break;}
            $safeClient=preg_replace('/[^A-Za-z0-9_\-]/','_',$client);
            ob_clean();
            echo json_encode(['ok'=>true,'xlsx'=>base64_encode($xlsx),'filename'=>$qnum.'_'.$safeClient.'.xlsx']);
            break;

        case 'import_projects':
            $projects = $body['projects'] ?? [];
            if(empty($projects)){ echo json_encode(['ok'=>false,'error'=>'No projects provided']); break; }
            $imported = 0; $skipped = 0;
            foreach($projects as $proj){
                $qnum = trim($proj['quoteNum'] ?? '');
                if(!$qnum){ $skipped++; continue; }
                $chk = $db->prepare("SELECT id FROM quotes WHERE quote_number=:q");
                $chk->execute([':q'=>$qnum]);
                if($chk->fetchColumn()){ $skipped++; continue; }
                preg_match('/INTL-?(\d{4})-(\d+)/i', $qnum, $m);
                $yr  = isset($m[1]) ? (int)$m[1] : (int)date('Y');
                $seq = isset($m[2]) ? (int)$m[2] : 0;
                $rev = 0;
                if(preg_match('/-R(\d+)$/i', $qnum, $rm)) $rev = (int)$rm[1];
                if(!empty($proj['sections']) && is_array($proj['sections'])){
                    $sectJ = json_encode(['sections'=> $proj['sections']]);
                } else {
                    $equip = trim($proj['equipment'] ?? '');
                    $sectJ = json_encode(['sections'=>[['id'=>'s1','name'=>'Equipment',
                         'items'=> $equip ? [['model_id'=>'LEGACY','title_only'=>$equip,'intl_dist_net'=>null,'qty'=>1,
                                               'isSubOf'=>null,'subPricing'=>'included','manufacturer'=>'','product_class'=>'',
                                               'key_topic'=>'','intl_market_price_note'=>null,'requires_models'=>[],'mfr_lead_time'=>'']] : []]]]);
                }
                $ins = $db->prepare("INSERT INTO quotes (seq_num,year,base_seq,revision,quote_number,client_name,country,quote_date,currency,incoterm,sections_json) VALUES (:sn,:yr,:bs,:rv,:qn,:cn,:co,:qd,:cu,:it,:sj)");
                $ins->execute([':sn'=>$seq,':yr'=>$yr,':bs'=>$seq,':rv'=>$rev,':qn'=>$qnum,
                    ':cn'=>trim($proj['clientName']??''),':co'=>trim($proj['country']??''),
                    ':qd'=>$proj['date']??date('Y-m-d'),':cu'=>$proj['currency']??'USD',
                    ':it'=>trim($proj['incoterm']??''),':sj'=>$sectJ]);
                foreach($proj['revisions']??[] as $rdata){
                    $rNum=$rdata['rNum']??1; $rqnum=preg_replace('/-R\d+$/i','',$qnum).'-R'.$rNum;
                    $chk2=$db->prepare("SELECT id FROM quotes WHERE quote_number=:q");
                    $chk2->execute([':q'=>$rqnum]);
                    if($chk2->fetchColumn()) continue;
                    $ins2=$db->prepare("INSERT INTO quotes (seq_num,year,base_seq,revision,quote_number,client_name,country,quote_date,currency,sections_json) VALUES (:sn,:yr,:bs,:rv,:qn,:cn,:co,:qd,:cu,:sj)");
                    $ins2->execute([':sn'=>$seq,':yr'=>$yr,':bs'=>$seq,':rv'=>$rNum,':qn'=>$rqnum,
                        ':cn'=>trim($proj['clientName']??''),':co'=>trim($proj['country']??''),
                        ':qd'=>$proj['date']??date('Y-m-d'),':cu'=>$proj['currency']??'USD',':sj'=>$sectJ]);
                }
                $imported++;
            }
            echo json_encode(['ok'=>true,'imported'=>$imported,'skipped'=>$skipped]);
            break;

        case 'get_document':
            $mid = trim($_GET['mid'] ?? '');
            if(!$mid){ http_response_code(400); echo 'Missing mid'; exit; }
            $s = $db->prepare("SELECT combined_pdf FROM product_documents WHERE model_id=:m LIMIT 1");
            $s->execute([':m'=>$mid]);
            $row = $s->fetch();
            if(!$row){ http_response_code(404); echo 'No document for: '.htmlspecialchars($mid); exit; }
            $file = __DIR__ . '/' . ltrim($row['combined_pdf'],'/');
            if(!file_exists($file)){ http_response_code(404); echo 'File not found'; exit; }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.basename($file).'"');
            header('Content-Length: '.filesize($file));
            header('Cache-Control: public, max-age=86400');
            ob_clean(); readfile($file); exit;

        case 'check_documents':
            $ids = $body['ids'] ?? [];
            if(empty($ids)){ echo json_encode(['ok'=>true,'has_doc'=>[]]); break; }
            $ph  = implode(',', array_fill(0,count($ids),'?'));
            $s   = $db->prepare("SELECT model_id FROM product_documents WHERE model_id IN ($ph)");
            $s->execute($ids);
            $found = $s->fetchAll(PDO::FETCH_COLUMN);
            $foundSet = array_flip($found);
            foreach($ids as $chkId){
                if(isset($foundSet[$chkId])) continue;
                $safe = preg_replace('/[^a-zA-Z0-9_\-]/','', $chkId);
                $diskPath = __DIR__.'/product_docs/'.$safe.'.pdf';
                if($safe && file_exists($diskPath)){
                    try{
                        $ins=$db->prepare("INSERT IGNORE INTO product_documents (model_id,combined_pdf) VALUES(?,?)");
                        $ins->execute([$chkId,'product_docs/'.$safe.'.pdf']);
                    }catch(\Throwable $e){}
                    $found[] = $chkId;
                }
            }
            ob_clean();
            echo json_encode(['ok'=>true,'has_doc'=>array_values($found)]);
            break;

        case 'doc_for_model':
            $mid = trim($_GET['mid'] ?? '');
            if(!$mid){ echo json_encode(['ok'=>false]); break; }
            $s = $db->prepare("SELECT combined_pdf FROM product_documents WHERE model_id=:m LIMIT 1");
            $s->execute([':m'=>$mid]);
            $row = $s->fetch();
            ob_clean();
            echo json_encode($row ? ['ok'=>true,'url'=>'api.php?action=get_document&mid='.urlencode($mid)] : ['ok'=>false]);
            break;

        case 'export_proposal_pdf':
            // NOTE: server memory_limit is already 2048M — do NOT lower it here.
            // A previous version set it to 512M which caused silent crashes on large proposals.
            set_time_limit(600);

            $pbLevel = ob_get_level();
            ob_start();
            try {
                require_once __DIR__ . '/ProposalBuilder.php';
            } catch (Throwable $reqErr) {
                while(ob_get_level() > $pbLevel) ob_end_clean();
                ob_clean();
                echo json_encode(['ok'=>false,'error'=>'ProposalBuilder load error: '.$reqErr->getMessage()]);
                break;
            }
            while(ob_get_level() > $pbLevel + 1) ob_end_clean();
            if(ob_get_level() > $pbLevel) ob_end_clean();

            $pClient  = $body['client_name']  ?? 'Client';
            $pCountry = $body['country']      ?? '';
            $pQnum    = preg_replace('/[^A-Za-z0-9\-_]/', '', $body['quote_num'] ?? 'QUOTE');
            $pDate    = $body['date']         ?? date('Y-m-d');
            $pSecs    = $body['sections']     ?? [];
            $pDivisor = max(0.01, (float)($body['divisor']      ?? 0.65));
            $pDisc    = min(100,  max(0, (float)($body['discount_pct'] ?? 0)));
            $pIncotermBase = trim((string)($body['incoterm'] ?? ''));
            $pIncoterm     = trim($pIncotermBase.' '.trim((string)($body['inco_location'] ?? '')));
            $pPayment      = tiNormalizePaymentTerms($body);
            $pShips        = tiShipEstimatesForExport($body['ship_estimates'] ?? [], $pIncotermBase !== '' ? $pIncotermBase : $pIncoterm);
            $pInstallAmt   = (float)($body['install_train_amt'] ?? 0);
            $pInstallLabel = trim($body['install_train_label'] ?? 'INSTALLATION AND COMMISSIONING');
            $pLogoPath = '';
            $logoRel   = trim($body['client_logo'] ?? '');
            if ($logoRel) {
                $candidate = __DIR__ . '/' . ltrim(preg_replace('/\.\./', '', $logoRel), '/');
                if (file_exists($candidate)) $pLogoPath = $candidate;
            }

            $pDivisors = is_array($body['divisors'] ?? null) ? $body['divisors'] : [];

            $innerLevel = ob_get_level();
            ob_start();
            $pdfBytes = false;
            try {
                // Resolve RFQ document path
                $rfqDocPath = '';
                $rfqRel = trim($body['rfq_doc_path'] ?? '');
                if($rfqRel){
                    $rfqCandidate = __DIR__ . '/' . ltrim(preg_replace('/\.\./', '', $rfqRel), '/');
                    if(file_exists($rfqCandidate)) $rfqDocPath = $rfqCandidate;
                }
                // Resolve Required Tender Document paths
                $tenderDocPaths = [];
                $tenderRels = $body['tender_doc_paths'] ?? null;
                if(is_array($tenderRels)){
                    foreach($tenderRels as $tRel){
                        $tRel = trim((string)$tRel);
                        if(!$tRel) continue;
                        $tCand = __DIR__ . '/' . ltrim(preg_replace('/\.\./', '', $tRel), '/');
                        if(file_exists($tCand)) $tenderDocPaths[] = $tCand;
                    }
                }
                // Fallback: if the client didn't send attachment paths, pull persisted ones by quote number
                if(($tenderRels === null || $rfqRel === '') && $pQnum){
                    try{
                        ensureAttachmentsTable($db);
                        $ast = $db->prepare("SELECT kind, path FROM quote_attachments WHERE quote_num=:q ORDER BY id ASC");
                        $ast->execute([':q'=>$pQnum]);
                        foreach($ast->fetchAll(PDO::FETCH_ASSOC) as $arow){
                            $aCand = __DIR__ . '/' . ltrim($arow['path'],'/');
                            if(!file_exists($aCand)) continue;
                            if($arow['kind']==='rfq' && $rfqRel==='' && !$rfqDocPath) $rfqDocPath = $aCand;
                            if($arow['kind']==='tender' && $tenderRels === null) $tenderDocPaths[] = $aCand;
                        }
                    }catch(Throwable $attErr){}
                }
                $pdfBytes = buildProposalPDF($pSecs,$pClient,$pCountry,$pQnum,$pDate,$pLogoPath,$pDivisor,$pDisc,$pShips,$pInstallAmt,$pInstallLabel,$pDivisors,$rfqDocPath, $body['pdf_mode'] ?? 'combined', $tenderDocPaths, $pIncoterm, $pPayment);
            } catch (Throwable $pdfErr) {
                while(ob_get_level() > $innerLevel) ob_end_clean();
                ob_clean();
                echo json_encode(['ok'=>false,'error'=>'PDF build error: '.$pdfErr->getMessage()]);
                break;
            }
            while(ob_get_level() > $innerLevel + 1) ob_end_clean();
            if(ob_get_level() > $innerLevel) ob_end_clean();

            if (!$pdfBytes) {
                ob_clean();
                echo json_encode(['ok'=>false,'error'=>'PDF generation returned empty']);
                break;
            }
            $safeClient = preg_replace('/[^A-Za-z0-9_\-]/','_', $pClient);
            $modeSuffix = ($body['pdf_mode']??'combined')==='commercial'?'_Commercial':(($body['pdf_mode']??'')==='literature'?'_Literature':'_Proposal');
            ob_clean();
            echo json_encode(['ok'=>true,'pdf'=>base64_encode($pdfBytes),'filename'=>$pQnum.'_'.$safeClient.$modeSuffix.'.pdf']);
            break;

        case 'upload_proposal_logo':
            $logoDir = __DIR__ . '/proposal_logos/';
            if(!is_dir($logoDir)) mkdir($logoDir, 0755, true);
            if(empty($_FILES['logo'])) { echo json_encode(['ok'=>false,'error'=>'No file']); break; }
            $f = $_FILES['logo'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if(!in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
                echo json_encode(['ok'=>false,'error'=>'Only PNG/JPG/GIF/WEBP allowed']); break;
            }
            $cid = preg_replace('/[^A-Za-z0-9_\-]/', '', $body['client_id'] ?? ('logo_'.time()));
            $dest = $logoDir . $cid . '.' . $ext;
            if(!move_uploaded_file($f['tmp_name'], $dest)) {
                echo json_encode(['ok'=>false,'error'=>'Upload failed']); break;
            }
            ob_clean();
            echo json_encode(['ok'=>true,'path'=>'proposal_logos/'.$cid.'.'.$ext,'filename'=>basename($dest)]);
            break;

        case 'list_proposal_logos':
            $logoDir = __DIR__ . '/proposal_logos/';
            $logos = [];
            if (is_dir($logoDir)) {
                foreach (scandir($logoDir) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    if (!preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $f)) continue;
                    $logos[] = ['filename'=>$f, 'path'=>'proposal_logos/'.$f];
                }
                usort($logos, function($a,$b){ return strcmp($a['filename'],$b['filename']); });
            }
            ob_clean();
            echo json_encode(['ok'=>true,'logos'=>$logos]);
            break;

        case 'reprocess_docs':
            ob_clean();
            $docDir = __DIR__ . '/product_docs/';
            $files = glob($docDir . '*.pdf') ?: [];
            $converted = 0; $skipped = 0; $failedFiles = [];
            foreach ($files as $fpath) {
                try {
                    $test = new \setasign\Fpdi\Fpdi();
                    $test->setSourceFile($fpath);
                    $test->importPage(1);
                    $skipped++;
                } catch (\Throwable $e) {
                    $before = md5_file($fpath);
                    _ensureFpdiCompatible($fpath);
                    if (md5_file($fpath) !== $before) {
                        $converted++;
                    } else {
                        $failedFiles[] = basename($fpath);
                    }
                }
            }
            echo json_encode(['ok'=>true,'total'=>count($files),'converted'=>$converted,'skipped'=>$skipped,'failed'=>count($failedFiles),'failed_files'=>$failedFiles]);
            break;

        case 'upload_rfq_doc':
            // Upload a PDF to be appended to the proposal (RFQ Comparison or Required Tender Documents).
            // Persists per quotation number in quote_attachments so it survives across sessions.
            ob_clean();
            $rfqDir = __DIR__ . '/rfq_docs/';
            if(!is_dir($rfqDir)) mkdir($rfqDir,0755,true);
            $f = $_FILES['rfq_doc'] ?? null;
            if(!$f || $f['error']!==UPLOAD_ERR_OK){ echo json_encode(['ok'=>false,'error'=>'No file or upload error']); break; }
            $ext = strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            if($ext !== 'pdf'){ echo json_encode(['ok'=>false,'error'=>'Only PDF files accepted']); break; }
            $kind  = (($_POST['kind'] ?? 'rfq') === 'tender') ? 'tender' : 'rfq';
            $aQnum = trim($_POST['quote_num'] ?? '');
            $origName = preg_replace('/[^\w .()\-]/u','_', $f['name']);
            $fname = $kind.'_'.time().'_'.substr(md5($f['name'].microtime()),0,6).'.pdf';
            $dest = $rfqDir.$fname;
            if(!move_uploaded_file($f['tmp_name'], $dest)){ echo json_encode(['ok'=>false,'error'=>'Upload failed']); break; }
            $newId = 0;
            if($aQnum !== ''){
                ensureAttachmentsTable($db);
                if($kind === 'rfq'){
                    // Only one RFQ comparison doc per quote — replace any existing one
                    $old = $db->prepare("SELECT id, path FROM quote_attachments WHERE quote_num=:q AND kind='rfq'");
                    $old->execute([':q'=>$aQnum]);
                    foreach($old->fetchAll(PDO::FETCH_ASSOC) as $orow){
                        $op = __DIR__.'/'.ltrim($orow['path'],'/');
                        if(strpos(realpath(dirname($op))?:'', realpath($rfqDir)?:'#')===0 && file_exists($op)) @unlink($op);
                        $db->prepare("DELETE FROM quote_attachments WHERE id=:i")->execute([':i'=>$orow['id']]);
                    }
                }
                $db->prepare("INSERT INTO quote_attachments (quote_num, kind, path, filename) VALUES (:q,:k,:p,:f)")
                   ->execute([':q'=>$aQnum, ':k'=>$kind, ':p'=>'rfq_docs/'.$fname, ':f'=>$origName]);
                $newId = (int)$db->lastInsertId();
            }
            echo json_encode(['ok'=>true,'path'=>'rfq_docs/'.$fname,'filename'=>$origName,'id'=>$newId]);
            break;

        case 'list_quote_attachments':
            ob_clean();
            $aQnum = trim($_GET['quote_num'] ?? $body['quote_num'] ?? '');
            if($aQnum === ''){ echo json_encode(['ok'=>true,'attachments'=>[]]); break; }
            ensureAttachmentsTable($db);
            $st = $db->prepare("SELECT id, kind, path, filename FROM quote_attachments WHERE quote_num=:q ORDER BY kind ASC, id ASC");
            $st->execute([':q'=>$aQnum]);
            $rows = [];
            foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){
                // Skip records whose file vanished from disk
                if(!file_exists(__DIR__.'/'.ltrim($r['path'],'/'))) continue;
                $rows[] = ['id'=>(int)$r['id'],'kind'=>$r['kind'],'path'=>$r['path'],'filename'=>$r['filename']];
            }
            echo json_encode(['ok'=>true,'attachments'=>$rows]);
            break;

        case 'delete_quote_attachment':
            ob_clean();
            $aid = (int)($body['id'] ?? 0);
            if(!$aid){ echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
            ensureAttachmentsTable($db);
            $st = $db->prepare("SELECT path FROM quote_attachments WHERE id=:i");
            $st->execute([':i'=>$aid]);
            if($row = $st->fetch(PDO::FETCH_ASSOC)){
                $op = __DIR__.'/'.ltrim($row['path'],'/');
                $rfqReal = realpath(__DIR__.'/rfq_docs/');
                if($rfqReal && strpos(realpath(dirname($op))?:'', $rfqReal)===0 && file_exists($op)) @unlink($op);
                $db->prepare("DELETE FROM quote_attachments WHERE id=:i")->execute([':i'=>$aid]);
            }
            echo json_encode(['ok'=>true]);
            break;

        // ── SYSTEM BUNDLES ──────────────────────────────────────────────
        case 'list_bundles':
            $db = getDB(); ensureQuotesTable($db);
            $rows = $db->query("SELECT id, name, description, items_json, updated_at FROM system_bundles ORDER BY name ASC")->fetchAll();
            foreach ($rows as &$r) { $r['items'] = json_decode($r['items_json'], true) ?: []; unset($r['items_json']); }
            ob_clean();
            echo json_encode(['ok'=>true,'bundles'=>$rows]);
            break;

        case 'save_bundle':
            $db = getDB(); ensureQuotesTable($db);
            $bname  = trim($body['name'] ?? '');
            $bdesc  = trim($body['description'] ?? '');
            $bitems = $body['items'] ?? [];
            if (!$bname) { ob_clean(); echo json_encode(['ok'=>false,'error'=>'Bundle name required']); break; }
            if (!is_array($bitems) || count($bitems) === 0) { ob_clean(); echo json_encode(['ok'=>false,'error'=>'Bundle must have at least one item']); break; }
            $bitemsJson = json_encode($bitems);
            if (isset($body['id']) && $body['id']) {
                $stmt = $db->prepare("UPDATE system_bundles SET name=:n, description=:d, items_json=:ij WHERE id=:id");
                $stmt->execute([':n'=>$bname,':d'=>$bdesc,':ij'=>$bitemsJson,':id'=>(int)$body['id']]);
                ob_clean(); echo json_encode(['ok'=>true,'id'=>(int)$body['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO system_bundles (name, description, items_json) VALUES (:n,:d,:ij)");
                $stmt->execute([':n'=>$bname,':d'=>$bdesc,':ij'=>$bitemsJson]);
                ob_clean(); echo json_encode(['ok'=>true,'id'=>(int)$db->lastInsertId()]);
            }
            break;

        case 'delete_bundle':
            $db = getDB(); ensureQuotesTable($db);
            $bid = (int)($body['id'] ?? 0);
            if (!$bid) { ob_clean(); echo json_encode(['ok'=>false,'error'=>'ID required']); break; }
            $db->prepare("DELETE FROM system_bundles WHERE id=:id")->execute([':id'=>$bid]);
            ob_clean(); echo json_encode(['ok'=>true]);
            break;

        case 'get_bundle_products':
            $db = getDB(); ensureQuotesTable($db);
            $bmids = array_values(array_filter(array_unique(array_map('trim', (array)($body['model_ids'] ?? [])))));
            if (!$bmids) { ob_clean(); echo json_encode(['ok'=>true,'products'=>[]]); break; }
            $bph = implode(',', array_fill(0, count($bmids), '?'));
            $bstmt = $db->prepare("SELECT model_id, title_only, title_description, intl_dist_net, manufacturer, requires_models, recommended_models FROM products WHERE model_id IN ($bph)");
            $bstmt->execute($bmids);
            $bfound = [];
            foreach ($bstmt->fetchAll() as $brow) {
                $brow['intl_dist_net']      = $brow['intl_dist_net'] !== null ? (float)$brow['intl_dist_net'] : null;
                $brow['title']              = $brow['title_only'] ?? '';
                $brow['description']        = $brow['title_description'] ?? '';
                $brow['requires_models']    = $brow['requires_models'] ? json_decode($brow['requires_models'], true) : [];
                $brow['recommended_models'] = $brow['recommended_models'] ? json_decode($brow['recommended_models'], true) : [];
                $bfound[$brow['model_id']] = $brow;
            }
            ob_clean();
            echo json_encode(['ok'=>true,'products'=>$bfound]);
            break;
        // ── END SYSTEM BUNDLES ────────────────────────────────────────────────

        // ── VENDOR IMPORT — proxy to vendor_import.php ──────────────────────
        // Handles parse_vendor_doc, import_to_catalog, import_to_quote, import_both, list_vendor_configs
        case 'parse_vendor_doc':
        case 'import_to_catalog':
        case 'import_to_quote':
        case 'import_both':
        case 'list_vendor_configs':
            ob_clean();
            $viFile = __DIR__ . '/vendor_import.php';
            if (!file_exists($viFile)) {
                echo json_encode(['ok'=>false,'error'=>'vendor_import.php not found in ' . __DIR__]);
                break;
            }
            // Pass through: include the file which reads its own $_GET/$_POST/$_FILES
            include $viFile;
            break;

        // ── SAVE CUSTOM ITEM NUMBERS for a quote ─────────────────────────
        // Lets the user re-number items in the proposal (e.g. map spec items 1,3,14 → 1,2,3)
        case 'save_item_numbers':
            ob_clean();
            $quoteId  = (int)($body['quote_id'] ?? 0);
            $numMap   = $body['item_numbers'] ?? []; // {lid|model_id => display_number}
            if (!$quoteId) { echo json_encode(['ok'=>false,'error'=>'Missing quote_id']); break; }
            // Load existing sections_json
            $sq = $db->prepare("SELECT sections_json FROM quotes WHERE id=:id");
            $sq->execute([':id'=>$quoteId]);
            $qrow = $sq->fetch(PDO::FETCH_ASSOC);
            if (!$qrow) { echo json_encode(['ok'=>false,'error'=>'Quote not found']); break; }
            $sj = json_decode($qrow['sections_json'], true) ?? [];
            // Apply custom numbers to items
            foreach ($sj['sections'] as &$sec) {
                foreach ($sec['items'] as &$item) {
                    $lid = $item['lid'] ?? $item['model_id'] ?? null;
                    if ($lid && isset($numMap[$lid])) {
                        $item['custom_num'] = $numMap[$lid];
                    } elseif (isset($item['model_id']) && isset($numMap[$item['model_id']])) {
                        $item['custom_num'] = $numMap[$item['model_id']];
                    }
                }
            }
            unset($sec, $item);
            $db->prepare("UPDATE quotes SET sections_json=:sj WHERE id=:id")
               ->execute([':sj'=>json_encode($sj), ':id'=>$quoteId]);
            echo json_encode(['ok'=>true]);
            break;

        default:
            echo json_encode(['ok'=>false,'error'=>'Unknown action: '.htmlspecialchars($action)]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['ok'=>false,'error'=>'DB error: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

function ensureAttachmentsTable(PDO $db): void {
    static $checkedAtt = false;
    if ($checkedAtt) return; $checkedAtt = true;
    $db->exec("CREATE TABLE IF NOT EXISTS quote_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_num VARCHAR(64) NOT NULL,
        kind ENUM('rfq','tender') NOT NULL DEFAULT 'tender',
        path VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_qnum (quote_num)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureQuotesTable(PDO $db): void {
    static $checked = false;
    if ($checked) return; $checked = true;
    $db->exec("CREATE TABLE IF NOT EXISTS quotes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        seq_num INT UNSIGNED NOT NULL, year SMALLINT UNSIGNED NOT NULL,
        base_seq INT UNSIGNED NOT NULL, revision TINYINT UNSIGNED NOT NULL DEFAULT 0,
        quote_number VARCHAR(60) NOT NULL, client_id VARCHAR(100) DEFAULT NULL,
        client_name VARCHAR(200) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL,
        quote_date DATE, currency VARCHAR(10) DEFAULT 'USD',
        incoterm VARCHAR(150) DEFAULT NULL, inco_location VARCHAR(150) DEFAULT NULL,
        divisor DECIMAL(7,4) NOT NULL DEFAULT 0.6500,
        discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        sections_json LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_base_year (base_seq, year), INDEX idx_year_seq (year, seq_num)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Ensure product_documents exists so check_documents never throws
    $db->exec("CREATE TABLE IF NOT EXISTS product_documents (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        model_id VARCHAR(50) NOT NULL,
        combined_pdf VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_mid (model_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // System bundles: pre-configured sets of items that can be dropped into a quote in one click
    $db->exec("CREATE TABLE IF NOT EXISTS system_bundles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT DEFAULT NULL,
        items_json LONGTEXT NOT NULL COMMENT 'JSON array of {model_id, qty, isSubOf, subPricing, isOptional}',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function _ensureFpdiCompatible(string $pdfPath): void {
    // Test if FPDI can already read this PDF
    try {
        $test = new \setasign\Fpdi\Fpdi();
        $test->setSourceFile($pdfPath);
        $test->importPage(1);
        return; // Already compatible
    } catch (\Throwable $e) {}

    // ── Strategy 1: proc_open + GhostScript ──────────────────────────────────
    // This works on Hostinger even though exec() is disabled, because proc_open
    // is NOT in the disable_functions list. GhostScript binary is at /usr/bin/gs.
    if (function_exists('proc_open')) {
        $tmpOut = tempnam(sys_get_temp_dir(), 'fpdi14_') . '.pdf';
        foreach (['/usr/bin/gs', '/usr/local/bin/gs', 'gs'] as $gs) {
            $cmd = $gs
                . ' -dBATCH -dNOPAUSE -dSAFER -dQUIET'
                . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4'
                . ' -sOutputFile=' . escapeshellarg($tmpOut)
                . ' ' . escapeshellarg($pdfPath);
            $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
            $proc = @proc_open($cmd, $desc, $pipes);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                stream_get_contents($pipes[1]); fclose($pipes[1]);
                stream_get_contents($pipes[2]); fclose($pipes[2]);
                $ret = proc_close($proc);
                if ($ret === 0 && file_exists($tmpOut) && filesize($tmpOut) > 1000) {
                    rename($tmpOut, $pdfPath);
                    return; // Successfully converted
                }
            }
            @unlink($tmpOut);
        }
    }

    // ── Strategy 2: Imagick (may be blocked by policy.xml on shared hosting) ──
    if (!extension_loaded('imagick')) return;
    try {
        $tmpDir = sys_get_temp_dir() . '/mpdf_cnv_' . md5($pdfPath);
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4',
            'margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,
            'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir]);
        $tmpImgs=[]; $added=0;
        $all = new \Imagick();
        $all->setResolution(150,150);
        $all->setBackgroundColor('white');
        $all->readImage($pdfPath);
        $pgCount = $all->getNumberImages();
        for($i=0;$i<$pgCount;$i++){
            try{
                $all->setIteratorIndex($i);
                $pg=$all->getImage();
                $pg->setImageFormat('png');
                $pg->setBackgroundColor('white');
                $pg->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $tmp=tempnam(sys_get_temp_dir(),'cnvpg_').'.png';
                $pg->writeImage($tmp); $tmpImgs[]=$tmp;
                $sz=$pg->getImageGeometry(); $pg->destroy();
                $wMm=max(10,$sz['width']/150*25.4); $hMm=max(10,$sz['height']/150*25.4);
                if($added>0) $mpdf->AddPage($wMm>$hMm?'L':'P',[$wMm,$hMm]);
                $mpdf->Image($tmp,0,0,$wMm,$hMm,'png'); $added++;
            }catch(\Throwable $pe){ continue; }
        }
        $all->destroy();
        if($added>0){
            $out=$mpdf->Output('','S');
            if($out&&strlen($out)>500) file_put_contents($pdfPath,$out);
        }
        foreach($tmpImgs as $t) @unlink($t);
        array_map('unlink',glob($tmpDir.'/*')?:[]);
        @rmdir($tmpDir);
    } catch (\Throwable $e) {}
}

function buildQuoteParams(int $sn, int $yr, int $bs, int $rv, string $qn, array $body, string $sectJ): array {
    return [
        ':sn'=>$sn, ':yr'=>$yr, ':bs'=>$bs, ':rv'=>$rv, ':qn'=>$qn,
        ':ci'=>$body['client_id'] ?: null,
        ':cn'=>trim($body['client_name']   ?? ''),
        ':co'=>trim($body['country']       ?? ''),
        ':qd'=>$body['quote_date']    ?? date('Y-m-d'),
        ':cu'=>$body['currency']      ?? 'USD',
        ':it'=>$body['incoterm']      ?? '',
        ':il'=>$body['inco_location'] ?? '',
        ':dv'=>max(0.01,(float)($body['divisor']      ?? 0.65)),
        ':dp'=>min(100,max(0,(float)($body['discount_pct'] ?? 0))),
        ':sj'=>$sectJ,
    ];
}

function xmlSafe(string $s): string {
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
}

function buildXLSX(array $rows, array $merges=[], array $rowHeights=[], int $colHdrRow=10, string $quoteNum='', string $reportDate=''): string|false {
    if (!class_exists('ZipArchive')) return false;

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="3">
    <numFmt numFmtId="49"  formatCode="@"/>
    <numFmt numFmtId="164" formatCode="$#,##0.00"/>
    <numFmt numFmtId="165" formatCode="$#,##0.00;[Red]-$#,##0.00"/>
  </numFmts>
  <fonts count="12">
    <font><sz val="10"/><name val="Calibri"/><color rgb="FF0D2137"/></font>
    <font><sz val="18"/><b/><name val="Calibri"/><color rgb="FF0D2137"/></font>
    <font><sz val="11"/><name val="Calibri"/><color rgb="FF0D2137"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
    <font><sz val="11"/><b/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/><color rgb="FF0D2137"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/><color rgb="FF1A6FA0"/></font>
    <font><sz val="10"/><name val="Calibri"/><color rgb="FF0D2137"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/><color rgb="FFC00000"/></font>
    <font><sz val="10"/><b/><name val="Calibri"/><color rgb="FF000000"/></font>
    <font><sz val="9"/><i/><name val="Calibri"/><color rgb="FF4A6A82"/></font>
    <font><sz val="9"/><b/><name val="Calibri"/><color rgb="FF0D2137"/></font>
  </fonts>
  <fills count="11">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0D2137"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFEEF4F9"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A6FA0"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFCCE3F0"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2B6CA3"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0DB8A8"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFCCF0EC"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF8DC"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF4FBFE"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFC8DDE8"/></left>
      <right style="thin"><color rgb="FFC8DDE8"/></right>
      <top style="thin"><color rgb="FFC8DDE8"/></top>
      <bottom style="thin"><color rgb="FFC8DDE8"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs>
    <xf numFmtId="0"   fontId="0"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="0"   fontId="1"  fillId="0" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0"   fontId="2"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3"  fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="4"  fillId="6" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="49"  fontId="5"  fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="0"   fontId="6"  fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="0"   fontId="7"  fillId="0" borderId="1" xfId="0"><alignment horizontal="left"   vertical="top" wrapText="1"/></xf>
    <xf numFmtId="164" fontId="0"  fillId="0" borderId="1" xfId="0"><alignment horizontal="right"  vertical="top"/></xf>
    <xf numFmtId="164" fontId="5"  fillId="0" borderId="1" xfId="0"><alignment horizontal="right"  vertical="top"/></xf>
    <xf numFmtId="164" fontId="5"  fillId="3" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5"  fillId="3" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5"  fillId="0" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="8"  fillId="2" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="0"   fontId="3"  fillId="2" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="9"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="top" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="9"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="top" wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="10" fillId="0" borderId="0" xfId="0"><alignment horizontal="right"  vertical="top"   wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="5"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="0"   fontId="0"  fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="0"   fontId="10" fillId="0" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center" wrapText="1"/></xf>
    <xf numFmtId="49"  fontId="5"  fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="0"   fontId="11" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"   wrapText="1"/></xf>
    <xf numFmtId="0"   fontId="0"  fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="top"/></xf>
    <xf numFmtId="164" fontId="4"  fillId="7" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="4"  fillId="7" borderId="0" xfId="0"><alignment horizontal="left"   vertical="center"/></xf>
    <xf numFmtId="164" fontId="5"  fillId="8" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5"  fillId="8" borderId="0" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="164" fontId="0"  fillId="9" borderId="1" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="164" fontId="5"  fillId="3" borderId="1" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="164" fontId="5"  fillId="10" borderId="1" xfId="0"><alignment horizontal="right"  vertical="center"/></xf>
    <xf numFmtId="0"   fontId="5"  fillId="0"  borderId="0" xfId="0"><alignment horizontal="left"   vertical="center" wrapText="1"/></xf>
  </cellXfs>
</styleSheet>';

    $strings=[]; $strIdx=[]; $strCount=0;
    foreach($rows as $row){
        foreach($row as $cell){
            $v=xmlSafe((string)($cell['v']??''));
            $n=$cell['n']??false;
            if(!$n && $v!==''){
                $strCount++;
                if(!isset($strIdx[$v])){ $strIdx[$v]=count($strings); $strings[]=$v; }
            }
        }
    }
    $ssXml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
          .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$strCount.'" uniqueCount="'.count($strings).'">';
    foreach($strings as $s) $ssXml.='<si><t xml:space="preserve">'.htmlspecialchars($s,ENT_XML1,'UTF-8').'</t></si>';
    $ssXml.='</sst>';

    $cols=['A','B','C','D','E','F','G'];
    $sheetXml ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sheetXml.='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
              .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

    $sheetXml.='<sheetFormatPr defaultRowHeight="15"/>';
    $sheetXml.='<cols>';
    $sheetXml.='<col min="1" max="1" width="8.0"  customWidth="1"/>';
    $sheetXml.='<col min="2" max="2" width="9.0"  customWidth="1"/>';
    $sheetXml.='<col min="3" max="3" width="14.0" customWidth="1"/>';
    $sheetXml.='<col min="4" max="4" width="48.0" customWidth="1"/>';
    $sheetXml.='<col min="5" max="5" width="6.0"  customWidth="1"/>';
    $sheetXml.='<col min="6" max="6" width="13.0" customWidth="1"/>';
    $sheetXml.='<col min="7" max="7" width="13.0" customWidth="1"/>';
    $sheetXml.='</cols><sheetData>';

    $rowNums=array_keys($rows); sort($rowNums);
    foreach($rowNums as $rowNum){
        $row=$rows[$rowNum];
        $ht=isset($rowHeights[$rowNum])?' ht="'.$rowHeights[$rowNum].'" customHeight="1"':'';
        $sheetXml.='<row r="'.$rowNum.'"'.$ht.'>';
        for($ci=0;$ci<7;$ci++){
            $cell=$row[$ci]??['v'=>'','s'=>0,'n'=>false,'f'=>null];
            $v=xmlSafe((string)($cell['v']??''));
            $xf=(int)($cell['s']??0);
            $num=$cell['n']??false;
            $formula=$cell['f']??null;
            $ref=$cols[$ci].$rowNum;
            if($formula!==null && is_string($formula)){
                $cached=is_numeric($v)?$v:'0';
                $sheetXml.='<c r="'.$ref.'" s="'.$xf.'"><f>'.htmlspecialchars($formula,ENT_XML1,'UTF-8').'</f><v>'.$cached.'</v></c>';
            }elseif($v===''){
                $sheetXml.='<c r="'.$ref.'" s="'.$xf.'"/>';
            }elseif($num&&is_numeric($v)){
                $sheetXml.='<c r="'.$ref.'" s="'.$xf.'"><v>'.$v.'</v></c>';
            }else{
                $idx=$strIdx[$v]??null;
                if($idx===null){ $idx=count($strings); $strIdx[$v]=$idx; $strings[]=$v; }
                $sheetXml.='<c r="'.$ref.'" s="'.$xf.'" t="s"><v>'.$idx.'</v></c>';
            }
        }
        $sheetXml.='</row>';
    }
    $sheetXml.='</sheetData>';

    if(!empty($merges)){
        $sheetXml.='<mergeCells count="'.count($merges).'">';
        foreach($merges as $m) $sheetXml.='<mergeCell ref="'.$m.'"/>';
        $sheetXml.='</mergeCells>';
    }


    // HEADER/FOOTER: only escape & < > (ENT_XML1 breaks font names like &"Calibri,Bold")
    $hdrE = fn(string $s) => str_replace(['&','<','>'], ['&amp;','&lt;','&gt;'], $s);
    // Header: left = TI/Kitmeer branding, center = quotation number
    $headerXml = '&L&"Calibri,Bold"&10Technologies International LLC / Kitmeer'
               . '&C&"Calibri,Bold"&10'.$quoteNum;
    // Footer date in M/D/YYYY format
    $footerDate = $reportDate ? date('n/j/Y', strtotime($reportDate)) : date('n/j/Y');
    // Footer: left = date, center = 3-line address (no page numbers)
    $footerXml = '&L&"Calibri"&8'.$footerDate
               . '&C&"Calibri"&08'
               . 'North America: 3149 Broadway, STE 16, New York, NY 10027 USA  Ph: +1 646.216.9043'."\n"
               . 'Middle East: P.O. Box 500211, Dubai Internet City, UAE  Ph: +971.4391.0970'."\n"
               . 'This offer expires in 60 days unless otherwise specified in writing.';
    $sheetXml.='<printOptions headings="0" gridLines="0"/>';
    $sheetXml.='<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.85" header="0.3" footer="0.5"/>';
    $sheetXml.='<pageSetup orientation="portrait" fitToWidth="1" fitToHeight="0" paperSize="9"/>';
    $sheetXml.='<headerFooter>';
    $sheetXml.='<oddHeader>'.$hdrE($headerXml).'</oddHeader>';
    $sheetXml.='<oddFooter>'.$hdrE($footerXml).'</oddFooter>';
    $sheetXml.='</headerFooter>';

    $logoData = false;
    $logoUrl  = 'https://ti-kitmeer.com/images/Proposal%20Combined.png';
    $ctx = stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);
    $logoRaw = @file_get_contents($logoUrl, false, $ctx);
    if($logoRaw && strlen($logoRaw) > 100) $logoData = $logoRaw;
    $hasLogo = ($logoData !== false);

    $sheetXml.='</worksheet>';

    $titleRowsXml='<definedNames>'.
        '<definedName name="_xlnm.Print_Titles">\'Quotation\'!$'.$colHdrRow.':$'.$colHdrRow.'</definedName>'.
        '<definedName name="_xlnm.Print_Area">\'Quotation\'!$A$1:$G$1048576</definedName>'.
        '</definedNames>';

    $relsMain='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
             .'</Relationships>';

    $sheetRels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
              .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

    $sheetRels.='</Relationships>';

    $relsWb='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
           .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
           .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
           .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
           .'</Relationships>';

    $workbook='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             .'<sheets><sheet name="Quotation" sheetId="1" r:id="rId1"/></sheets>'
             .$titleRowsXml
             .'<calcPr calcId="0" fullCalcOnLoad="1"/>'
             .'</workbook>';

    $ct='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
       .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
       .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
       .'<Default Extension="xml"  ContentType="application/xml"/>'
       .'<Override PartName="/xl/workbook.xml"          ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
       .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
       .'<Override PartName="/xl/sharedStrings.xml"     ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
       .'<Override PartName="/xl/styles.xml"            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    if($hasLogo){

        $ct.='<Default Extension="png" ContentType="image/png"/>';
    }
    $ct.='</Types>';

    // VML drawing for header logo — &G in oddHeader references this
    $vmlXml=''; $vmlRels='';
    if($hasLogo){
        $vmlXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<xml xmlns:v="urn:schemas-microsoft-com:vml"'."\n"
            .'     xmlns:o="urn:schemas-microsoft-com:office:office"'."\n"
            .'     xmlns:x="urn:schemas-microsoft-com:office:excel"'."\n"
            .'     xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'."\n"
            .'<o:shapelayout v:ext="edit"><o:idmap v:ext="edit" data="1"/></o:shapelayout>'."\n"
            .'<v:shapetype id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t"'."\n"
            .'  path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f">'."\n"
            .'  <v:stroke joinstyle="miter"/>'."\n"
            .'  <v:formulas>'."\n"
            .'    <v:f eqn="if lineDrawn pixelLineWidth 0"/><v:f eqn="sum @0 1 0"/>'."\n"
            .'    <v:f eqn="sum 0 0 @1"/><v:f eqn="prod @2 1 2"/>'."\n"
            .'    <v:f eqn="prod @3 21600 pixelWidth"/><v:f eqn="prod @3 21600 pixelHeight"/>'."\n"
            .'    <v:f eqn="sum @0 0 1"/><v:f eqn="prod @6 1 2"/>'."\n"
            .'    <v:f eqn="prod @7 21600 pixelWidth"/><v:f eqn="prod @7 21600 pixelHeight"/>'."\n"
            .'  </v:formulas>'."\n"
            .'  <v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"/>'."\n"
            .'  <o:lock v:ext="edit" aspectratio="t"/>'."\n"
            .'</v:shapetype>'."\n"
            .'<v:shape id="_x0000_s1025" type="#_x0000_t75"'."\n"
            .'  style="position:absolute;margin-left:0;margin-top:0;width:180pt;height:55pt;z-index:1">'."\n"
            .'  <v:imagedata r:id="rId1" o:title="TI-Kitmeer Logo"/>'."\n"
            .'  <o:lock v:ext="edit" rotation="t"/>'."\n"
            .'  <x:ClientData ObjectType="Pict">'."\n"
            .'    <x:CF>Pict</x:CF><x:Lock/><x:Row>0</x:Row><x:Col>0</x:Col>'."\n"
            .'    <x:ObjType>Left</x:ObjType>'."\n"  
            .'  </x:ClientData>'."\n"
            .'</v:shape>'."\n"
            .'</xml>';
        $vmlRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/>'
            .'</Relationships>';
    }

    $tmp=tempnam(sys_get_temp_dir(),'xlsx_');
    if(!$tmp)return false;
    $zip=new ZipArchive();
    if($zip->open($tmp,ZipArchive::OVERWRITE)!==true){unlink($tmp);return false;}
    $zip->addFromString('[Content_Types].xml',           $ct);
    $zip->addFromString('_rels/.rels',                   $relsMain);
    $zip->addFromString('xl/workbook.xml',               $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels',    $relsWb);
    $zip->addFromString('xl/worksheets/sheet1.xml',      $sheetXml);
    $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRels);
    $zip->addFromString('xl/sharedStrings.xml',          $ssXml);
    $zip->addFromString('xl/styles.xml',                 $stylesXml);
    if($hasLogo){
        $zip->addFromString('xl/media/logo.png', $logoData);
    }
    $zip->close();
    $data=file_get_contents($tmp); unlink($tmp);
    return $data?:false;
}
