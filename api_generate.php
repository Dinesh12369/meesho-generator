<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user has credits
if (get_remaining_credits() <= 0) {
    json_response(false, ['error' => 'No credits remaining'], 403);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['error' => 'Invalid request method'], 405);
}

try {
    // Validate uploaded image
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_response(false, ['error' => 'No image uploaded or upload error'], 400);
    }

    // Validate categories
    $category_ids = isset($_POST['category_ids']) ? json_decode($_POST['category_ids'], true) : [];
    $category_names = isset($_POST['category_names']) ? json_decode($_POST['category_names'], true) : [];
    
    if (empty($category_ids) || count($category_ids) > MAX_CATEGORIES) {
        json_response(false, ['error' => 'Invalid number of categories (1-' . MAX_CATEGORIES . ' allowed)'], 400);
    }

    // Get form parameters
    $num_variants = isset($_POST['num_variants']) ? (int)$_POST['num_variants'] : 50;
    $target_size_kb = isset($_POST['target_size_kb']) ? (int)$_POST['target_size_kb'] : DEFAULT_IMAGE_SIZE_KB;
    $add_discount_tag = isset($_POST['add_discount_tag']) && $_POST['add_discount_tag'] === 'true';

    // Validate num_variants
    if ($num_variants < 1 || $num_variants > MAX_VARIANTS_PER_GENERATION) {
        json_response(false, ['error' => 'Invalid number of variants (1-' . MAX_VARIANTS_PER_GENERATION . ' allowed)'], 400);
    }

    // Generate unique ID
    $generation_id = uniqid('gen_', true);
    $output_folder = GENERATED_FOLDER . '/' . $generation_id;
    
    // Create output directory
    if (!mkdir($output_folder, 0755, true)) {
        json_response(false, ['error' => 'Failed to create output directory'], 500);
    }

    // Move uploaded file
    $image_file = $_FILES['image'];
    $original_filename = basename($image_file['name']);
    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $original_filename);
    $base_image_path = $output_folder . '/original_' . $safe_filename;
    
    if (!move_uploaded_file($image_file['tmp_name'], $base_image_path)) {
        json_response(false, ['error' => 'Failed to save uploaded image'], 500);
    }

    // Load and validate image
    $image_info = getimagesize($base_image_path);
    if ($image_info === false) {
        unlink($base_image_path);
        rmdir($output_folder);
        json_response(false, ['error' => 'Invalid image file'], 400);
    }

    // Load base image
    $mime_type = $image_info['mime'];
    switch ($mime_type) {
        case 'image/jpeg':
            $base_image = imagecreatefromjpeg($base_image_path);
            break;
        case 'image/png':
            $base_image = imagecreatefrompng($base_image_path);
            break;
        case 'image/gif':
            $base_image = imagecreatefromgif($base_image_path);
            break;
        case 'image/webp':
            $base_image = imagecreatefromwebp($base_image_path);
            break;
        default:
            unlink($base_image_path);
            rmdir($output_folder);
            json_response(false, ['error' => 'Unsupported image format'], 400);
    }

    if ($base_image === false) {
        unlink($base_image_path);
        rmdir($output_folder);
        json_response(false, ['error' => 'Failed to load image'], 500);
    }

    // Get original dimensions
    $orig_width = imagesx($base_image);
    $orig_height = imagesy($base_image);

    // Gradient colors for borders
    $gradient_colors = [
        ['start' => [255, 20, 147], 'end' => [138, 43, 226]], // Pink to Purple
        ['start' => [0, 191, 255], 'end' => [0, 255, 255]], // Blue to Cyan
        ['start' => [255, 140, 0], 'end' => [255, 20, 147]], // Orange to Pink
        ['start' => [138, 43, 226], 'end' => [0, 0, 255]], // Purple to Blue
        ['start' => [255, 0, 0], 'end' => [255, 165, 0]], // Red to Orange
        ['start' => [0, 255, 0], 'end' => [0, 0, 255]], // Green to Blue
        ['start' => [255, 255, 0], 'end' => [255, 20, 147]], // Yellow to Pink
    ];

    // Load discount tags if enabled
    $discount_tags = [];
    if ($add_discount_tag && is_dir(DISCOUNT_TAGS_DIR)) {
        $tag_files = glob(DISCOUNT_TAGS_DIR . '/*.{png,jpg,jpeg}', GLOB_BRACE);
        foreach ($tag_files as $tag_file) {
            $tag_img = null;
            $tag_info = getimagesize($tag_file);
            if ($tag_info) {
                switch ($tag_info['mime']) {
                    case 'image/png':
                        $tag_img = imagecreatefrompng($tag_file);
                        break;
                    case 'image/jpeg':
                        $tag_img = imagecreatefromjpeg($tag_file);
                        break;
                }
                if ($tag_img) {
                    $discount_tags[] = $tag_img;
                }
            }
        }
    }

    // Generate variants
    $generated_images = [];
    for ($i = 1; $i <= $num_variants; $i++) {
        // Create copy of base image
        $variant = imagecreatetruecolor($orig_width, $orig_height);
        imagecopy($variant, $base_image, 0, 0, 0, 0, $orig_width, $orig_height);

        // Add gradient border
        $border_width = rand(5, 30);
        $gradient = $gradient_colors[array_rand($gradient_colors)];
        
        // Draw gradient border
        for ($b = 0; $b < $border_width; $b++) {
            $progress = $b / $border_width;
            $r = (int)($gradient['start'][0] + ($gradient['end'][0] - $gradient['start'][0]) * $progress);
            $g = (int)($gradient['start'][1] + ($gradient['end'][1] - $gradient['start'][1]) * $progress);
            $bl = (int)($gradient['start'][2] + ($gradient['end'][2] - $gradient['start'][2]) * $progress);
            
            $color = imagecolorallocate($variant, $r, $g, $bl);
            
            // Top
            imagefilledrectangle($variant, $b, $b, $orig_width - $b - 1, $b, $color);
            // Bottom
            imagefilledrectangle($variant, $b, $orig_height - $b - 1, $orig_width - $b - 1, $orig_height - $b - 1, $color);
            // Left
            imagefilledrectangle($variant, $b, $b, $b, $orig_height - $b - 1, $color);
            // Right
            imagefilledrectangle($variant, $orig_width - $b - 1, $b, $orig_width - $b - 1, $orig_height - $b - 1, $color);
        }

        // Add discount tag if enabled and available
        if ($add_discount_tag && !empty($discount_tags)) {
            $tag = $discount_tags[array_rand($discount_tags)];
            $tag_width = imagesx($tag);
            $tag_height = imagesy($tag);
            
            // Scale tag to fit (max 20% of image width)
            $max_tag_width = (int)($orig_width * 0.2);
            if ($tag_width > $max_tag_width) {
                $scale = $max_tag_width / $tag_width;
                $new_tag_width = $max_tag_width;
                $new_tag_height = (int)($tag_height * $scale);
            } else {
                $new_tag_width = $tag_width;
                $new_tag_height = $tag_height;
            }
            
            // Position in top-left corner
            $tag_x = 10;
            $tag_y = 10;
            
            imagecopyresampled($variant, $tag, $tag_x, $tag_y, 0, 0, 
                             $new_tag_width, $new_tag_height, $tag_width, $tag_height);
        }

        // Save variant with compression
        $variant_filename = sprintf('variant_%03d.jpg', $i);
        $variant_path = $output_folder . '/' . $variant_filename;
        
        // Try different quality levels to hit target size
        $quality = 85;
        $max_attempts = 5;
        $attempt = 0;
        
        do {
            ob_start();
            imagejpeg($variant, null, $quality);
            $image_data = ob_get_clean();
            $current_size = strlen($image_data) / 1024; // KB
            
            if ($current_size <= $target_size_kb * 1.1) { // 10% tolerance
                file_put_contents($variant_path, $image_data);
                break;
            }
            
            $quality -= 5;
            $attempt++;
        } while ($attempt < $max_attempts && $quality > 50);
        
        // If still too large, save at current quality
        if (!file_exists($variant_path)) {
            imagejpeg($variant, $variant_path, $quality);
        }
        
        imagedestroy($variant);
        
        $file_size = filesize($variant_path);
        $generated_images[] = [
            'filename' => $variant_filename,
            'path' => str_replace(BASE_DIR . '/', '', $variant_path),
            'size_kb' => round($file_size / 1024, 2),
            'variant_number' => $i
        ];
    }

    // Clean up
    imagedestroy($base_image);
    foreach ($discount_tags as $tag) {
        imagedestroy($tag);
    }

    // Use credit
    use_credit();

    // Save generation data
    $generation_data = [
        'generation_id' => $generation_id,
        'timestamp' => date('Y-m-d H:i:s'),
        'category_ids' => $category_ids,
        'category_names' => $category_names,
        'category_name' => $category_names[0] ?? 'Product',
        'num_variants' => $num_variants,
        'target_size_kb' => $target_size_kb,
        'add_discount_tag' => $add_discount_tag,
        'generated_images' => $generated_images,
        'output_folder' => $output_folder
    ];

    add_generation($generation_id, $generation_data);

    // Return success response
    json_response(true, [
        'message' => "Successfully generated {$num_variants} image variants!",
        'generation_id' => $generation_id,
        'generated_images' => $generated_images,
        'credits_remaining' => get_remaining_credits(),
        'output_folder' => str_replace(BASE_DIR . '/', '', $output_folder)
    ]);

} catch (Exception $e) {
    error_log("Generation error: " . $e->getMessage());
    json_response(false, ['error' => 'An error occurred during generation: ' . $e->getMessage()], 500);
}
?>
