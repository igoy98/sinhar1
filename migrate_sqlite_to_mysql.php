<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// Temporarily open sqlite and mysql
$cfg = $SINHAR_CONFIG ?? [];
$sqlite = new PDO('sqlite:' . __DIR__ . '/data/sinhar.db');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mysql = null;
if (($cfg['driver'] ?? '') === 'mysql') {
  $mysql = getDb();
}

// migrate templates
$tstmt = $sqlite->query('SELECT type, content FROM templates');
$countT = 0;
foreach ($tstmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
  $stmt = $mysql->prepare('INSERT INTO templates (type, content) VALUES (:type, :content) ON DUPLICATE KEY UPDATE content = :content');
  $stmt->execute([':type'=>$r['type'], ':content'=>$r['content']]);
  $countT++;
}
echo "Migrated templates: $countT\n";

// migrate checks
$cstmt = $sqlite->query('SELECT type, subject, payload, requirements, created_at FROM checks');
$countC = 0;
foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
  $stmt = $mysql->prepare('INSERT INTO checks (type, subject, payload, requirements, created_at) VALUES (:type,:subject,:payload,:requirements,:created_at)');
  $stmt->execute([
    ':type'=>$r['type'], ':subject'=>$r['subject'], ':payload'=>$r['payload'], ':requirements'=>$r['requirements'], ':created_at'=>$r['created_at']
  ]);
  $countC++;
}
echo "Migrated checks: $countC\n";
