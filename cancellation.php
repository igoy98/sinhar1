<?php
function getCancellationRequirements($data) {
  $requirements = [];
  // Try to load base template from DB (if available)
  try {
    require_once __DIR__ . '/db.php';
    $db = getDb();
    $stmt = $db->prepare('SELECT content FROM templates WHERE type = :type LIMIT 1');
    $stmt->execute([':type' => 'cancellation']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['content'])) {
      $decoded = json_decode($row['content'], true);
      if (is_array($decoded)) {
        $requirements = $decoded;
      }
    }
  } catch (Exception $e) {
    // ignore and fallback to defaults
  }

  // If DB had no template, use default base list
  if (empty($requirements)) {
    $requirements[] = 'Fotokopi KTP pemohon';
    $requirements[] = 'Fotokopi Kartu Keluarga (KK)';
    $requirements[] = 'Surat permohonan pembatalan yang ditandatangani';
    $requirements[] = 'Fotokopi bukti pendaftaran';
  }

  // Tambahan berdasarkan alasan
  if (!empty($data['alasan']) && $data['alasan'] === 'sakit') {
    $requirements[] = 'Surat keterangan dokter / rumah sakit yang menjelaskan kondisi kesehatan';
  }
  if (!empty($data['alasan']) && $data['alasan'] === 'meninggal') {
    $requirements[] = 'Akta Kematian dan surat keterangan terkait';
  }

  // Jika porsi sudah dibayarkan penuh
  if (!empty($data['sudah_bayar']) && $data['sudah_bayar'] === 'ya') {
    $requirements[] = 'Bukti pembayaran / kwitansi';
  }

  return $requirements;
}

$result = null;
// Ensure DB exists and record checks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = getCancellationRequirements($_POST);

  try {
    require_once __DIR__ . '/db.php';
    $db = getDb();

    $stmt = $db->prepare('INSERT INTO checks (type, subject, payload, requirements) VALUES (:type, :subject, :payload, :requirements)');
    $payload = json_encode([
      'nama' => $_POST['nama'] ?? null,
      'alasan' => $_POST['alasan'] ?? null,
      'sudah_bayar' => $_POST['sudah_bayar'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    $stmt->execute([
      ':type' => 'cancellation',
      ':subject' => $_POST['nama'] ?? null,
      ':payload' => $payload,
      ':requirements' => json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);
  } catch (Exception $e) {
    // If DB fails, silently ignore to not break user flow; in production log this.
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Cek persyaratan pembatalan porsi haji reguler dengan mudah">
  <title>Pembatalan - Kemenag Nganjuk SINHAR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
            <div class="header-content">
        <div class="header-logo">
          <img src="assets/img/logo-kemenag.png" alt="Kementerian Agama">
        </div>
        <h1>Kemenag Nganjuk - Sistem Informasi Haji Reguler (SINHAR)</h1>
        <nav>
          <a href="index.php">Beranda</a>
          <a href="cancellation.php">Pembatalan</a>
          <a href="transfer.php">Pelimpahan</a>
          <a href="admin.php">Admin</a>
        </nav>
      </div>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Cek Persyaratan Pembatalan Porsi</h2>
      <form method="post" style="background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <div class="form-group">
          <label for="nama">Nama:</label>
          <input type="text" id="nama" name="nama" placeholder="Masukkan nama pemohon (opsional)">
        </div>

        <div class="form-group">
          <label for="alasan">Alasan Pembatalan:</label>
          <select id="alasan" name="alasan">
            <option value="">-- Pilih alasan --</option>
            <option value="sakit">Sakit / Tidak Memungkinkan</option>
            <option value="meninggal">Meninggal</option>
            <option value="meninggal">Sebab Lain</option>
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

          <p class="hint">📌 Catatan: Dokumen dan prosedur bisa berbeda tergantung kebijakan kantor penyelenggara. Silakan verifikasi ke kantor Kemenag setempat.</p>

          <div class="btn-group" style="margin-top:20px;">
            <?php
              require_once __DIR__ . '/config_gdrive.php';
              $alasan_key = match($_POST['alasan'] ?? '') {
                'sakit' => 'cancellation_sakit',
                'meninggal' => 'cancellation_meninggal',
                'keuangan' => 'cancellation_keuangan',
                default => 'cancellation_umum'
              };
              $gdrive_url = getGDriveLink($alasan_key);
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



