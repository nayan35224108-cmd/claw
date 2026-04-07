<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']!=='POST'){
  http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit;
}
if(!csrf_check($_POST['csrf'] ?? '')){
  http_response_code(403); echo json_encode(['ok'=>false,'error'=>'CSRF tidak valid']); exit;
}
if(!isset($_FILES['file'])){
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'File tidak ditemukan']); exit;
}

$f=$_FILES['file'];
$max=2*1024*1024;
$allow=['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];

if($f['error']!==0){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Upload error']); exit; }
if($f['size']>$max){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Maksimal 2MB']); exit; }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
if(!in_array($mime,$allow,true)){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Format gambar tidak didukung']); exit; }

$updir=realpath(__DIR__.'/../uploads').'/';
$ext=pathinfo($f['name'], PATHINFO_EXTENSION);
$name='postimg_'.time().'_'.bin2hex(random_bytes(4)).($ext?'.'.$ext:'');
if(!move_uploaded_file($f['tmp_name'],$updir.$name)){
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Gagal simpan file']); exit;
}

$uploaderId = 1;
if(!empty($_SESSION['admin_user'])){
  $st=$pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
  $st->execute([$_SESSION['admin_user']]);
  $u=$st->fetch();
  if($u) $uploaderId=(int)$u['id'];
}

$pdo->prepare('INSERT INTO media(filename,original_name,mime_type,file_size,uploaded_by) VALUES(?,?,?,?,?)')
    ->execute([$name,$f['name'],$mime,$f['size'],$uploaderId]);

echo json_encode(['ok'=>true,'url'=>'/uploads/'.$name,'name'=>$name]);
