<?php
// Route root to a proper homepage. Previously this redirected to cancellation.php
// which made the "Beranda" nav link effectively non-distinct. Point to
// `beranda.php` instead so users can click the Beranda link and see a landing page.
header('Location: beranda.php');
exit;

?>