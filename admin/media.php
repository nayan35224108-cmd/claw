<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';
$updir=realpath(__DIR__.'/../uploads').'/'; $max=2*1024*1024;
$allow=['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['file'])){
  if(!csrf_check($_POST['csrf'] ?? '')) die('CSRF tidak valid');
  $f=$_FILES['file']; if($f['error']===0){
    if($f['size']>$max){$msg='Ukuran file melebihi 2MB';}
    else { $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
      if(!in_array($mime,$allow,true)){$msg='MIME type tidak diizinkan';}
      else { $ext=pathinfo($f['name'], PATHINFO_EXTENSION); $name=time().'_'.bin2hex(random_bytes(4)).($ext?'.'.$ext:'');
        if(move_uploaded_file($f['tmp_name'],$updir.$name)){ $pdo->prepare('INSERT INTO media(filename,original_name,mime_type,file_size,uploaded_by) VALUES(?,?,?,?,1)')->execute([$name,$f['name'],$mime,$f['size']]); header('Location: media.php'); exit; }
      }
    }
  } else $msg='Upload error';
}
if(isset($_GET['del']) && isset($_GET['csrf']) && csrf_check($_GET['csrf'])){ $id=(int)$_GET['del']; $st=$pdo->prepare('SELECT filename FROM media WHERE id=?');$st->execute([$id]);$m=$st->fetch(); if($m)@unlink($updir.$m['filename']); $pdo->prepare('DELETE FROM media WHERE id=?')->execute([$id]); header('Location: media.php'); exit; }
$rows=$pdo->query('SELECT * FROM media ORDER BY id DESC')->fetchAll(); $token=csrf_token();
admin_header('media','Media');
?>
<div class="card"><h2>Media</h2><?php if($msg) echo '<p style="color:red">'.e($msg).'</p>'; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e($token) ?>"><input type="file" name="file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" required><button>Upload</button></form></div>
<div class="card"><table><tr><th>Preview</th><th>Nama</th><th>Tipe</th><th>Ukuran</th><th>URL</th><th>Aksi</th></tr><?php foreach($rows as $r): $url='/uploads/'.$r['filename']; $isImg=str_starts_with($r['mime_type'],'image/'); ?><tr><td><?php if($isImg): ?><img class="thumb" src="<?= e($url) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:6px;background:#eef"><?php else: ?><span>📄</span><?php endif; ?></td><td><?= e($r['original_name']) ?></td><td><?= e($r['mime_type']) ?></td><td><?= (int)$r['file_size'] ?></td><td><input id="u<?= (int)$r['id'] ?>" value="<?= e($url) ?>" readonly></td><td><button type="button" onclick="navigator.clipboard.writeText(document.getElementById('u<?= (int)$r['id'] ?>').value)">Copy URL</button> <a href="?del=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" onclick="return confirm('Hapus file ini?')">Hapus</a></td></tr><?php endforeach; ?></table></div>
<?php admin_footer(); ?>
