<?php
if (!function_exists('admin_header')) {
  function admin_header(string $active='dashboard', string $title='Admin'){
    require_once __DIR__.'/../includes/functions.php';
    $items = [
      'dashboard' => ['/admin/index.php','Dashboard'],
      'pages'     => ['/admin/pages.php','Pages'],
      'posts'     => ['/admin/posts.php','Posts'],
      'menu'      => ['/admin/menu.php','Menu'],
      'media'     => ['/admin/media.php','Media'],
      'settings'  => ['/admin/settings-general.php','Pengaturan'],
      'logout'    => ['/admin/logout.php','Logout'],
    ];
    echo '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="/admin/assets/admin.css"><title>'.e($title).'</title></head><body>';
    echo '<div class="wrap admin-layout">';
    echo '<aside class="topnav">';
    foreach($items as $k=>$v){
      $cls = 'btn'.($active===$k?' active':'');
      echo '<a class="'.$cls.'" href="'.$v[0].'">'.$v[1].'</a>';
    }
    echo '</aside><section class="main-content">';
  }
  function admin_footer(){ echo '</section></div></body></html>'; }
}
