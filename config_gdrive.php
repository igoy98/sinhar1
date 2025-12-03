<?php
/**
 * Google Drive configuration for SINHAR
 * Store folder links here that users can download from
 */

// Define Google Drive folder links for each document type
// Format: 'key' => 'https://drive.google.com/drive/folders/FOLDER_ID'
$gdrive_links = [
    'cancellation_sakit' => 'https://drive.google.com/file/d/1MRaVi-oiiXWkixbDGQTKr93RIrgjYCVr/view',       // Pembatalan - Sakit / Sebab Lain
    'cancellation_meninggal' => 'https://drive.google.com/file/d/1M96uUp5FWUwOdUSWVbxaHPsEb7zO7Svu/view',       // Pembatalan - Wafat
    'cancellation_sebab_lain' => 'https://drive.google.com/file/d/1MRaVi-oiiXWkixbDGQTKr93RIrgjYCVr/view',       // Pembatalan - Sebab Lain
    'transfer_sakit' => 'https://drive.google.com/file/d/1MjaiLV8n57TbimPSNouUeiFGVt1O5_rd/view',       // Pelimpahan - Sakit
    'transfer_wafat' => 'https://drive.google.com/file/d/1VujIT9zQgoDxTsXqu0g9GkEbKWXkVst0/view',       // Pelimpahan - Wafat
    'transfer_umum' => '',       // Pelimpahan - Umum (fallback)
    'cancellation_umum' => '',       // Pembatalan - Umum (fallback)
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
