<?php
require_once __DIR__ . '/db.php';
function safe($v) { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$nama = $_POST['nama'] ?? '';
$alasan = $_POST['alasan'] ?? '';
$sudah_bayar = $_POST['sudah_bayar'] ?? '';

// Recompute requirements server-side to avoid trusting client-provided payload
$requirements = [];
try {
  $db = getDb();
  $stmt = $db->prepare('SELECT content FROM templates WHERE type = :type LIMIT 1');
  $stmt->execute([':type' => 'cancellation']);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row && !empty($row['content'])) {
    $decoded = json_decode($row['content'], true);
    if (is_array($decoded)) $requirements = $decoded;
  }
} catch (Exception $e) {
  // ignore and fallback to defaults
}

if (empty($requirements)) {
  $requirements[] = 'Fotokopi KTP pemohon';
  $requirements[] = 'Fotokopi Kartu Keluarga (KK)';
  $requirements[] = 'Surat permohonan pembatalan yang ditandatangani';
  $requirements[] = 'Fotokopi bukti pendaftaran';
}

if (!empty($alasan) && $alasan === 'sakit') {
  $requirements[] = 'Surat keterangan dokter / rumah sakit yang menjelaskan kondisi kesehatan';
}
if (!empty($alasan) && $alasan === 'meninggal') {
  $requirements[] = 'Akta Kematian dan surat keterangan terkait';
}
if (!empty($sudah_bayar) && $sudah_bayar === 'ya') {
  $requirements[] = 'Bukti pembayaran / kwitansi';
}

$filename = 'persyaratan_pembatalan_' . preg_replace('/[^0-9_]/', '', date('Ymd_His')) . '.doc';
header("Content-Type: application/msword; charset=utf-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><body>";
echo "<h1>Persyaratan Pembatalan Porsi Haji Reguler</h1>";
if ($nama !== '') echo "<p><strong>Nama:</strong> " . safe($nama) . "</p>";
if ($alasan !== '') echo "<p><strong>Alasan:</strong> " . safe($alasan) . "</p>";
if ($sudah_bayar !== '') echo "<p><strong>Sudah Bayar:</strong> " . safe($sudah_bayar) . "</p>";

echo "<h2>Daftar Persyaratan</h2>";
echo "<ul>";
foreach ($requirements as $r) {
  echo '<li>' . safe($r) . '</li>';
}
echo "</ul>";

echo "<p>Catatan: Dokumen dan prosedur bisa berbeda tergantung kebijakan kantor penyelenggara.</p>";
echo "</body></html>";
exit;
