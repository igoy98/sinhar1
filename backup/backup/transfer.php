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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cek Persyaratan Pelimpahan - SINHAR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1>Persyaratan Pelimpahan Porsi Haji Reguler</h1>
      <nav>
        <a href="index.php">Beranda</a>
        <a href="cancellation.php">Pembatalan</a>
        <a href="transfer.php">Pelimpahan</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Form Cek Persyaratan Pelimpahan</h2>
      <form method="post">
        <label>Nama Pemilik Porsi:</label>
        <input type="text" name="nama" placeholder="Masukkan nama pemilik (opsional)">

        <label>Hubungan pemberi dan penerima:</label>
        <select name="hubungan">
          <option value="">-- Pilih --</option>
          <option value="suamiistri">Suami / Istri</option>
          <option value="anak">Anak</option>
          <option value="saudara">Saudara</option>
          <option value="lainnya">Lainnya</option>
        </select>

        <label>Apakah penerima sudah memiliki paspor?</label>
        <select name="penerima_paspor">
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
          <p class="hint">Catatan: Beberapa dokumen mungkin perlu pengesahan atau verifikasi lebih lanjut di kantor penyelenggara.</p>
        </section>
      <?php endif; ?>
    </section>
  </main>
                    <form method="post" action="download_transfer_word.php" target="_blank">
                      <input type="hidden" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                      <input type="hidden" name="hubungan" value="<?php echo htmlspecialchars($_POST['hubungan'] ?? ''); ?>">
                      <input type="hidden" name="penerima_paspor" value="<?php echo htmlspecialchars($_POST['penerima_paspor'] ?? ''); ?>">
                      <input type="hidden" name="requirements" value="<?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE)); ?>">
                      <button type="submit" class="btn">Unduh Persyaratan (.doc)</button>
                    </form>
                    <form method="post" action="download_transfer_docx.php" target="_blank" style="display:inline-block;margin-left:8px;">
                      <input type="hidden" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                      <input type="hidden" name="hubungan" value="<?php echo htmlspecialchars($_POST['hubungan'] ?? ''); ?>">
                      <input type="hidden" name="penerima_paspor" value="<?php echo htmlspecialchars($_POST['penerima_paspor'] ?? ''); ?>">
                      <input type="hidden" name="requirements" value="<?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE)); ?>">
                      <button type="submit" class="btn">Unduh Persyaratan (.docx)</button>
                    </form>

  <footer class="site-footer">
    <div class="container">© SINHAR</div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
