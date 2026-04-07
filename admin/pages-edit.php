<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';
$id=(int)($_GET['id']??0);
$row=['title'=>'','slug'=>'','content'=>'','type'=>'static','status'=>'draft','sort_order'=>0];
if($id){$st=$pdo->prepare('SELECT * FROM pages WHERE id=?');$st->execute([$id]);$f=$st->fetch(); if($f)$row=$f;}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf'] ?? '')) die('CSRF tidak valid');
  $title=sanitize($_POST['title']??'');
  $slug=sanitize($_POST['slug']??''); if(!$slug)$slug=slugify($title);
  $content=$_POST['content']??''; // html dari quill
  $type=($_POST['type']??'static')==='dynamic'?'dynamic':'static';
  $status=($_POST['status']??'draft')==='published'?'published':'draft';
  $sort=(int)($_POST['sort_order']??0);
  if($id){$q=$pdo->prepare('UPDATE pages SET title=?,slug=?,content=?,type=?,status=?,sort_order=? WHERE id=?');$q->execute([$title,$slug,$content,$type,$status,$sort,$id]);}
  else{$q=$pdo->prepare('INSERT INTO pages(title,slug,content,type,status,sort_order) VALUES(?,?,?,?,?,?)');$q->execute([$title,$slug,$content,$type,$status,$sort]);}
  header('Location: pages.php'); exit;
}
admin_header('pages',$id?'Edit Page':'Tambah Page');
?>
<div class="card"><h2><?= $id?'Edit':'Tambah' ?> Halaman</h2>
<form method="post" id="pageForm">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <label>Judul</label>
  <input id="title" name="title" value="<?= e($row['title']) ?>" required>

  <label>Slug</label>
  <input id="slug" name="slug" value="<?= e($row['slug']) ?>">

  <label>Konten (Rich Text)</label>
  <div id="toolbar" style="border:1px solid #f3b8d3;border-bottom:0;border-radius:10px 10px 0 0;background:#fff7fb;padding:6px;display:flex;flex-wrap:wrap;gap:6px">
    <button type="button" class="ql-bold"></button>
    <button type="button" class="ql-italic"></button>
    <button type="button" class="ql-underline"></button>
    <button type="button" class="ql-strike"></button>
    <button type="button" class="ql-blockquote"></button>
    <button type="button" class="ql-code-block"></button>
    <button type="button" class="ql-link"></button>
    <button type="button" class="ql-image"></button>
    <button type="button" class="ql-list" value="ordered"></button>
    <button type="button" class="ql-list" value="bullet"></button>
    <button type="button" class="ql-header" value="1"></button>
    <button type="button" class="ql-header" value="2"></button>
    <button type="button" class="ql-clean"></button>
  </div>
  <div id="editor" style="height:340px;border:1px solid #f3b8d3;border-radius:0 0 10px 10px;background:#fff"></div>
  <textarea id="content" name="content" style="display:none;"><?= e($row['content']) ?></textarea>

  <label>Tipe</label>
  <select name="type"><option value="static" <?= $row['type']==='static'?'selected':'' ?>>static</option><option value="dynamic" <?= $row['type']==='dynamic'?'selected':'' ?>>dynamic</option></select>

  <label>Status</label>
  <select name="status"><option value="draft" <?= $row['status']==='draft'?'selected':'' ?>>draft</option><option value="published" <?= $row['status']==='published'?'selected':'' ?>>published</option></select>

  <label>Sort</label>
  <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>">

  <button>Simpan</button>
</form>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const t=document.getElementById('title'), s=document.getElementById('slug');
if(t&&s){t.addEventListener('input',()=>{ if(!s.dataset.edited){ s.value=t.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-'); }}); s.addEventListener('input',()=>s.dataset.edited='1');}

const quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: '#toolbar' } });
const hiddenContent = document.getElementById('content');
if(hiddenContent.value){ quill.clipboard.dangerouslyPasteHTML(hiddenContent.value); }

document.getElementById('pageForm').addEventListener('submit', function(){
  hiddenContent.value = quill.root.innerHTML;
});
</script>
<?php admin_footer(); ?>
