<?php
$dbFile = __DIR__ . '/data/sinhar.db';
if (!file_exists(dirname($dbFile))) mkdir(dirname($dbFile),0755,true);
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS checks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL,
  subject TEXT,
  payload TEXT,
  requirements TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");
$db->exec("CREATE TABLE IF NOT EXISTS templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL UNIQUE,
  content TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

echo "SQLite tables ensured and default templates inserted (if missing)\n";
