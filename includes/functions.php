<?php
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sanitize($v){ return trim((string)$v); }
function slugify($text){
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return $text ?: 'halaman';
}
function csrf_token(){
  if(empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_check($token){
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
function is_logged_in(){ return !empty($_SESSION['admin_user']); }

function seo_description(string $html, int $max=160): string {
  $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
  if(mb_strlen($text) <= $max) return $text;
  return rtrim(mb_substr($text, 0, $max-1)) . '…';
}

function optimize_content_html(string $html): string {
  // Add lazy/async attrs to images if missing
  $html = preg_replace('/<img\b(?![^>]*\bloading=)([^>]*)>/i', '<img loading="lazy" decoding="async" $1>', $html);
  return $html;
}
