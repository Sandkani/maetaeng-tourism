<?php
/**
 * Helper function to re-organize the $_FILES array for multiple file uploads.
 * This is necessary because PHP's default file array structure for multiple files is not easy to loop through.
 *
 * @param array $file_post The $_FILES array from the form submission.
 * @return array The re-organized array where each element represents a single file.
 */
function reArrayFiles(&$file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
}

/**
 * Handles the upload of a media file and inserts a record into the database.
 * This function now accepts a single file array from reArrayFiles().
 *
 * @param mysqli $conn The database connection object.
 * @param int $location_id The ID of the location to associate with the media.
 * @param array $file The array containing a single file's data.
 * @param string $upload_dir The destination directory for the uploaded file.
 * @return bool True on successful upload and database insert, false otherwise.
 */
function handle_media_upload($conn, $location_id, $file, $upload_dir) {
    if ($file['error'] == UPLOAD_ERR_OK) {
        $file_name = uniqid() . '-' . basename($file['name']);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (strpos($mime_type, 'image') !== false) {
            $media_type = 'image';
            $subdirectory = 'image/';
        } elseif (strpos($mime_type, 'video') !== false) {
            $media_type = 'video';
            $subdirectory = 'video/';
        } elseif (strpos(strtolower($mime_type), 'audio') !== false) {
            $media_type = 'audio';
            $subdirectory = 'audio/';
        } else {
            return false;
        }

        $file_path = $upload_dir . $subdirectory . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = 'admin/uploads/' . $subdirectory . $file_name;
            
            $stmt_media = $conn->prepare("INSERT INTO media (location_id, media_type, url) VALUES (?, ?, ?)");
            if ($stmt_media) {
                $stmt_media->bind_param("iss", $location_id, $media_type, $file_url);
                $stmt_media->execute();
                $stmt_media->close();
            } else {
                error_log("Failed to prepare media insert statement: " . $conn->error);
            }
            return true;
        } else {
            error_log("Failed to move uploaded file to: " . $file_path);
        }
    }
    return false;
}
?>