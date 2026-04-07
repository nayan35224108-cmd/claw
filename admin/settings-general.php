<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/_layout.php';
require_once __DIR__.'/../includes/settings.php';

$msg=''; $err='';
$updir = realpath(__DIR__.'/../uploads').'/';
$max = 2*1024*1024;
$allowImg = ['image/jpeg','image/png','image/webp','image/svg+xml','image/x-icon','image/vnd.microsoft.icon'];
$themes = [
  'crab-manja' => 'Kepiting Manja (merah)',
  'midnight-code' => 'Midnight Code (gelap)',
  'ocean-breeze' => 'Ocean Breeze (biru)',
  'forest-zen' => 'Forest Zen (hijau)',
  'inner-glow' => 'Inner Glow (teal-orange premium)',
  'inner-glow-emerald' => 'Inner Glow Emerald (hijau premium)',
  'inner-glow-violet' => 'Inner Glow Violet (ungu-cyan)',
  'inner-glow-rose' => 'Inner Glow Rose (pink-orange)',
  'inner-glow-slate' => 'Inner Glow Slate (teal-slate)'
];

$saveSetting = function($k,$v) use ($pdo){
  $q=$pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
  $q->execute([$k,$v]);
};

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf'] ?? '')) die('CSRF tidak valid');

  $siteTitle=sanitize($_POST['site_title'] ?? 'Claw CMS');
  $saveSetting('site_title',$siteTitle);

  $theme=sanitize($_POST['web_theme'] ?? 'crab-manja');
  if(!array_key_exists($theme,$themes)) $theme='crab-manja';
  $saveSetting('web_theme',$theme);

  $siteLogoManual=sanitize($_POST['site_logo'] ?? '');
  $siteFaviconManual=sanitize($_POST['site_favicon'] ?? '');

  if(isset($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? 4) !== 4){
    $f=$_FILES['logo_file'];
    if($f['error']!==0) $err='Upload logo gagal.';
    elseif($f['size']>$max) $err='Logo melebihi 2MB.';
    else {
      $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
      if(!in_array($mime,$allowImg,true)) $err='Format logo tidak didukung.';
      else {
        $ext=pathinfo($f['name'], PATHINFO_EXTENSION);
        $name='logo_'.time().'_'.bin2hex(random_bytes(3)).($ext?'.'.$ext:'');
        if(move_uploaded_file($f['tmp_name'],$updir.$name)){
          $siteLogoManual='/uploads/'.$name;
          $saveSetting('site_logo',$siteLogoManual);
        } else $err='Gagal simpan file logo.';
      }
    }
  }

  if(isset($_FILES['favicon_file']) && ($_FILES['favicon_file']['error'] ?? 4) !== 4){
    $f=$_FILES['favicon_file'];
    if($f['error']!==0) $err='Upload favicon gagal.';
    elseif($f['size']>$max) $err='Favicon melebihi 2MB.';
    else {
      $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
      if(!in_array($mime,$allowImg,true)) $err='Format favicon tidak didukung.';
      else {
        $ext=pathinfo($f['name'], PATHINFO_EXTENSION);
        $name='favicon_'.time().'_'.bin2hex(random_bytes(3)).($ext?'.'.$ext:'');
        if(move_uploaded_file($f['tmp_name'],$updir.$name)){
          $siteFaviconManual='/uploads/'.$name;
          $saveSetting('site_favicon',$siteFaviconManual);
        } else $err='Gagal simpan file favicon.';
      }
    }
  }

  if($siteLogoManual!=='') $saveSetting('site_logo',$siteLogoManual);
  if($siteFaviconManual!=='') $saveSetting('site_favicon',$siteFaviconManual);

  if(!$err) $msg='Pengaturan tersimpan.';
}

$siteTitle=get_setting($pdo,'site_title','Claw CMS');
$siteLogo=get_setting($pdo,'site_logo','');
$siteFavicon=get_setting($pdo,'site_favicon','');
$webTheme=get_setting($pdo,'web_theme','crab-manja');
if(!array_key_exists($webTheme,$themes)) $webTheme='crab-manja';

admin_header('settings','General Settings');
?>
<div class="card">
  <h2>Pengaturan → General</h2>
  <?php if($msg): ?><p style="color:green"><?= e($msg) ?></p><?php endif; ?>
  <?php if($err): ?><p style="color:red"><?= e($err) ?></p><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>Judul Website</label>
    <input name="site_title" value="<?= e($siteTitle) ?>" required>

    <label>Tema Web</label>
    <select name="web_theme">
      <?php foreach($themes as $k=>$v): ?>
        <option value="<?= e($k) ?>" <?= $webTheme===$k?'selected':'' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>

    <hr>
    <h3 style="margin:0 0 8px">Logo Website</h3>
    <p style="font-size:13px;opacity:.8;margin-top:0">Rekomendasi logo: <b>tinggi 80–120px</b>, rasio horizontal (mis. <b>320×100</b>), PNG/WebP transparan. Maks 2MB.</p>
    <?php if($siteLogo): ?><p>Logo saat ini:<br><img src="<?= e($siteLogo) ?>" alt="logo" style="max-height:60px;background:#fff;border:1px solid #eee;padding:4px;border-radius:8px"></p><?php endif; ?>
    <label>Upload Logo (langsung)</label>
    <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
    <label>Atau URL/Path Logo (manual)</label>
    <input name="site_logo" value="<?= e($siteLogo) ?>" placeholder="/uploads/logo.png atau https://...">

    <hr>
    <h3 style="margin:0 0 8px">Favicon</h3>
    <p style="font-size:13px;opacity:.8;margin-top:0">Rekomendasi favicon: <b>512×512</b> (sumber), minimal <b>32×32</b>, PNG/ICO. Maks 2MB.</p>
    <?php if($siteFavicon): ?><p>Favicon saat ini:<br><img src="<?= e($siteFavicon) ?>" alt="favicon" style="width:32px;height:32px;background:#fff;border:1px solid #eee;padding:3px;border-radius:6px"></p><?php endif; ?>
    <label>Upload Favicon (langsung)</label>
    <input type="file" name="favicon_file" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/webp">
    <label>Atau URL/Path Favicon (manual)</label>
    <input name="site_favicon" value="<?= e($siteFavicon) ?>" placeholder="/uploads/favicon.png atau https://...">

    <button>Simpan</button>
  </form>
</div>
<?php admin_footer(); ?>
