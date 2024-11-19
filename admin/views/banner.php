<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
    $target_dir = './../uploads'; // Replace with your actual directory path

    if (isset($_FILES['image'])) {
        $images = $_FILES['image'];
      
        // Loop through each file
        for ($i = 0; $i < count($images['name']); $i++) {
            $target_file = $target_dir . basename($images["name"][$i]);

            // Move the uploaded file to the target directory
            if (move_uploaded_file($images["tmp_name"][$i], $target_file)) {
                // Insert into database
                $description = $_POST['description'] ?? 'Uploaded Image'; // Change 'content' to 'description'
                $stmt = $conn->prepare("INSERT INTO banners (content, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $target_file, $description);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        // Handle image deletion
        $delete_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch images from the database
$result = $conn->query("SELECT * FROM banners");
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
            line-height: 30px; /* Center the text */
            cursor: pointer;
        }
        .fa-pen {
            color: #FF902B;
            background-color: #ffffff;
            border-radius: 100%;
            padding: 0.8rem;
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
                    <h5 class="modal-title text-center text-light" id="uploadModalLabel">Edit Banner</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-3">Uploaded Images</h5>
                    <div id="uploadedImages">
                        <?php
                        // Fetch images again to display in the modal
                        $result->data_seek(0); // Reset the pointer to the first row
                        while ($row = $result->fetch_assoc()): ?>
                            <div class="image-container">
                                <img src="<?php echo $row['content']; ?>" alt="Uploaded Image" style="width: 100px; height: 100px; object-fit: cover;">
                                <form action="" method="post" style="display: inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this banner?');">X</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="image[]" class="form-control" multiple required> <!-- Allow multiple files -->
                        </div>
                        <!-- <div class="form-group">
                            <label for="description">Description</label> Change label to 'Description' 
                            <textarea id="summernote" name="description"></textarea>  Change 'content' to 'description' 
                        </div> -->
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                height: 150 // Set the height of the editor
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
