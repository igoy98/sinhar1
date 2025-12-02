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

  // handle Google Drive links save
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gdrive_links'])) {
    $gdrive_file = __DIR__ . '/config_gdrive.php';
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Google Drive configuration for SINHAR\n";
    $content .= " * Store folder links here that users can download from\n";
    $content .= " */\n\n";
    $content .= "// Define Google Drive folder links for each document type\n";
    $content .= "// Format: 'key' => 'https://drive.google.com/drive/folders/FOLDER_ID'\n";
    $content .= "\$gdrive_links = [\n";
    
    $keys = [
      'cancellation_sakit' => 'Pembatalan - Sakit',
      'cancellation_meninggal' => 'Pembatalan - Meninggal',
      'cancellation_keuangan' => 'Pembatalan - Keuangan',
      'transfer_suami_istri' => 'Pelimpahan - wafat',
      'transfer_anak' => 'Pelimpahan - sakit',
    ];
    
    foreach ($keys as $key => $label) {
      $url = trim($_POST['gdrive_' . $key] ?? '');
      $content .= "    '{$key}' => '" . addslashes($url) . "',       // {$label}\n";
    }
    
    $content .= "];\n\n";
    $content .= "/**\n";
    $content .= " * Get Google Drive link for a specific document type\n";
    $content .= " * @param string \$type Document type key\n";
    $content .= " * @return string|null Google Drive URL or null if not configured\n";
    $content .= " */\n";
    $content .= "function getGDriveLink(\$type) {\n";
    $content .= "    global \$gdrive_links;\n";
    $content .= "    return \$gdrive_links[\$type] ?? null;\n";
    $content .= "}\n\n";
    $content .= "/**\n";
    $content .= " * Check if a document type has a configured Google Drive link\n";
    $content .= " * @param string \$type Document type key\n";
    $content .= " * @return bool\n";
    $content .= " */\n";
    $content .= "function hasGDriveLink(\$type) {\n";
    $content .= "    \$link = getGDriveLink(\$type);\n";
    $content .= "    return !empty(\$link);\n";
    $content .= "}\n\n";
    $content .= "?>\n";
    
    if (file_put_contents($gdrive_file, $content)) {
      $msg = 'Link Google Drive berhasil disimpan.';
    } else {
      $msg = 'Gagal menyimpan link Google Drive.';
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
  
  // Load Google Drive links
  require_once __DIR__ . '/config_gdrive.php';
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
      <div class="header-content">
        <div class="header-logo">
          <img src="assets/img/logo-kemenag.png" alt="Kementerian Agama" sizeof="32px">
        </div>
        <div class="header-title-group">
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
      </div>
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
      <h2>⚙️ Kelola Link Google Drive</h2>
      <p class="hint">Masukkan URL folder Google Drive untuk setiap jenis dokumen. Pengguna akan melihat tombol "Buka Google Drive" pada halaman hasil cek jika link telah dikonfigurasi.</p>
      <?php if(!empty($msg)): ?><div class="success" style="margin-bottom:16px;">✓ <?=htmlspecialchars($msg)?></div><?php endif; ?>
      <form method="post" style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <input type="hidden" name="save_gdrive_links" value="1">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>🚫 Pembatalan - Sakit</label>
            <input type="url" name="gdrive_cancellation_sakit" placeholder="https://drive.google.com/drive/folders/..." value="<?php echo htmlspecialchars(getGDriveLink('cancellation_sakit') ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>🚫 Pembatalan - Meninggal</label>
            <input type="url" name="gdrive_cancellation_meninggal" placeholder="https://drive.google.com/drive/folders/..." value="<?php echo htmlspecialchars(getGDriveLink('cancellation_meninggal') ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>↔️ Pelimpahan - Wafat</label>
            <input type="url" name="gdrive_transfer_suami_istri" placeholder="https://drive.google.com/drive/folders/..." value="<?php echo htmlspecialchars(getGDriveLink('transfer_suami_istri') ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>↔️ Pelimpahan - Sakit Permanen</label>
            <input type="url" name="gdrive_transfer_anak" placeholder="https://drive.google.com/drive/folders/..." value="<?php echo htmlspecialchars(getGDriveLink('transfer_anak') ?? ''); ?>">
          </div>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;margin-top:16px;">Simpan Link Google Drive</button>
      </form>
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

