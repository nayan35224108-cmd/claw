<?php
if (!function_exists('get_setting')) {
  function get_setting(PDO $pdo, string $key, string $default=''): string {
    $st=$pdo->prepare('SELECT value FROM settings WHERE `key`=? LIMIT 1');
    $st->execute([$key]);
    $r=$st->fetch();
    return $r && isset($r['value']) ? (string)$r['value'] : $default;
  }
}
