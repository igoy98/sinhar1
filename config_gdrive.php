<?php
/**
 * Google Drive configuration for SINHAR
 * Store folder links here that users can download from
 */

// Define Google Drive folder links for each document type
// Format: 'key' => 'https://drive.google.com/drive/folders/FOLDER_ID'
$gdrive_links = [
    'cancellation_sakit' => 'https://docs.google.com/document/d/1MRaVi-oiiXWkixbDGQTKr93RIrgjYCVr/edit?usp=sharing&ouid=115230136242235187965&rtpof=true&sd=true',       // Pembatalan - Sakit
    'cancellation_meninggal' => 'https://docs.google.com/document/d/1M96uUp5FWUwOdUSWVbxaHPsEb7zO7Svu/edit?usp=sharing&ouid=115230136242235187965&rtpof=true&sd=true',       // Pembatalan - Meninggal
    'cancellation_keuangan' => '',       // Pembatalan - Keuangan
    'transfer_wafat' => 'http://drive.google.com/file/d/1VujIT9zQgoDxTsXqu0g9GkEbKWXkVst0/view',       // Pelimpahan - Wafat
    'transfer_sakit' => 'https://docs.google.com/document/d/1VujIT9zQgoDxTsXqu0g9GkEbKWXkVst0/edit?usp=sharing&ouid=115230136242235187965&rtpof=true&sd=true',       // Pelimpahan - Sakit
    'transfer_umum' => '',       // Pelimpahan - Umum (fallback)
];

/**
 * Get Google Drive link for a specific document type
 * @param string $type Document type key
 * @return string|null Google Drive URL or null if not configured
 */
function getGDriveLink($type) {
    global $gdrive_links;
    return $gdrive_links[$type] ?? null;
}

/**
 * Check if a document type has a configured Google Drive link
 * @param string $type Document type key
 * @return bool
 */
function hasGDriveLink($type) {
    $link = getGDriveLink($type);
    return !empty($link);
}

?>
