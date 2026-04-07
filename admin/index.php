<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/_layout.php';
$published=$pdo->query("SELECT COUNT(*) c FROM pages WHERE status='published'")->fetch()['c']??0;
$draft=$pdo->query("SELECT COUNT(*) c FROM pages WHERE status='draft'")->fetch()['c']??0;
$media=$pdo->query("SELECT COUNT(*) c FROM media")->fetch()['c']??0;
admin_header('dashboard','Dashboard');
?>
<div class="card"><h2>Dashboard</h2><p>Published Pages: <b><?= (int)$published ?></b></p><p>Draft Pages: <b><?= (int)$draft ?></b></p><p>Total Media: <b><?= (int)$media ?></b></p></div>
<?php admin_footer(); ?>
