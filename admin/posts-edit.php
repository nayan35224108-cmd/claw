<?php require_once __DIR__.'/../includes/auth.php'; require_once __DIR__.'/../includes/functions.php'; require_once __DIR__.'/_layout.php';
$id=(int)($_GET['id']??0);
$row=['title'=>'','slug'=>'','excerpt'=>'','content'=>'','status'=>'draft','published_at'=>''];
if($id){$st=$pdo->prepare('SELECT * FROM posts WHERE id=?');$st->execute([$id]);$f=$st->fetch(); if($f)$row=$f;}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf'] ?? '')) die('CSRF tidak valid');
  $title=sanitize($_POST['title']??'');
  $slug=sanitize($_POST['slug']??''); if(!$slug)$slug=slugify($title);
  $excerpt=sanitize($_POST['excerpt']??'');
  $content=$_POST['content']??'';
  $status=($_POST['status']??'draft')==='published'?'published':'draft';
  $publishedAt=!empty($_POST['published_at']) ? $_POST['published_at'] : null;

  if($id){
    $pdo->prepare('UPDATE posts SET title=?,slug=?,excerpt=?,content=?,status=?,published_at=? WHERE id=?')
        ->execute([$title,$slug,$excerpt,$content,$status,$publishedAt,$id]);
  } else {
    $pdo->prepare('INSERT INTO posts(title,slug,excerpt,content,status,published_at) VALUES(?,?,?,?,?,?)')
        ->execute([$title,$slug,$excerpt,$content,$status,$publishedAt]);
  }
  header('Location: posts.php'); exit;
}

$images=$pdo->query("SELECT original_name, filename FROM media WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 200")->fetchAll();
$csrf = csrf_token();
admin_header('posts',$id?'Edit Post':'Tambah Post');
?>
<div class="card">
  <h2><?= $id?'Edit':'Tambah' ?> Post</h2>
  <form method="post" id="postForm">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <label>Judul</label>
    <input id="title" name="title" value="<?= e($row['title']) ?>" required>

    <label>Slug</label>
    <input id="slug" name="slug" value="<?= e($row['slug']) ?>">

    <label>Excerpt</label>
    <textarea name="excerpt" rows="3"><?= e($row['excerpt']) ?></textarea>

    <label>Konten (Rich Text)</label>
    <div id="toolbar" style="border:1px solid #f3b8d3;border-bottom:0;border-radius:10px 10px 0 0;background:#fff7fb;padding:6px;display:flex;flex-wrap:wrap;gap:6px">
      <button type="button" class="ql-bold"></button>
      <button type="button" class="ql-italic"></button>
      <button type="button" class="ql-underline"></button>
      <button type="button" class="ql-strike"></button>
      <button type="button" class="ql-blockquote"></button>
      <button type="button" class="ql-code-block"></button>
      <button type="button" class="ql-link"></button>
      <button type="button" class="ql-list" value="ordered"></button>
      <button type="button" class="ql-list" value="bullet"></button>
      <button type="button" class="ql-header" value="1"></button>
      <button type="button" class="ql-header" value="2"></button>
      <button type="button" class="ql-clean"></button>
    </div>

    <div style="border:1px solid #f3b8d3;border-top:0;padding:10px;background:#fff;display:grid;gap:8px">
      <strong>Sisipkan Gambar</strong>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="file" id="uploadImageInput" accept="image/*">
        <select id="imageSizeSelect">
          <option value="20">20%</option><option value="30">30%</option><option value="40">40%</option>
          <option value="50" selected>50%</option><option value="60">60%</option><option value="70">70%</option>
          <option value="80">80%</option><option value="90">90%</option><option value="100">100% (full)</option>
        </select>
        <button type="button" id="uploadInsertBtn">Upload & Sisipkan</button>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <select id="mediaSelect" style="min-width:320px;max-width:100%">
          <option value="">Pilih gambar dari Media...</option>
          <?php foreach($images as $im): ?>
            <option value="/uploads/<?= e($im['filename']) ?>"><?= e($im['original_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" id="insertFromMediaBtn">Sisipkan dari Media</button>
      </div>
      <small style="opacity:.8">Tips: ukuran gambar bisa dipilih 20% s/d 100% (full). Maks upload langsung 2MB.</small>
    </div>

    <div id="editor" style="height:340px;border:1px solid #f3b8d3;border-radius:0 0 10px 10px;background:#fff"></div>
    <textarea id="content" name="content" style="display:none;"><?= e($row['content']) ?></textarea>

    <label>Status</label>
    <select name="status">
      <option value="draft" <?= $row['status']==='draft'?'selected':'' ?>>draft</option>
      <option value="published" <?= $row['status']==='published'?'selected':'' ?>>published</option>
    </select>

    <label>Published At</label>
    <input type="datetime-local" name="published_at" value="<?= !empty($row['published_at']) ? date('Y-m-d\TH:i', strtotime($row['published_at'])) : '' ?>">

    <button>Simpan</button>
  </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const csrfToken = <?= json_encode($csrf) ?>;
const t=document.getElementById('title'), s=document.getElementById('slug');
if(t&&s){
  t.addEventListener('input',()=>{ if(!s.dataset.edited){ s.value=t.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').trim().replace(/\s+/g,'-').replace(/-+/g,'-'); }});
  s.addEventListener('input',()=>s.dataset.edited='1');
}

const quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: '#toolbar' } });
const hiddenContent = document.getElementById('content');
if(hiddenContent.value){ quill.clipboard.dangerouslyPasteHTML(hiddenContent.value); }

function insertImageAtCursor(url, widthPct){
  const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
  const w = parseInt(widthPct || '100', 10);
  const html = `<p><img src="${url}" style="max-width:${w}%;width:${w}%;height:auto;display:block;margin:8px 0;"></p>`;
  quill.clipboard.dangerouslyPasteHTML(range.index, html, 'user');
  quill.setSelection(range.index + 1, 0);
}

document.getElementById('insertFromMediaBtn').addEventListener('click', () => {
  const url = document.getElementById('mediaSelect').value;
  const size = document.getElementById('imageSizeSelect').value;
  if(!url){ alert('Pilih gambar dari media dulu.'); return; }
  insertImageAtCursor(url, size);
});

document.getElementById('uploadInsertBtn').addEventListener('click', async () => {
  const fileInput = document.getElementById('uploadImageInput');
  const file = fileInput.files[0];
  const size = document.getElementById('imageSizeSelect').value;
  if(!file){ alert('Pilih file gambar dulu.'); return; }

  const fd = new FormData();
  fd.append('file', file);
  fd.append('csrf', csrfToken);

  try {
    const res = await fetch('/admin/upload-image.php', { method:'POST', body: fd, credentials:'same-origin' });
    const data = await res.json();
    if(!data.ok){ alert(data.error || 'Upload gagal'); return; }
    insertImageAtCursor(data.url, size);
    const sel = document.getElementById('mediaSelect');
    const opt = document.createElement('option');
    opt.value = data.url; opt.textContent = data.name || data.url; sel.prepend(opt); sel.value = data.url;
    fileInput.value = '';
  } catch(e){
    alert('Upload gagal. Cek koneksi/server.');
  }
});

document.getElementById('postForm').addEventListener('submit', function(){
  hiddenContent.value = quill.root.innerHTML;
});
</script>
<?php admin_footer(); ?>
