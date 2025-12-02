<?php
function getTransferRequirements($data) {
  $requirements = [];
  // Try to load base template from DB
  try {
    require_once __DIR__ . '/db.php';
    $db = getDb();
    $stmt = $db->prepare('SELECT content FROM templates WHERE type = :type LIMIT 1');
    $stmt->execute([':type' => 'transfer']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['content'])) {
      $decoded = json_decode($row['content'], true);
      if (is_array($decoded)) {
        $requirements = $decoded;
      }
    }
  } catch (Exception $e) {
    // ignore, fallback to defaults
  }

  if (empty($requirements)) {
    $requirements[] = 'Fotokopi KTP pemilik porsi (pemberi)';
    $requirements[] = 'Fotokopi KTP penerima pelimpahan';
    $requirements[] = 'Fotokopi Kartu Keluarga (KK) atau bukti hubungan keluarga';
    $requirements[] = 'Surat permohonan pelimpahan yang ditandatangani oleh pemberi porsi';
    $requirements[] = 'Surat kuasa jika dikuasakan';
    $requirements[] = 'Fotokopi bukti pendaftaran';
  }

  if (!empty($data['hubungan']) && $data['hubungan'] === 'suamiistri') {
    $requirements[] = 'Fotokopi buku nikah (jika pelimpahan kepada pasangan)';
  }

  if (!empty($data['penerima_paspor']) && $data['penerima_paspor'] === 'ya') {
    $requirements[] = 'Paspor penerima (jika sudah tersedia)';
  }

  return $requirements;
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = getTransferRequirements($_POST);

  try {
    require_once __DIR__ . '/db.php';
    $db = getDb();

    $stmt = $db->prepare('INSERT INTO checks (type, subject, payload, requirements) VALUES (:type, :subject, :payload, :requirements)');
    $payload = json_encode([
      'nama' => $_POST['nama'] ?? null,
      'hubungan' => $_POST['hubungan'] ?? null,
      'penerima_paspor' => $_POST['penerima_paspor'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    $stmt->execute([
      ':type' => 'transfer',
      ':subject' => $_POST['nama'] ?? null,
      ':payload' => $payload,
      ':requirements' => json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);
  } catch (Exception $e) {
    // ignore DB errors for now
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Cek persyaratan pelimpahan porsi haji reguler dengan mudah">
  <title>Cek Pelimpahan - SINHAR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="header-content">
        <div class="header-logo">
          <img src="assets/img/logo-kemenag.png" alt="Kementerian Agama">
        </div>
        <div class="header-title-group">
          <h1>Sistem Informasi Haji Reguler (SINHAR)</h1>
          <nav>
            <a href="index.php">Beranda</a>
            <a href="cancellation.php">Pembatalan</a>
            <a href="transfer.php">Pelimpahan</a>
            <a href="admin.php">Admin</a>
          </nav>
        </div>
      </div>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Cek Persyaratan Pelimpahan Porsi</h2>
      <form method="post" style="background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <div class="form-group">
          <label for="nama">Nama Pemilik Porsi:</label>
          <input type="text" id="nama" name="nama" placeholder="Masukkan nama pemilik (opsional)">
        </div>

        <div class="form-group">
          <label for="hubungan">Hubungan pemberi dan penerima:</label>
          <select id="hubungan" name="hubungan">
            <option value="">-- Pilih hubungan --</option>
            <option value="wafat">Wafat</option>
            <option value="sakit">Sakit Permanen</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Cek Persyaratan</button>
      </form>

      <?php if ($result !== null): ?>
        <section class="result">
          <h3>✓ Persyaratan yang Perlu Dipersiapkan</h3>
          <ul>
            <?php foreach ($result as $item): ?>
              <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
          </ul>

          <p class="hint">📌 Catatan: Beberapa dokumen mungkin perlu pengesahan atau verifikasi lebih lanjut di kantor penyelenggara.</p>

          <div class="btn-group" style="margin-top:20px;">
            
            <?php
              require_once __DIR__ . '/config_gdrive.php';
              $hubungan_key = match($_POST['hubungan'] ?? '') {
                'suamiistri' => 'transfer_suami_istri',
                'anak' => 'transfer_anak',
                'saudara' => 'transfer_saudara',
                'lainnya' => 'transfer_lainnya',
                default => 'transfer_umum'
              };
              $gdrive_url = getGDriveLink($hubungan_key);
              if (!empty($gdrive_url)):
            ?>
            <a href="<?php echo htmlspecialchars($gdrive_url); ?>" target="_blank" class="btn" style="background:#4285f4;">☁️ Buka Google Drive</a>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2025 SINHAR - Sistem Informasi Haji Reguler. Semua hak cipta dilindungi.</p>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>

