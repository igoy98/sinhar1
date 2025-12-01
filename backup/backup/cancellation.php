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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cek Persyaratan Pembatalan - SINHAR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1>Persyaratan Pembatalan Porsi Haji Reguler</h1>
      <nav>
        <a href="index.php">Beranda</a>
        <a href="cancellation.php">Pembatalan</a>
        <a href="transfer.php">Pelimpahan</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Form Cek Persyaratan</h2>
      <form method="post">
        <label>Nama:</label>
        <input type="text" name="nama" placeholder="Masukkan nama pemohon (opsional)">

        <label>Alasan Pembatalan:</label>
        <select name="alasan">
          <option value="">-- Pilih --</option>
          <option value="sakit">Sakit / Tidak Memungkinkan</option>
          <option value="meninggal">Meninggal</option>
          <option value="keuangan">Alasan Keuangan</option>
        </select>

        <label>Apakah sudah melakukan pembayaran penuh?</label>
        <select name="sudah_bayar">
          <option value="">-- Pilih --</option>
          <option value="ya">Ya</option>
          <option value="tidak">Tidak</option>
        </select>

        <button type="submit" class="btn">Cek Persyaratan</button>
      </form>

      <?php if ($result !== null): ?>
        <section class="result">
          <h3>Persyaratan yang perlu dipersiapkan</h3>
          <ul>
              <?php foreach ($result as $item): ?>
                <li><?php echo htmlspecialchars($item); ?></li>
              <?php endforeach; ?>
          </ul>

            <p class="hint">Catatan: Dokumen dan prosedur bisa berbeda tergantung kebijakan kantor penyelenggara. Silakan verifikasi ke kantor Kemenag setempat.</p>

            <form method="post" action="download_cancellation_word.php" target="_blank">
              <input type="hidden" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
              <input type="hidden" name="alasan" value="<?php echo htmlspecialchars($_POST['alasan'] ?? ''); ?>">
              <input type="hidden" name="sudah_bayar" value="<?php echo htmlspecialchars($_POST['sudah_bayar'] ?? ''); ?>">
              <input type="hidden" name="requirements" value="<?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE)); ?>">
              <button type="submit" class="btn">Unduh Persyaratan (.doc)</button>
            </form>
            <form method="post" action="download_cancellation_docx.php" target="_blank" style="display:inline-block;margin-left:8px;">
              <input type="hidden" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
              <input type="hidden" name="alasan" value="<?php echo htmlspecialchars($_POST['alasan'] ?? ''); ?>">
              <input type="hidden" name="sudah_bayar" value="<?php echo htmlspecialchars($_POST['sudah_bayar'] ?? ''); ?>">
              <input type="hidden" name="requirements" value="<?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE)); ?>">
              <button type="submit" class="btn">Unduh Persyaratan (.docx)</button>
            </form>
        </section>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">© SINHAR</div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
