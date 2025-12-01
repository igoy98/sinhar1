<?php
require_once __DIR__ . '/config.php';

function getDb() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $cfg = $GLOBALS['SINHAR_CONFIG'] ?? [];
  $driver = $cfg['driver'] ?? 'sqlite';
  if ($driver === 'mysql') {
    $host = $cfg['mysql']['host'] ?? '127.0.0.1';
    $db = $cfg['mysql']['database'] ?? 'sinhar';
    $user = $cfg['mysql']['user'] ?? 'root';
    $pass = $cfg['mysql']['pass'] ?? '';
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  } else {
    $file = __DIR__ . '/data/sinhar.db';
    if (!is_dir(dirname($file))) mkdir(dirname($file),0755,true);
    $dsn = "sqlite:" . $file;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  return $pdo;
}
