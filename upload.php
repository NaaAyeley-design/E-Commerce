<!DOCTYPE html>
<html>
<head>
    <title>File Upload</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .file-list { border: 1px solid #ddd; padding: 10px; margin-top: 20px; }
        form { margin: 20px 0; }
        input[type="file"] { margin-right: 10px; }
        input[type="submit"] { background-color: #007cba; color: white; padding: 8px 16px; border: none; cursor: pointer; }
        input[type="submit"]:hover { background-color: #005a8b; }
    </style>
</head>
<body>
    <h2>File Upload</h2>
    <?php
    function sanitizeFileName($filename) {
        // Remove any non-alphanumeric characters except dots, hyphens, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $filename);
        // Prevent directory traversal
        $filename = basename($filename);
        return $filename;
    }

    $upload_dir = "uploads/";
    $max_file_size = 64 * 1024 * 1024; // 64MB

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["uploadedFile"])) {
        $file = $_FILES["uploadedFile"];

        // Check for upload errors
        if ($file["error"] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => "File is too large (exceeds server limit)",
                UPLOAD_ERR_FORM_SIZE => "File is too large (exceeds form limit)",
                UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
                UPLOAD_ERR_NO_FILE => "No file was uploaded",
                UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
            ];

            echo "<p class='error'>Upload Error: " .
                (isset($upload_errors[$file["error"]]) ? $upload_errors[$file["error"]] : "Unknown error") .
                "</p>";
        } else {
            // Sanitize filename
            $original_filename = $file["name"];
            $filename = sanitizeFileName($original_filename);

            if (empty($filename)) {
                echo "<p class='error'>Invalid filename. Please use only alphanumeric characters, dots, hyphens, and underscores.</p>";
            } else {
                $target_file = $upload_dir . $filename;

                // Check file size against our limit
                if ($file["size"] > $max_file_size) {
                    echo "<p class='error'>Sorry, your file is too large. Maximum size allowed is " .
                         number_format($max_file_size / (1024 * 1024), 0) . "MB.</p>";
                } else {
                    // Check if file already exists
                    if (file_exists($target_file)) {
                        echo "<p class='error'>Sorry, file already exists. Please rename your file or delete the existing one first.</p>";
                    } else {
                        // Attempt to move uploaded file
                        if (move_uploaded_file($file["tmp_name"], $target_file)) {
                            // Set proper file permissions
                            chmod($target_file, 0644);
                            echo "<p class='success'>File " . htmlspecialchars($filename) . " uploaded successfully.</p>";
                        } else {
                            echo "<p class='error'>Sorry, there was an error uploading your file. Please check directory permissions.</p>";
                        }
                    }
                }
            }
        }
    }
    ?>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="uploadedFile" id="uploadedFile" required>
        <input type="submit" value="Upload File" name="submit">
    </form>

    <div class="file-list">
        <h3>Uploaded Files:</h3>
        <?php
        if (is_dir($upload_dir)) {
            $files = array_diff(scandir($upload_dir), ['.', '..']);
            if (count($files) > 0) {
                // Sort files by modification time (newest first)
                usort($files, function($a, $b) use ($upload_dir) {
                    return filemtime($upload_dir . $b) - filemtime($upload_dir . $a);
                });

                echo "<ul>";
                foreach($files as $file) {
                    $file_path = $upload_dir . $file;
                    if (is_file($file_path)) {
                        $file_size = filesize($file_path);
                        $file_date = date("Y-m-d H:i:s", filemtime($file_path));
                        echo "<li>" .
                             "<a href='" . htmlspecialchars($upload_dir . $file) . "' target='_blank'>" .
                             htmlspecialchars($file) . "</a>" .
                             " <small>(" . number_format($file_size / 1024, 2) . " KB, " .
                             $file_date . ")</small></li>";
                    }
                }
                echo "</ul>";
            } else {
                echo "<p>No files uploaded yet.</p>";
            }
        } else {
            echo "<p class='error'>Upload directory does not exist.</p>";
        }
        ?>
    </div>
</body>
</html>
