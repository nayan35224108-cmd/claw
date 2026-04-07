<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';
if(isset($_GET['del']) && isset($_GET['csrf']) && csrf_check($_GET['csrf'])){
  $pdo->prepare('DELETE FROM posts WHERE id=?')->execute([(int)$_GET['del']]);
  header('Location: posts.php'); exit;
}
$rows=$pdo->query('SELECT * FROM posts ORDER BY created_at DESC,id DESC')->fetchAll();
$token=csrf_token();
admin_header('posts','Posts');
?>
<div class="card"><div style="display:flex;justify-content:space-between;align-items:center"><h2>Posts</h2><a class="btn" href="/admin/posts-edit.php">+ Tambah Post</a></div>
<table><tr><th>Judul</th><th>Slug</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['slug']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['published_at'] ?: $r['created_at']) ?></td><td><a href="/admin/posts-edit.php?id=<?= (int)$r['id'] ?>">Edit</a> | <a href="?del=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" onclick="return confirm('Hapus post?')">Hapus</a></td></tr><?php endforeach; ?>
</table></div><?php admin_footer(); ?>
