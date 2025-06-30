<?php

class DKL_Rental_Image_Processor {
    
    public static function processDocument($file, $type = 'document') {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_file', 'Invalid file upload');
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Only JPEG and PNG files are allowed');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', 'File size must be less than 5MB');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $dkl_dir = $upload_dir['basedir'] . '/dkl-rental/documents';
        
        if (!file_exists($dkl_dir)) {
            wp_mkdir_p($dkl_dir);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid($type . '_') . '.' . $extension;
        $filepath = $dkl_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/dkl-rental/documents/' . $filename;
        
        try {
            // Process image with compression and watermark
            $result = self::compressAndWatermarkImage($file['tmp_name'], $filepath, $file['type']);
            
            if ($result) {
                return array(
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'url' => $file_url
                );
            } else {
                return new WP_Error('processing_failed', 'Failed to process image');
            }
            
        } catch (Exception $e) {
            return new WP_Error('processing_error', $e->getMessage());
        }
    }
    
    public static function processVehiclePhotos($files) {
        $processed_photos = array();
        
        if (empty($files) || !is_array($files)) {
            return $processed_photos;
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $photos_dir = $upload_dir['basedir'] . '/dkl-rental/vehicle-photos';
        
        if (!file_exists($photos_dir)) {
            wp_mkdir_p($photos_dir);
        }
        
        foreach ($files as $index => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $result = self::processVehiclePhoto($file, $index);
                if (!is_wp_error($result)) {
                    $processed_photos[$index] = $result;
                }
            }
        }
        
        return $processed_photos;
    }
    
    public static function processVehiclePhoto($file, $index) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_file', 'Invalid file upload');
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Only JPEG and PNG files are allowed');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $photos_dir = $upload_dir['basedir'] . '/dkl-rental/vehicle-photos';
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vehicle_' . $index . '_') . '.' . $extension;
        $filepath = $photos_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/dkl-rental/vehicle-photos/' . $filename;
        
        try {
            // Process image with compression
            $result = self::compressImage($file['tmp_name'], $filepath, $file['type']);
            
            if ($result) {
                return array(
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'url' => $file_url
                );
            } else {
                return new WP_Error('processing_failed', 'Failed to process vehicle photo');
            }
            
        } catch (Exception $e) {
            return new WP_Error('processing_error', $e->getMessage());
        }
    }
    
    public static function processSignature($signature_data) {
        if (empty($signature_data) || strpos($signature_data, 'data:image/png;base64,') !== 0) {
            return new WP_Error('invalid_signature', 'Invalid signature data');
        }
        
        // Decode base64 data
        $image_data = base64_decode(str_replace('data:image/png;base64,', '', $signature_data));
        
        if (!$image_data) {
            return new WP_Error('decode_failed', 'Failed to decode signature data');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $signatures_dir = $upload_dir['basedir'] . '/dkl-rental/signatures';
        
        if (!file_exists($signatures_dir)) {
            wp_mkdir_p($signatures_dir);
        }
        
        // Generate unique filename
        $filename = uniqid('signature_') . '.png';
        $filepath = $signatures_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/dkl-rental/signatures/' . $filename;
        
        // Save signature
        if (file_put_contents($filepath, $image_data)) {
            return array(
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => $file_url
            );
        }
        
        return new WP_Error('save_failed', 'Failed to save signature');
    }
    
    private static function compressAndWatermarkImage($source_path, $destination_path, $mime_type) {
        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source_path);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calculate new dimensions (max 1200px width)
        $max_width = 1200;
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height * $max_width) / $width;
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($mime_type === 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefill($new_image, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Add watermark
        self::addWatermark($new_image, $new_width, $new_height);
        
        // Save image
        $result = false;
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $result = imagejpeg($new_image, $destination_path, 85); // 85% quality
                break;
            case 'image/png':
                $result = imagepng($new_image, $destination_path, 6); // Compression level 6
                break;
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($new_image);
        
        return $result;
    }
    
    private static function compressImage($source_path, $destination_path, $mime_type) {
        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source_path);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calculate new dimensions (max 800px width for vehicle photos)
        $max_width = 800;
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height * $max_width) / $width;
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($mime_type === 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefill($new_image, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Save image
        $result = false;
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $result = imagejpeg($new_image, $destination_path, 85); // 85% quality
                break;
            case 'image/png':
                $result = imagepng($new_image, $destination_path, 6); // Compression level 6
                break;
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($new_image);
        
        return $result;
    }
    
    private static function addWatermark($image, $width, $height) {
        // Add "AK13 - Drive KL" watermark
        $watermark_text = 'AK13 - Drive KL';
        $font_size = max(12, $width / 50); // Responsive font size
        
        // Calculate text position (bottom right)
        $text_box = imagettfbbox($font_size, 0, DKL_RENTAL_PLUGIN_PATH . 'assets/fonts/arial.ttf', $watermark_text);
        $text_width = $text_box[4] - $text_box[0];
        $text_height = $text_box[1] - $text_box[7];
        
        $x = $width - $text_width - 20;
        $y = $height - 20;
        
        // Add semi-transparent background
        $bg_color = imagecolorallocatealpha($image, 0, 0, 0, 50);
        imagefilledrectangle($image, $x - 10, $y - $text_height - 5, $x + $text_width + 10, $y + 5, $bg_color);
        
        // Add text
        $text_color = imagecolorallocate($image, 255, 255, 255);
        imagettftext($image, $font_size, 0, $x, $y, $text_color, DKL_RENTAL_PLUGIN_PATH . 'assets/fonts/arial.ttf', $watermark_text);
    }
}