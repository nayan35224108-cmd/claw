<?php
require __DIR__.'/config.php';
require __DIR__.'/includes/functions.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
$host = $_SERVER['HTTP_HOST'] ?? 'claw.pjdigital.top';
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = $path === '' ? [] : explode('/', $path);

$pageTitle = null;
$metaDescription = null;
$canonicalUrl = $scheme.'://'.$host.'/';
$ogType = 'website';
$articlePublishedTime = null;
$articleModifiedTime = null;

// BLOG DETAIL: /blog/{slug}
if (count($segments) >= 2 && $segments[0] === 'blog') {
  $postSlug = $segments[1];
  $st = $pdo->prepare("SELECT * FROM posts WHERE slug=? AND status='published' LIMIT 1");
  $st->execute([$postSlug]);
  $post = $st->fetch();
  if (!$post) { include __DIR__.'/templates/404.php'; exit; }

  $pageTitle = $post['title'];
  $metaDescription = !empty($post['excerpt']) ? $post['excerpt'] : seo_description($post['content']);
  $canonicalUrl = $scheme.'://'.$host.'/blog/'.rawurlencode($post['slug']);
  $ogType = 'article';
  $articlePublishedTime = $post['published_at'] ?: $post['created_at'];
  $articleModifiedTime = $post['updated_at'] ?? null;

  $layoutMode = 'two-col';
  $bodyClass = 'inner-page';
  include __DIR__.'/templates/header.php';
  echo '<article class="content">';
  echo '<p><a href="/blog">← Kembali ke Blog</a></p>';
  echo '<h1>'.e($post['title']).'</h1>';
  echo '<small>'.e($post['published_at'] ?: $post['created_at']).'</small>';
  echo '<div>'.optimize_content_html($post['content']).'</div>';
  $shareUrl = rawurlencode($canonicalUrl);
  $shareText = rawurlencode($post['title']);
  echo '<hr><div class="share-row" style="margin-top:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap"><strong>Bagikan:</strong>';
  echo '<a class="share-btn" style="padding:.45rem .7rem;border-radius:8px;border:1px solid #ddd;text-decoration:none" target="_blank" rel="noopener" href="https://x.com/intent/tweet?text='.$shareText.'&url='.$shareUrl.'">𝕏 Twitter</a>';
  echo '<a class="share-btn" style="padding:.45rem .7rem;border-radius:8px;border:1px solid #ddd;text-decoration:none" target="_blank" rel="noopener" href="https://wa.me/?text='.$shareText.'%20'.$shareUrl.'">WhatsApp</a>';
  echo '<a class="share-btn" style="padding:.45rem .7rem;border-radius:8px;border:1px solid #ddd;text-decoration:none" target="_blank" rel="noopener" href="https://t.me/share/url?url='.$shareUrl.'&text='.$shareText.'">Telegram</a>';
  echo '</div>';
  echo '</article>';
  include __DIR__.'/templates/footer.php';
  exit;
}

$slug = $_GET['page'] ?? ($segments[0] ?? '');
$slug = trim($slug, '/');
if ($slug==='' || $slug==='index.php') $slug='beranda';

$st=$pdo->prepare("SELECT * FROM pages WHERE slug=? AND status='published' LIMIT 1");
$st->execute([$slug]);
$page=$st->fetch();
if(!$page){ include __DIR__.'/templates/404.php'; exit; }

$pageTitle = $page['title'];
$metaDescription = seo_description($page['content'] ?: $page['title']);
$canonicalUrl = $scheme.'://'.$host.'/'.rawurlencode($page['slug']);
if($page['slug']==='beranda') $canonicalUrl = $scheme.'://'.$host.'/';
$layoutMode = ($page['slug']==='beranda') ? 'one-col' : 'two-col';


