<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ADMINS = ['dhanzal' => 'aA292199', 'fdegheidy' => 'Winner#1'];
$BASE   = realpath(dirname(__DIR__)); // = public_html/portal

function san($s)  { return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($s))); }
function resp($d) { echo json_encode($d); exit; }

// Auth
$user = $_POST['user'] ?? $_GET['user'] ?? '';
$pass = $_POST['pass'] ?? $_GET['pass'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// List projects does not require auth (projects.html handles it client-side)
// but we still verify for write operations
$authed = isset($ADMINS[$user]) && $ADMINS[$user] === $pass;

$PROJ_FILE    = $BASE . '/projects.json';
$CLIENTS_FILE = $BASE . '/clients-db.json';

function loadProjects($f) {
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : [];
}
function saveProjects($f, $data) {
    file_put_contents($f, json_encode(array_values($data), JSON_PRETTY_PRINT));
}
function loadClients($f) {
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : [];
}

switch ($action) {

    case 'list_projects':
        $projects = loadProjects($PROJ_FILE);
        resp(['ok' => true, 'projects' => $projects]);

    case 'list_clients':
        $clients = loadClients($CLIENTS_FILE);
        resp(['ok' => true, 'clients' => $clients]);

    case 'add_project':
        if (!$authed) resp(['ok'=>false,'error'=>'Unauthorized']);
        $projects = loadProjects($PROJ_FILE);
        $revs = [];
        $rawRevs = json_decode($_POST['revisions'] ?? '[]', true);
        if (is_array($rawRevs)) $revs = $rawRevs;
        $p = [
            'id'         => uniqid('proj_'),
            'quoteNum'   => trim($_POST['quoteNum'] ?? ''),
            'date'       => trim($_POST['date'] ?? ''),
            'status'     => trim($_POST['status'] ?? 'Active'),
            'clientName' => trim($_POST['clientName'] ?? ''),
            'country'    => trim($_POST['country'] ?? ''),
            'equipment'  => trim($_POST['equipment'] ?? ''),
            'notes'      => trim($_POST['notes'] ?? ''),
            'r1NetCost'  => trim($_POST['r1NetCost'] ?? ''),
            'currency'   => trim($_POST['currency'] ?? 'USD'),
            'r1File'     => null,
            'revisions'  => $revs,
            'created'    => date('Y-m-d H:i:s'),
        ];
        $projects[] = $p;
        saveProjects($PROJ_FILE, $projects);
        resp(['ok' => true, 'project' => $p]);

    case 'update_project':
        if (!$authed) resp(['ok'=>false,'error'=>'Unauthorized']);
        $projects = loadProjects($PROJ_FILE);
        $id = trim($_POST['id'] ?? '');
        $idx = null;
        foreach ($projects as $i => $proj) { if ($proj['id'] === $id) { $idx = $i; break; } }
        if ($idx === null) resp(['ok'=>false,'error'=>'Not found']);
        $revs = [];
        $rawRevs = json_decode($_POST['revisions'] ?? '[]', true);
        if (is_array($rawRevs)) $revs = $rawRevs;
        // Preserve existing files
        $existing = $projects[$idx];
        // Merge revision files
        foreach ($revs as &$r) {
            foreach ($existing['revisions'] ?? [] as $er) {
                if ($er['rNum'] == $r['rNum'] && !empty($er['file'])) {
                    $r['file'] = $er['file'];
                    break;
                }
            }
        }
        $projects[$idx] = array_merge($existing, [
            'quoteNum'   => trim($_POST['quoteNum'] ?? $existing['quoteNum']),
            'date'       => trim($_POST['date'] ?? $existing['date']),
            'status'     => trim($_POST['status'] ?? $existing['status']),
            'clientName' => trim($_POST['clientName'] ?? $existing['clientName']),
            'country'    => trim($_POST['country'] ?? $existing['country']),
            'equipment'  => trim($_POST['equipment'] ?? $existing['equipment']),
            'notes'      => trim($_POST['notes'] ?? $existing['notes']),
            'r1NetCost'  => trim($_POST['r1NetCost'] ?? $existing['r1NetCost']),
            'currency'   => trim($_POST['currency'] ?? $existing['currency']),
            'revisions'  => $revs,
            'updated'    => date('Y-m-d H:i:s'),
        ]);
        saveProjects($PROJ_FILE, $projects);
        resp(['ok' => true, 'project' => $projects[$idx]]);

    case 'delete_project':
        if (!$authed) resp(['ok'=>false,'error'=>'Unauthorized']);
        $projects = loadProjects($PROJ_FILE);
        $id = trim($_POST['id'] ?? '');
        $projects = array_filter($projects, function($p) use ($id){ return $p['id'] !== $id; });
        saveProjects($PROJ_FILE, array_values($projects));
        resp(['ok' => true]);

    case 'upload_revision_file':
        if (!$authed) resp(['ok'=>false,'error'=>'Unauthorized']);
        $pid  = san($_POST['projectId'] ?? '');
        $rNum = intval($_POST['rNum'] ?? 0);
        if (!$pid || !$rNum) resp(['ok'=>false,'error'=>'Missing params']);
        $allowed = ['xlsx','xls','pdf','doc','docx','ppt','pptx','zip','png','jpg','jpeg','mp4','webm','mov'];
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== 0) resp(['ok'=>false,'error'=>'No file']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) resp(['ok'=>false,'error'=>'File type not allowed']);
        $dir = $BASE . '/project-files/' . $pid;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = 'R' . $rNum . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file['name']);
        move_uploaded_file($file['tmp_name'], $dir . '/' . $fname);
        // Update the project record
        $projects = loadProjects($PROJ_FILE);
        foreach ($projects as &$p) {
            if ($p['id'] !== $pid) continue;
            if ($rNum === 1) {
                $p['r1File'] = $fname;
            } else {
                $found = false;
                foreach ($p['revisions'] as &$r) {
                    if ($r['rNum'] == $rNum) { $r['file'] = $fname; $found = true; break; }
                }
                if (!$found) $p['revisions'][] = ['rNum'=>$rNum,'file'=>$fname];
            }
            break;
        }
        saveProjects($PROJ_FILE, $projects);
        resp(['ok'=>true,'filename'=>$fname]);

    case 'remove_revision_file':
        if (!$authed) resp(['ok'=>false,'error'=>'Unauthorized']);
        $pid  = san($_POST['projectId'] ?? '');
        $rNum = intval($_POST['rNum'] ?? 0);
        $projects = loadProjects($PROJ_FILE);
        foreach ($projects as &$p) {
            if ($p['id'] !== $pid) continue;
            if ($rNum===1) { $p['r1File']=null; }
            else { foreach ($p['revisions'] as &$r) { if ($r['rNum']==$rNum) { $r['file']=null; break; } } }
            break;
        }
        saveProjects($PROJ_FILE, $projects);
        resp(['ok'=>true]);

    case 'download_file':
        $pid  = san($_GET['projectId'] ?? '');
        $rNum = intval($_GET['rNum'] ?? 0);
        if (!$pid || !$rNum) resp(['ok'=>false,'error'=>'Missing params']);

        $dir = $BASE . '/project-files/' . $pid;

        // First: try to get filename from projects.json
        $projects = loadProjects($PROJ_FILE);
        $fname = null;
        foreach ($projects as $p) {
            if (san($p['id']) !== $pid) continue;
            if ($rNum === 1) {
                $fname = $p['r1File'] ?? null;
            } else {
                foreach ($p['revisions'] ?? [] as $r) {
                    if ($r['rNum'] == $rNum && !empty($r['file'])) { $fname = $r['file']; break; }
                }
            }
            break;
        }

        // Fallback: scan disk for R{rNum}_* file if JSON has no record
        if (!$fname && is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if ($f === '.' || $f === '..') continue;
                if (stripos($f, 'R' . $rNum . '_') === 0) { $fname = $f; break; }
            }
        }

        if (!$fname) {
            http_response_code(404);
            echo 'File not found. Dir checked: ' . $dir;
            exit;
        }
        $fpath = $dir . '/' . $fname;
        if (!file_exists($fpath)) {
            http_response_code(404);
            echo 'File not on disk. Path: ' . $fpath;
            exit;
        }
        $mime = mime_content_type($fpath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($fname) . '"');
        header('Content-Length: ' . filesize($fpath));
        header('Cache-Control: no-cache');
        readfile($fpath);
        exit;

    default:
        resp(['ok'=>false,'error'=>'Unknown action: '.$action]);
}