<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';

// Define maximum file size (45MB in bytes)
define('MAX_FILE_SIZE', 45 * 1024 * 1024); // 45MB
define('MAX_FILENAME_LENGTH', 50); // Maximum filename length

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = './../../uploads/'; // Make sure this directory exists and is writable

    if (isset($_FILES['image'])) {
        $images = $_FILES['image'];
      
        // Loop through each file
        for ($i = 0; $i < count($images['name']); $i++) {
            // Check file size
            if ($images['size'][$i] > MAX_FILE_SIZE) {
                echo "<script>alert('File is too large. Maximum size allowed is 45MB.');</script>";
                continue; // Skip this file
            }

            // Process filename
            $original_filename = basename($images["name"][$i]);
            $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
            
            // Sanitize and trim filename to max length
            $basename = pathinfo($original_filename, PATHINFO_FILENAME);
            $basename = preg_replace("/[^a-zA-Z0-9]/", "_", $basename); // Remove special chars
            $basename = substr($basename, 0, MAX_FILENAME_LENGTH - strlen($imageFileType) - 1);
            $filename = uniqid() . '_' . $basename . '.' . $imageFileType; // Add unique prefix
            $target_file = $target_dir . $filename;

            // Check if it's an image
            $check = getimagesize($images["tmp_name"][$i]);
            if ($check === false) {
                echo "<script>alert('File is not an image.');</script>";
                continue;
            }

            // Only allow certain file formats
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowed_types)) {
                echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');</script>";
                continue;
            }

            // Create upload directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($images["tmp_name"][$i], $target_file)) {
                // Insert into database
                $description = $_POST['description'] ?? 'Uploaded Image';
                $stmt = $conn->prepare("INSERT INTO banners (content, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $target_file, $description);
                if ($stmt->execute()) {
                    echo "<script>alert('Image uploaded successfully.');</script>";
                } else {
                    // Delete the file if DB insert failed
                    unlink($target_file);
                    echo "<script>alert('Error saving image to database.');</script>";
                }
                $stmt->close();
            } else {
                error_log("Upload failed: " . print_r(error_get_last(), true));
                echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        // Handle image deletion
        $delete_id = $_POST['delete_id'];
        
        // First get the file path to delete the physical file
        $stmt = $conn->prepare("SELECT content FROM banners WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                // Delete the physical file
                if (file_exists($row['content'])) {
                    unlink($row['content']);
                }
                echo "<script>alert('Image deleted successfully.');</script>";
            } else {
                echo "<script>alert('Error deleting image.');</script>";
            }
            $stmt->close();
        }
    }
}

// Fetch images from the database
$result = $conn->query("SELECT * FROM banners ORDER BY id DESC"); // Newest first
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel with Summernote</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        #carouselExampleIndicators {
            width: 90%;
            height: 300px;
            margin: 20px auto;
            border: 2px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
        }
        .carousel-item img { 
            object-fit: cover; 
            height: 100%; 
            width: 100%;
        }
        .modal-header { 
            background-color: orange; 
        }
        .modal-button { 
            position: absolute; 
            bottom: 15px; 
            right: 15px; 
            z-index: 10; 
        }
        .image-container {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .delete-button {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 30px;
            cursor: pointer;
        }
        .fa-pen {
            color: #FF902B;
            background-color: #ffffff;
            border-radius: 100%;
            padding: 0.8rem;
        }
        .upload-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .upload-info h6 {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .uploadModal{
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>

    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
            <?php $active = true; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="carousel-item <?php echo $active ? 'active' : ''; ?>">
                    <img class="d-block w-100" src="<?php echo $row['content']; ?>" alt="Slide">
                </div>
                <?php $active = false; ?>
            <?php endwhile; ?>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
        <button type="button" class="btn modal-button" data-toggle="modal" data-target="#uploadModal">
            <i class="fa-solid fa-pen"></i>
        </button>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center text-light" id="uploadModalLabel">Edit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body d-flex flex-column">
                    <div class="upload-info">
                        <h6>Upload Instructions:</h6>
                        <p>- Maximum file size: 45MB</p>
                        <p>- Recommended banner dimensions: 1200px (width) × 400px (height)</p>
                        <p>- The carousel container has a fixed height of 300px</p>
                        <p>The file name should not exceed 50 characters.</p>
                        <p>- Images will be automatically resized to fit the container</p>
                        <p>- For best results, use high-quality images in landscape orientation</p>
                    </div>
                    
                    <h5 class="mt-3 mx-3">Uploaded Images</h5>
                    <div id="uploadedImages" class="d-flex flex-column mb-3">
                        <?php
                        // Fetch images again to display in the modal
                        $result->data_seek(0); // Reset the pointer to the first row
                        while ($row = $result->fetch_assoc()): ?>
                            <div class="image-container mb-2">
                                <img src="<?php echo $row['content']; ?>" alt="Uploaded Image" style="width: 100px; height: 100px; object-fit: cover;">
                                <form action="" method="post" style="display: inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this banner?');">X</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <form action="" method="post" enctype="multipart/form-data" class="d-flex flex-column">
                        <div class="form-group mb-3">
                            <input type="file" name="image[]" class="form-control" multiple required>
                            <small class="form-text text-muted">Max file size: 45MB</small>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                height: 150
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>