// FAQ schema for service pages (AI-era SEO)
$faqSchema = null;
if (!empty($page['slug']) && in_array($page['slug'], ['beranda','paket-openclaw'], true)) {
  if ($page['slug'] === 'beranda') {
    $faqSchema = [
      ['q'=>'Apakah harga paket sudah termasuk VPS?','a'=>'Belum. Biaya VPS/domain/infrastruktur ditanggung klien.'],
      ['q'=>'Berapa lama proses setup sampai live?','a'=>'Umumnya 1–3 hari kerja tergantung kompleksitas kebutuhan dan kesiapan akses.'],
      ['q'=>'Apakah bisa request channel selain Telegram?','a'=>'Bisa, selama channel/plugin tersedia dan kompatibel.'],
      ['q'=>'Apakah ada support setelah setup?','a'=>'Ada, terutama melalui paket Managed untuk pendampingan berkala.']
    ];
  } else {
    $faqSchema = [
      ['q'=>'Jika saya belum punya server, bisa dibantu?','a'=>'Bisa. Kami bantu rekomendasi spesifikasi dan alur deploy sesuai budget.'],
      ['q'=>'Apa bedanya Pro dan Managed?','a'=>'Pro fokus setup + optimasi awal. Managed mencakup pendampingan, update, dan troubleshooting prioritas.'],
      ['q'=>'Apakah bisa upgrade paket di tengah jalan?','a'=>'Bisa, paket dapat dinaikkan sesuai kebutuhan operasional.']
    ];
  }
}

$bodyClass = ($page['slug']==='beranda') ? 'home-page' : 'inner-page';
include __DIR__.'/templates/header.php';

echo '<article class="content">';

// DYNAMIC BLOG LIST for page type=dynamic and slug=blog
if ($page['type']==='dynamic' && $page['slug']==='blog') {
  echo '<h1>'.e($page['title']).'</h1>';
  echo '<p>'.e($page['content'] ?: 'Artikel terbaru dari blog kami.').'</p>';
  $perPage = 6;
  $currentPage = max(1, (int)($_GET['p'] ?? 1));

  $countSt = $pdo->query("SELECT COUNT(*) c FROM posts WHERE status='published'");
  $totalPosts = (int)($countSt->fetch()['c'] ?? 0);
  $totalPages = max(1, (int)ceil($totalPosts / $perPage));
  if ($currentPage > $totalPages) $currentPage = $totalPages;

  $offset = ($currentPage - 1) * $perPage;
  $pst = $pdo->prepare("SELECT title,slug,excerpt,published_at,created_at FROM posts WHERE status='published' ORDER BY COALESCE(published_at,created_at) DESC, id DESC LIMIT :limit OFFSET :offset");
  $pst->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $pst->bindValue(':offset', $offset, PDO::PARAM_INT);
  $pst->execute();
  $posts = $pst->fetchAll();

  if (!$posts) {
    echo '<p>Belum ada post published.</p>';
  } else {
    echo '<div style="display:grid;gap:12px">';
    foreach($posts as $p){
      echo '<div style="padding:12px;border:1px solid #eee;border-radius:10px">';
      echo '<h3 style="margin:0 0 8px"><a href="/blog/'.e($p['slug']).'">'.e($p['title']).'</a></h3>';
      echo '<small>'.e($p['published_at'] ?: $p['created_at']).'</small>';
      if(!empty($p['excerpt'])) echo '<p>'.e($p['excerpt']).'</p>';
      echo '</div>';
    }
    echo '</div>';

    if ($totalPages > 1) {
      echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:14px">';
      if ($currentPage > 1) {
        echo '<a href="/blog?p='.($currentPage-1).'" style="padding:.4rem .7rem;border:1px solid #ddd;border-radius:8px;text-decoration:none">← Prev</a>';
      }
      echo '<span>Halaman '.(int)$currentPage.' dari '.(int)$totalPages.'</span>';
      if ($currentPage < $totalPages) {
        echo '<a href="/blog?p='.($currentPage+1).'" style="padding:.4rem .7rem;border:1px solid #ddd;border-radius:8px;text-decoration:none">Next →</a>';
      }
      echo '</div>';
    }
  }
} else {
  echo optimize_content_html($page['content']);
}

echo '</article>';
include __DIR__.'/templates/footer.php';
