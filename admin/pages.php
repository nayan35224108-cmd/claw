<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';
if(isset($_GET['del']) && isset($_GET['csrf']) && csrf_check($_GET['csrf'])){
  $id=(int)$_GET['del'];
  $pdo->prepare('DELETE FROM pages WHERE id=?')->execute([$id]);
  header('Location: pages.php'); exit;
}
$rows=$pdo->query('SELECT id,title,type,status,created_at,updated_at FROM pages ORDER BY sort_order,id DESC')->fetchAll();
$token=csrf_token();
admin_header('pages','Pages');
?>
<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h2>Pages</h2><a class="btn" href="/admin/pages-edit.php">+ Tambah</a></div>
<table><tr><th>Judul</th><th>Tipe</th><th>Status</th><th>Dibuat</th><th>Diupdate</th><th>Aksi</th></tr><?php foreach($rows as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['type']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['created_at']) ?></td><td><?= e($r['updated_at']) ?></td><td><a href="pages-edit.php?id=<?= (int)$r['id'] ?>">Edit</a> | <a href="?del=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" onclick="return confirm('Hapus halaman ini?')">Hapus</a></td></tr><?php endforeach; ?></table></div>
<?php admin_footer(); ?>
