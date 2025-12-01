<?php
session_start();

// Simple admin panel for SINHAR prototype using SQLite
// Default credentials: username=admin password=admin123

require_once __DIR__ . '/db.php';


$action = $_GET['action'] ?? null;
if ($action === 'logout') {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === 'admin' && $pass === 'admin123') {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Kredensial salah';
    }
}

// admin-only actions
if (isset($_SESSION['admin'])) {
  // handle template save
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    try {
      $db = getDb();
      $type = $_POST['type'] ?? '';
      $lines = explode("\n", $_POST['content_lines'] ?? '');
      $items = [];
      foreach ($lines as $ln) {
        $t = trim($ln);
        if ($t !== '') $items[] = $t;
      }
      $content = json_encode($items, JSON_UNESCAPED_UNICODE);
      $stmt = $db->prepare('UPDATE templates SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE type = :type');
      $stmt->execute([':content' => $content, ':type' => $type]);
      if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare('INSERT INTO templates (type, content) VALUES (:type, :content)');
        $stmt->execute([':type' => $type, ':content' => $content]);
      }
      $msg = 'Template disimpan.';
    } catch (Exception $e) {
      $msg = 'Gagal menyimpan template.';
    }
  }
  // handle doc template save (HTML content for downloadable file)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_doc_template'])) {
    try {
      $db = getDb();
      $type = $_POST['doc_type'] ?? '';
      $html = $_POST['doc_html'] ?? '';
      $stmt = $db->prepare('UPDATE templates SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE type = :type');
      $stmt->execute([':content' => $html, ':type' => $type]);
      if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare('INSERT INTO templates (type, content) VALUES (:type, :content)');
        $stmt->execute([':type' => $type, ':content' => $html]);
      }
      $msg = 'Template dokumen disimpan.';
    } catch (Exception $e) {
      $msg = 'Gagal menyimpan template dokumen.';
    }
  }

    // handle delete
    if ($action === 'delete' && !empty($_GET['id'])) {
        $db = getDb();
        $stmt = $db->prepare('DELETE FROM checks WHERE id = :id');
        $stmt->execute([':id' => (int)$_GET['id']]);
        header('Location: admin.php');
        exit;
    }

    // fetch entries
    $db = getDb();
    $rows = $db->query('SELECT * FROM checks ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
  // fetch templates for editing
  $tplStmt = $db->query('SELECT type, content FROM templates');
  $templates = [];
  foreach ($tplStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $content = $t['content'];
    $maybe = json_decode($content, true);
    if (is_array($maybe)) {
      $templates[$t['type']] = $maybe;
    } else {
      // store raw string for HTML document templates
      $templates[$t['type']] = $content;
    }
  }
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Admin Panel - SINHAR">
  <title>Admin Panel - SINHAR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1>Sistem Informasi Haji Reguler (SINHAR)</h1>
      <nav>
        <a href="index.php">Beranda</a>
        <a href="cancellation.php">Pembatalan</a>
        <a href="transfer.php">Pelimpahan</a>
        <a href="admin.php">Admin</a>
        <?php if(isset($_SESSION['admin'])): ?>
          <a href="admin.php?action=logout" style="margin-left:auto;">Logout</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="container">
<?php if (!isset($_SESSION['admin'])): ?>
    <section>
      <h2>Login Admin</h2>
      <?php if(!empty($error)): ?><p style="color:#c0392b"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button class="btn" type="submit" name="login">Login</button>
      </form>
      <p class="hint">Default: <strong>admin / admin123</strong>. Ganti kredensial setelah instalasi.</p>
    </section>
<?php else: ?>
    <section>
      <h2>Riwayat Cek</h2>
      <?php if(empty($rows)): ?>
        <p>Tidak ada riwayat.</p>
      <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="text-align:left;background:var(--card)"><th>ID</th><th>Tipe</th><th>Subjek</th><th>Tanggal</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td style="padding:8px;border-bottom:1px solid #eee"><?=htmlspecialchars($r['id'])?></td>
                <td style="padding:8px;border-bottom:1px solid #eee"><?=htmlspecialchars($r['type'])?></td>
                <td style="padding:8px;border-bottom:1px solid #eee"><?=htmlspecialchars($r['subject'])?></td>
                <td style="padding:8px;border-bottom:1px solid #eee"><?=htmlspecialchars($r['created_at'])?></td>
                <td style="padding:8px;border-bottom:1px solid #eee"><a href="admin.php?action=view&id=<?=$r['id']?>">Lihat</a> | <a href="admin.php?action=delete&id=<?=$r['id']?>" onclick="return confirm('Hapus entri ini?')">Hapus</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <section>
      <h2>Edit Template Persyaratan</h2>
      <?php if(!empty($msg)): ?><p style="color:green"><?=htmlspecialchars($msg)?></p><?php endif; ?>
      <p class="hint">Masukkan satu persyaratan per baris. Setelah disimpan, perubahan akan menjadi default pada form pengecekan.</p>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <form method="post" style="flex:1;min-width:280px">
          <input type="hidden" name="type" value="cancellation">
          <label>Template Pembatalan</label>
          <textarea name="content_lines" rows="8" style="width:100%"><?php
            if(!empty($templates['cancellation'])) echo htmlspecialchars(implode("\n", $templates['cancellation']));
          ?></textarea>
          <button class="btn" type="submit" name="save_template">Simpan Pembatalan</button>
        </form>

        <form method="post" style="flex:1;min-width:280px">
          <input type="hidden" name="type" value="transfer">
          <label>Template Pelimpahan</label>
          <textarea name="content_lines" rows="8" style="width:100%"><?php
            if(!empty($templates['transfer'])) echo htmlspecialchars(implode("\n", $templates['transfer']));
          ?></textarea>
          <button class="btn" type="submit" name="save_template">Simpan Pelimpahan</button>
        </form>
      </div>
    </section>
    
    <section>
      <h2>Edit Template Dokumen (Downloadable)</h2>
      <p class="hint">Anda dapat mengedit HTML yang akan digunakan untuk file .doc yang diunduh. Gunakan placeholder: <code>{{NAMA}}</code>, <code>{{ALASAN}}</code>, <code>{{SUDAH_BAYAR}}</code>, <code>{{REQUIREMENTS}}</code> (daftar persyaratan akan digantikan menjadi &lt;ul&gt;).</p>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <form method="post" style="flex:1;min-width:300px">
          <input type="hidden" name="doc_type" value="cancellation_doc">
          <label>Dokumen Pembatalan (HTML)</label>
          <textarea name="doc_html" rows="10" style="width:100%"><?php
            // show stored HTML if exists
            if(!empty($templates['cancellation_doc'])) echo htmlspecialchars(implode("\n", (array)$templates['cancellation_doc']));
          ?></textarea>
          <button class="btn" type="submit" name="save_doc_template">Simpan Dokumen Pembatalan</button>
        </form>

        <form method="post" style="flex:1;min-width:300px">
          <input type="hidden" name="doc_type" value="transfer_doc">
          <label>Dokumen Pelimpahan (HTML)</label>
          <textarea name="doc_html" rows="10" style="width:100%"><?php
            if(!empty($templates['transfer_doc'])) echo htmlspecialchars(implode("\n", (array)$templates['transfer_doc']));
          ?></textarea>
          <button class="btn" type="submit" name="save_doc_template">Simpan Dokumen Pelimpahan</button>
        </form>
      </div>
    </section>

    <?php if(isset($_GET['action']) && $_GET['action']==='view' && !empty($_GET['id'])):
        $id = (int)$_GET['id'];
        $stmt = $db->prepare('SELECT * FROM checks WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row):
    ?>
      <section class="result">
        <h3>Detail Entri #<?=htmlspecialchars($row['id'])?></h3>
        <p><strong>Tipe:</strong> <?=htmlspecialchars($row['type'])?></p>
        <p><strong>Subjek:</strong> <?=htmlspecialchars($row['subject'])?></p>
        <p><strong>Dikunjungi:</strong> <?=htmlspecialchars($row['created_at'])?></p>
        <p><strong>Payload:</strong></p>
        <pre><?=htmlspecialchars($row['payload'])?></pre>
        <p><strong>Requirements:</strong></p>
        <pre><?=htmlspecialchars($row['requirements'])?></pre>
      </section>
    <?php endif; endif; ?>

<?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container">© SINHAR</div>
  </footer>
</body>
</html>
