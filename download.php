<?php
require_once 'config.php';

$generation_id = $_GET['id'] ?? null;

if (!$generation_id) {
    die('Invalid generation ID');
}

$generations = get_user_generations();
$generation = $generations[$generation_id] ?? null;

if (!$generation) {
    die('Generation not found');
}

// Create ZIP file
$zip = new ZipArchive();
$zip_filename = tempnam(sys_get_temp_dir(), 'gen_') . '.zip';

if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
    die('Failed to create ZIP file');
}

// Add all images to ZIP
foreach ($generation['generated_images'] as $img) {
    $file_path = BASE_DIR . '/' . $img['path'];
    if (file_exists($file_path)) {
        $zip->addFile($file_path, $img['filename']);
    }
}

$zip->close();

// Send ZIP file to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $generation_id . '_images.zip"');
header('Content-Length: ' . filesize($zip_filename));

readfile($zip_filename);
unlink($zip_filename);
exit;
?>
