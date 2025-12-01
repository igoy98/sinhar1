<?php
require_once __DIR__ . '/db.php';
try{
  $db = getDb();
  // create checks table
  $db->exec("CREATE TABLE IF NOT EXISTS checks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    subject TEXT,
    payload TEXT,
    requirements TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );");
  // templates table
  $db->exec("CREATE TABLE IF NOT EXISTS templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL UNIQUE,
    content TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );");
  echo "Initialized DB\n";
}catch(Exception $e){
  echo "Init failed: " . $e->getMessage() . "\n";
}
