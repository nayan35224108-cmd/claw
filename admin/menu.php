<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';

$editId = (int)($_GET['edit'] ?? 0);
$editRow = ['id'=>0,'label'=>'','url'=>'','parent_id'=>0,'sort_order'=>0,'is_active'=>1];

// Helper: normalize sort_order jadi 1..N biar tidak loncat
function normalize_menu_sort(PDO $pdo): array {
  $rows = $pdo->query('SELECT id FROM menu_items ORDER BY sort_order ASC, id ASC')->fetchAll();
  $ids = [];
  $i = 1;
  foreach($rows as $r){
    $id = (int)$r['id'];
    $ids[] = $id;
    $pdo->prepare('UPDATE menu_items SET sort_order=? WHERE id=?')->execute([$i, $id]);
    $i++;
  }
  return $ids;
}

if(isset($_GET['move']) && isset($_GET['id']) && isset($_GET['csrf']) && csrf_check($_GET['csrf'])){
  $id=(int)$_GET['id'];
  $dir=$_GET['move']==='up' ? 'up' : 'down';

  // pastikan urutan rapi dulu
  $ids = normalize_menu_sort($pdo);
  $idx = array_search($id, $ids, true);

  if($idx !== false){
    if($dir==='up' && $idx > 0){
      [$ids[$idx-1], $ids[$idx]] = [$ids[$idx], $ids[$idx-1]];
    }
    if($dir==='down' && $idx < count($ids)-1){
      [$ids[$idx+1], $ids[$idx]] = [$ids[$idx], $ids[$idx+1]];
    }

    // tulis ulang urutan final 1..N (swap tetangga, tidak loncat)
    $i=1;
    foreach($ids as $mid){
      $pdo->prepare('UPDATE menu_items SET sort_order=? WHERE id=?')->execute([$i, $mid]);
      $i++;
    }
  }

  header('Location: menu.php'); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf'] ?? '')) die('CSRF tidak valid');
  $id=(int)($_POST['id']??0);
  $label=sanitize($_POST['label']??'');
  $url=sanitize($_POST['url']??'');
  if(empty($url) && !empty($_POST['page_slug'])) $url='/'.sanitize($_POST['page_slug']);
  $pid=(int)($_POST['parent_id']??0);
  $sort=(int)($_POST['sort_order']??0);
  $active=isset($_POST['is_active'])?1:0;
  $parent=$pid>0?$pid:null;

  if($id){
    $pdo->prepare('UPDATE menu_items SET label=?,url=?,parent_id=?,sort_order=?,is_active=? WHERE id=?')
        ->execute([$label,$url,$parent,$sort,$active,$id]);
  } else {
    if($sort===0){
      $mx=$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 n FROM menu_items')->fetch();
      $sort=(int)($mx['n'] ?? 1);
    }
    $pdo->prepare('INSERT INTO menu_items(label,url,parent_id,sort_order,is_active) VALUES(?,?,?,?,?)')
        ->execute([$label,$url,$parent,$sort,$active]);
  }

  normalize_menu_sort($pdo);
  header('Location: menu.php'); exit;
}

if(isset($_GET['del']) && isset($_GET['csrf']) && csrf_check($_GET['csrf'])){
  $pdo->prepare('DELETE FROM menu_items WHERE id=?')->execute([(int)$_GET['del']]);
  normalize_menu_sort($pdo);
  header('Location: menu.php'); exit;
}

$rows=$pdo->query('SELECT * FROM menu_items ORDER BY sort_order,id')->fetchAll();
$pages=$pdo->query("SELECT slug,title FROM pages ORDER BY title")->fetchAll();
$token=csrf_token();

if($editId){
  $st=$pdo->prepare('SELECT * FROM menu_items WHERE id=? LIMIT 1');
  $st->execute([$editId]);
  $f=$st->fetch();
  if($f) $editRow=$f;
}

admin_header('menu','Menu');
?>
<div class="card">
  <h2><?= $editRow['id'] ? 'Edit Menu' : 'Tambah Menu' ?></h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">
    <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">

    <label>Label</label>
    <input name="label" value="<?= e($editRow['label']) ?>" required>

    <label>URL Manual</label>
    <input name="url" value="<?= e($editRow['url']) ?>" placeholder="/beranda atau URL">

    <label>Atau pilih dari halaman</label>
    <select name="page_slug">
      <option value="">-- pilih halaman --</option>
      <?php foreach($pages as $p): ?>
        <option value="<?= e($p['slug']) ?>"><?= e($p['title']) ?> (<?= e($p['slug']) ?>)</option>
      <?php endforeach; ?>
    </select>

    <label>Parent</label>
    <select name="parent_id">
      <option value="0">(tanpa parent)</option>
      <?php foreach($rows as $r): if((int)$r['id']===(int)$editRow['id']) continue; ?>
        <option value="<?= (int)$r['id'] ?>" <?= ((int)$editRow['parent_id']===(int)$r['id'])?'selected':'' ?>><?= e($r['label']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Sort Order (opsional)</label>
    <input type="number" name="sort_order" value="<?= (int)$editRow['sort_order'] ?>">

    <label><input type="checkbox" name="is_active" <?= (int)$editRow['is_active']===1?'checked':'' ?>> Aktif</label>

    <button type="submit">Simpan</button>
    <?php if($editRow['id']): ?><a class="btn" href="/admin/menu.php" style="margin-left:8px;background:#777">Batal</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Label</th><th>URL</th><th>Parent</th><th>Urut</th><th>Aktif</th><th>Aksi</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['label']) ?></td>
        <td><?= e($r['url']) ?></td>
        <td><?= e((string)$r['parent_id']) ?></td>
        <td>
          <a href="?move=up&id=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" title="Naik">⬆️</a>
          <b><?= (int)$r['sort_order'] ?></b>
          <a href="?move=down&id=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" title="Turun">⬇️</a>
        </td>
        <td><?= (int)$r['is_active'] ?></td>
        <td>
          <a href="?edit=<?= (int)$r['id'] ?>">Edit</a> |
          <a href="?del=<?= (int)$r['id'] ?>&csrf=<?= e($token) ?>" onclick="return confirm('Hapus menu ini?')">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_footer(); ?>
