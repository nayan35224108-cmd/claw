<?php
require_once __DIR__.'/../includes/settings.php';
$siteTitle = get_setting($pdo,'site_title','Claw CMS');
$siteLogo  = get_setting($pdo,'site_logo','');
$siteFavicon = get_setting($pdo,'site_favicon','');
$theme = get_setting($pdo,'web_theme','crab-manja');
$allowedThemes = ['crab-manja','midnight-code','ocean-breeze','forest-zen','inner-glow','inner-glow-emerald','inner-glow-violet','inner-glow-rose','inner-glow-slate'];
if(!in_array($theme,$allowedThemes,true)) $theme='crab-manja';
$themeCssPath = __DIR__.'/../assets/css/theme-'.$theme.'.css';
$themeCss = '/assets/css/theme-'.$theme.'.css?v='.(file_exists($themeCssPath)?filemtime($themeCssPath):time());
$menu = $pdo->query("SELECT * FROM menu_items WHERE is_active=1 ORDER BY sort_order ASC, id ASC")->fetchAll();
$parents=[]; $children=[];
foreach($menu as $m){ if(empty($m['parent_id'])) $parents[]=$m; else $children[$m['parent_id']][]=$m; }

$latestPosts = [];
try { $latestPosts = $pdo->query("SELECT title,slug FROM posts WHERE status='published' ORDER BY COALESCE(published_at,created_at) DESC, id DESC LIMIT 5")->fetchAll(); } catch(Throwable $e) {}

$fullTitle = isset($pageTitle) && $pageTitle ? ($pageTitle.' | '.$siteTitle) : $siteTitle;
$desc = isset($metaDescription) && $metaDescription ? $metaDescription : ('Layanan digital: SEO, website, dan sistem informasi dari '.$siteTitle);
$canonical = $canonicalUrl ?? (('https://'.($_SERVER['HTTP_HOST'] ?? 'claw.pjdigital.top').'/'));
$ogT = $ogType ?? 'website';
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if($siteFavicon): ?><link rel="icon" href="<?= e($siteFavicon) ?>" type="image/png"><?php endif; ?>

<meta property="og:type" content="<?= e($ogT) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if($siteLogo): ?><meta property="og:image" content="<?= e('https://'.($_SERVER['HTTP_HOST'] ?? 'claw.pjdigital.top').$siteLogo) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($fullTitle) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">

<script type="application/ld+json">
<?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'Organization',
  'name'=>$siteTitle,
  'url'=>'https://'.($_SERVER['HTTP_HOST'] ?? 'claw.pjdigital.top').'/',
  'logo'=>$siteLogo ? 'https://'.($_SERVER['HTTP_HOST'] ?? 'claw.pjdigital.top').$siteLogo : null
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>

<?php if(!empty($faqSchema) && is_array($faqSchema)): ?>
<script type="application/ld+json">
<?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'FAQPage',
  'mainEntity'=>array_map(function($it){
    return [
      '@type'=>'Question',
      'name'=>$it['q'],
      'acceptedAnswer'=>['@type'=>'Answer','text'=>$it['a']]
    ];
  }, $faqSchema)
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>

<?php if(($ogT ?? '')==='article' && !empty($articlePublishedTime)): ?>
<script type="application/ld+json">
<?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'Article',
  'headline'=>$pageTitle ?? $siteTitle,
  'datePublished'=>$articlePublishedTime,
  'dateModified'=>$articleModifiedTime ?? $articlePublishedTime,
  'author'=>['@type'=>'Organization','name'=>$siteTitle],
  'publisher'=>['@type'=>'Organization','name'=>$siteTitle]
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>

<?php $bodyClass = $bodyClass ?? ''; ?>
<link rel="stylesheet" href="<?= e($themeCss) ?>"></head><body class="<?= e($bodyClass) ?>">
<header class="site-header"><div class="container nav-wrap">
<a class="logo" href="/">
  <?php if($siteLogo): ?><img src="<?= e($siteLogo) ?>" alt="logo" style="width:38px;height:38px;object-fit:cover;object-position:center;vertical-align:middle;margin-right:8px;border-radius:8px"><?php endif; ?>
  <span><?= e($siteTitle) ?></span>
</a>
<div style="display:flex;align-items:center;gap:8px">
  <button id="menuToggle" class="menu-toggle">☰</button>
</div>
<nav id="nav" class="nav">
<?php foreach($parents as $p): $sub=$children[$p['id']]??[]; ?>
  <div class="nav-item"><a href="<?= e($p['url']) ?>"><?= e($p['label']) ?></a><?php if($sub): ?><div class="dropdown"><?php foreach($sub as $s): ?><a href="<?= e($s['url']) ?>"><?= e($s['label']) ?></a><?php endforeach; ?></div><?php endif; ?></div>
<?php endforeach; ?>
</nav></div></header>

<?php $layoutMode = $layoutMode ?? 'two-col'; ?>
<main class="container <?= $layoutMode==='one-col' ? 'one-col-layout' : 'two-col-layout' ?>">
  <section class="main-col">
