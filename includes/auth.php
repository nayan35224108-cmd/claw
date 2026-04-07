<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/functions.php';
if (!is_logged_in()) {
  header('Location: /admin/login.php');
  exit;
}
