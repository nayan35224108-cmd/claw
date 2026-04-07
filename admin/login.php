<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/functions.php';
$err='';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$maxAttempts = 5;
$lockMinutes = 15;

$st=$pdo->prepare('SELECT * FROM login_attempts WHERE ip=? LIMIT 1');
$st->execute([$ip]);
$attempt=$st->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf'] ?? '')){
    $err='CSRF token tidak valid';
  } else {
    // check lock
    if($attempt && !empty($attempt['locked_until']) && strtotime($attempt['locked_until']) > time()){
      $remain = ceil((strtotime($attempt['locked_until']) - time())/60);
      $err='Terlalu banyak percobaan login. Coba lagi dalam '.$remain.' menit.';
    } else {
      $u=sanitize($_POST['username']??'');
      $p=$_POST['password']??'';
      $uSt=$pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1');
      $uSt->execute([$u]);
      $user=$uSt->fetch();

      if($user && password_verify($p,$user['password'])){
        // success: reset attempts for IP
        if($attempt){
          $pdo->prepare('UPDATE login_attempts SET attempts=0, locked_until=NULL WHERE ip=?')->execute([$ip]);
        }
        session_regenerate_id(true);
        $_SESSION['admin_user']=$user['username'];
        $_SESSION['admin_role']=$user['role'];
        header('Location: /admin/index.php'); exit;
      }

      // failed login
      if(!$attempt){
        $pdo->prepare('INSERT INTO login_attempts(ip,attempts,last_attempt) VALUES(?,1,NOW())')->execute([$ip]);
        $attemptCount=1;
      } else {
        $attemptCount=((int)$attempt['attempts'])+1;
        $lockedUntil = null;
        if($attemptCount >= $maxAttempts){
          $lockedUntil = date('Y-m-d H:i:s', time()+($lockMinutes*60));
          $pdo->prepare('UPDATE login_attempts SET attempts=?, last_attempt=NOW(), locked_until=? WHERE ip=?')->execute([$attemptCount,$lockedUntil,$ip]);
          $err='Terlalu banyak percobaan login. Coba lagi dalam '.$lockMinutes.' menit.';
        } else {
          $pdo->prepare('UPDATE login_attempts SET attempts=?, last_attempt=NOW(), locked_until=NULL WHERE ip=?')->execute([$attemptCount,$ip]);
          $err='Login gagal. Percobaan '.$attemptCount.'/'.$maxAttempts;
        }
      }

      if($err==='') $err='Login gagal';
    }
  }
}
?><!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="/admin/assets/admin.css"></head><body class="login-page"><div class="login-shell"><div class="login-card"><div class="login-badge">Admin Area</div><h2>Masuk Dashboard</h2><p class="login-sub">Kelola konten, menu, media, dan pengaturan website.</p><?php if($err): ?><p class="login-err"><?= e($err) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Username</label><input name="username" required><label>Password</label><input type="password" name="password" required><button>Masuk</button></form></div></div></body></html>
