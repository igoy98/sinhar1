<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

function safe($v) { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$nama = $_POST['nama'] ?? '';
$alasan = $_POST['alasan'] ?? '';
$sudah_bayar = $_POST['sudah_bayar'] ?? '';
$requirements = [];
if (!empty($_POST['requirements'])) {
  $decoded = json_decode($_POST['requirements'], true);
  if (is_array($decoded)) $requirements = $decoded;
}

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addTitle('Persyaratan Pembatalan Porsi Haji Reguler', 1);
if ($nama !== '') $section->addText('Nama: ' . $nama);
if ($alasan !== '') $section->addText('Alasan: ' . $alasan);
if ($sudah_bayar !== '') $section->addText('Sudah Bayar: ' . $sudah_bayar);
$section->addTextBreak(1);
$section->addText('Daftar Persyaratan:');
foreach ($requirements as $r) $section->addListItem($r, 0, null, 'multilevel');

// Use admin HTML template if available
try {
  $db = getDb();
  $stmt = $db->prepare('SELECT content FROM templates WHERE type = :type LIMIT 1');
  $stmt->execute([':type' => 'cancellation_doc']);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row && strlen(trim($row['content'])) > 0) {
    $reqHtml = '<ul>';
    foreach ($requirements as $r) $reqHtml .= '<li>' . safe($r) . '</li>';
    $reqHtml .= '</ul>';
    $html = str_replace(['{{NAMA}}','{{ALASAN}}','{{SUDAH_BAYAR}}','{{REQUIREMENTS}}'], [safe($nama), safe($alasan), safe($sudah_bayar), $reqHtml], $row['content']);
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    Html::addHtml($section, $html, false, false);
  }
} catch (Exception $e) { }

$filename = 'persyaratan_pembatalan_' . date('Ymd_His') . '.docx';
$tmp = tempnam(sys_get_temp_dir(), 'sinhar_') . '.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($tmp);

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
readfile($tmp);
@unlink($tmp);
exit;
