<?php
function safe($v) { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$nama = $_POST['nama'] ?? '';
$hubungan = $_POST['hubungan'] ?? '';
$penerima_paspor = $_POST['penerima_paspor'] ?? '';
$requirements = [];
if (!empty($_POST['requirements'])) {
  $decoded = json_decode($_POST['requirements'], true);
  if (is_array($decoded)) $requirements = $decoded;
}

// Generate filename based on hubungan
$hubungan_label = match($hubungan) {
  'suamiistri' => 'suami_istri',
  'anak' => 'anak',
  'saudara' => 'saudara',
  'lainnya' => 'lainnya',
  default => 'umum'
};
$filename = 'persyaratan_pelimpahan_' . $hubungan_label . '_' . preg_replace('/[^0-9_]/', '', date('Ymd_His')) . '.doc';
header("Content-Type: application/msword; charset=utf-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><body>";
echo "<h1>Persyaratan Pelimpahan Porsi Haji Reguler</h1>";
if ($nama !== '') echo "<p><strong>Nama Pemilik Porsi:</strong> " . safe($nama) . "</p>";
if ($hubungan !== '') echo "<p><strong>Hubungan pemberi & penerima:</strong> " . safe($hubungan) . "</p>";
if ($penerima_paspor !== '') echo "<p><strong>Penerima sudah punya paspor:</strong> " . safe($penerima_paspor) . "</p>";

echo "<h2>Daftar Persyaratan</h2>";
echo "<ul>";
foreach ($requirements as $r) {
  echo '<li>' . safe($r) . '</li>';
}
echo "</ul>";

echo "<p>Catatan: Dokumen dan prosedur bisa berbeda tergantung kebijakan kantor penyelenggara.</p>";
echo "</body></html>";
exit;
