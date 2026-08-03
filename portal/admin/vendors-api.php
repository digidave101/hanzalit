<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ADMINS = ['dhanzal' => 'aA292199', 'fdegheidy' => 'Winner#1'];
$BASE   = realpath(dirname(__DIR__));
$VFILE  = $BASE . '/vendors.json';
$POFILE = $BASE . '/purchase-orders.json';

function resp($d)     { echo json_encode($d); exit; }
function hs($s)       { return htmlspecialchars(trim((string)($s ?? '')), ENT_QUOTES, 'UTF-8'); }
function loadJ($f)    { return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : []; }
function saveJ($f,$d) { file_put_contents($f, json_encode(array_values($d), JSON_PRETTY_PRINT)); }

$in   = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($in)) $in = $_REQUEST;
$user   = $in['user'] ?? '';
$pass   = $in['pass'] ?? '';
$authed = isset($ADMINS[$user]) && $ADMINS[$user] === $pass;
$action = $in['action'] ?? $_GET['action'] ?? '';

switch ($action) {

  // ── LIST ──────────────────────────────────────────────────────────
  case 'list_vendors':
    $vendors = loadJ($VFILE);
    $type    = trim($in['type']   ?? '');
    $search  = strtolower(trim($in['search'] ?? ''));
    $status  = trim($in['status'] ?? '');
    if ($type)   $vendors = array_filter($vendors, fn($v) => ($v['type']   ?? '') === $type);
    if ($status) $vendors = array_filter($vendors, fn($v) => ($v['status'] ?? '') === $status);
    if ($search) $vendors = array_filter($vendors, fn($v) =>
        str_contains(strtolower($v['name']        ?? ''), $search) ||
        str_contains(strtolower($v['country']     ?? ''), $search) ||
        str_contains(strtolower($v['code']        ?? ''), $search) ||
        str_contains(strtolower($v['contactName'] ?? ''), $search) ||
        str_contains(strtolower($v['contactEmail']?? ''), $search));
    usort($vendors, fn($a,$b) => strcmp($a['name']??'', $b['name']??''));
    resp(['ok' => true, 'vendors' => array_values($vendors)]);

  // ── GET SINGLE ────────────────────────────────────────────────────
  case 'get_vendor':
    $id = $in['id'] ?? $_GET['id'] ?? '';
    $vendors = loadJ($VFILE);
    $found = array_values(array_filter($vendors, fn($v) => $v['id'] === $id));
    if (!$found) resp(['ok' => false, 'error' => 'Vendor not found']);
    resp(['ok' => true, 'vendor' => $found[0]]);

  // ── SAVE (create or update) ────────────────────────────────────────
  case 'save_vendor':
    if (!$authed) resp(['ok' => false, 'error' => 'Unauthorized']);
    $vendors = loadJ($VFILE);
    $id      = trim($in['id'] ?? '');

    if (!$id) {
      // New vendor — generate ID and code
      $id  = 'vnd-' . uniqid();
      $yr  = date('Y');
      $cnt = count(array_filter($vendors, fn($v) => str_starts_with($v['code'] ?? '', "VND-{$yr}-")));
      $code = "VND-{$yr}-" . str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);
      $vendor = ['id' => $id, 'code' => $code, 'createdAt' => date('Y-m-d H:i:s')];
    } else {
      $idx = array_search($id, array_column($vendors, 'id'));
      $vendor = $idx !== false ? $vendors[$idx] : ['id' => $id, 'code' => $in['code'] ?? '', 'createdAt' => date('Y-m-d H:i:s')];
    }

    $vendor = array_merge($vendor, [
      'name'         => hs($in['name']         ?? ''),
      'type'         => hs($in['type']         ?? 'equipment_supplier'),
      'contactName'  => hs($in['contactName']  ?? ''),
      'contactTitle' => hs($in['contactTitle'] ?? ''),
      'contactEmail' => hs($in['contactEmail'] ?? ''),
      'contactPhone' => hs($in['contactPhone'] ?? ''),
      'address'      => hs($in['address']      ?? ''),
      'city'         => hs($in['city']         ?? ''),
      'country'      => hs($in['country']      ?? ''),
      'currency'     => hs($in['currency']     ?? 'USD'),
      'leadTimeDays' => strlen($in['leadTimeDays'] ?? '') ? intval($in['leadTimeDays']) : null,
      'paymentTerms' => hs($in['paymentTerms'] ?? ''),
      'status'       => hs($in['status']       ?? 'active'),
      'website'      => hs($in['website']      ?? ''),
      'notes'        => hs($in['notes']        ?? ''),
      'updatedAt'    => date('Y-m-d H:i:s'),
    ]);

    $idx = array_search($vendor['id'], array_column($vendors, 'id'));
    if ($idx !== false) $vendors[$idx] = $vendor;
    else $vendors[] = $vendor;

    saveJ($VFILE, $vendors);
    resp(['ok' => true, 'vendor' => $vendor]);

  // ── DELETE ────────────────────────────────────────────────────────
  case 'delete_vendor':
    if (!$authed) resp(['ok' => false, 'error' => 'Unauthorized']);
    $id = $in['id'] ?? '';
    if (!$id) resp(['ok' => false, 'error' => 'Missing id']);
    // Safety check: referenced in POs?
    $pos = loadJ($POFILE);
    foreach ($pos as $po) {
      if (($po['vendorId'] ?? '') === $id)
        resp(['ok' => false, 'error' => 'This vendor is referenced in one or more purchase orders. Remove those references first.']);
    }
    $vendors = array_filter(loadJ($VFILE), fn($v) => $v['id'] !== $id);
    saveJ($VFILE, $vendors);
    resp(['ok' => true]);

  default:
    resp(['ok' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
}
