<?php
session_start();

define('DB_HOST','localhost');
define('DB_NAME','claw_cms');
define('DB_USER','dbs13');
define('DB_PASS','qGLnu48XZEKjlxL9OH8Q');
define('BASE_URL','');

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  exit('Database connection failed.');
}
