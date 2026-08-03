<?php
/**
 * reset.php — completes a password reset from an emailed link.
 * Link format: reset.php?u=USERNAME&t=TOKEN (45-minute, single-use token).
 */
require_once __DIR__ . '/auth_check.php';
auth_boot();

$u = trim($_REQUEST['u'] ?? '');
$t = trim($_REQUEST['t'] ?? '');
$valid = ($u !== '' && $t !== '' && auth_check_reset_token($u, $t));
$done = false; $err = '';

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $n1 = $_POST['new1'] ?? ''; $n2 = $_POST['new2'] ?? '';
    if ($n1 !== $n2)            $err = 'Passwords do not match.';
    elseif (strlen($n1) < 8)    $err = 'Password must be at least 8 characters.';
    elseif (!auth_consume_reset_token($u, $t, $n1)) $err = 'This link has expired or was already used — request a new one.';
    else $done = true;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>TI-Kitmeer Admin — Reset Password</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
:root{--blue-dark:#0d2137;--blue-mid:#1a6fa0;--teal:#0db8a8;--bg:#eef4f9;--white:#fff;
  --text:#0d2137;--subtext:#4a6a82;--border:#c8dde8;--shadow-h:0 6px 24px rgba(13,33,55,.16);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:
  linear-gradient(135deg,#eef4f9 0%,#dcedf7 100%);color:var(--text);min-height:100vh;
  display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:var(--white);border:1px solid var(--border);border-radius:16px;
  padding:38px 42px;width:100%;max-width:400px;box-shadow:var(--shadow-h);}
h2{font-size:19px;font-weight:800;margin-bottom:6px;text-align:center;}
p{font-size:12px;color:var(--subtext);margin-bottom:20px;text-align:center;line-height:1.55;}
.lbl{display:block;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
  color:var(--subtext);margin-bottom:6px;}
.inp{width:100%;background:#f4f8fc;border:1.5px solid var(--border);border-radius:8px;
  padding:11px 13px;font-size:13px;outline:none;font-family:inherit;margin-bottom:14px;}
.inp:focus{border-color:#1a9ad4;background:var(--white);}
.btn{width:100%;background:linear-gradient(135deg,var(--blue-mid),var(--teal));border:none;
  border-radius:8px;padding:13px;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;}
.btn:hover{opacity:.88;}
.ok{background:#e9f9f2;border:1px solid #b8e8d2;color:#0a7a52;border-radius:9px;
  padding:14px;font-size:12.5px;line-height:1.55;text-align:center;}
.bad{background:#fdeeee;border:1px solid #f0c4c4;color:#b03030;border-radius:9px;
  padding:14px;font-size:12.5px;line-height:1.55;text-align:center;margin-bottom:14px;}
.back{display:block;text-align:center;margin-top:18px;font-size:11.5px;color:var(--blue-mid);text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="box">
  <h2>Reset Password</h2>
<?php if ($done): ?>
  <div class="ok">✅ Password changed for <b><?= e($u) ?></b>. You can sign in now.</div>
<?php elseif (!$valid): ?>
  <div class="bad">This reset link is invalid, expired, or already used.</div>
  <p>Links are valid for 45 minutes and work once. Request a fresh one below.</p>
  <a class="back" href="forgot.php">→ Request a new reset link</a>
<?php else: ?>
  <p>Setting a new password for <b><?= e($u) ?></b>. Minimum 8 characters.</p>
  <?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="u" value="<?= e($u) ?>">
    <input type="hidden" name="t" value="<?= e($t) ?>">
    <label class="lbl">New Password</label>
    <input class="inp" type="password" name="new1" autocomplete="new-password" autofocus required>
    <label class="lbl">Repeat New Password</label>
    <input class="inp" type="password" name="new2" autocomplete="new-password" required>
    <button class="btn" type="submit">Set New Password</button>
  </form>
<?php endif; ?>
  <a class="back" href="login.php">← Back to Sign In</a>
</div>
</body>
</html>
