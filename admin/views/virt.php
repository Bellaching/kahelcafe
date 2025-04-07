<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = './../../uploads/';

    if (isset($_FILES['image'])) {
        $images = $_FILES['image'];
        for ($i = 0; $i < count($images['name']); $i++) {
            $target_file = $target_dir . basename($images["name"][$i]);

            if (move_uploaded_file($images["tmp_name"][$i], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO virt (content) VALUES (?)");
                $stmt->bind_param("s", $target_file);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM virt WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
}

$result = $conn->query("SELECT * FROM virt");
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

  /* Your custom CSS */
  #carouselExampleIndicators {
        width: 90%;
        height: 300px;
        margin: 20px auto;
        border: 2px solid #ccc;
        border-radius: 10px;
        overflow: hidden;
    }
    #carouselExampleIndicators .carousel-item {
        width: 100%;
        height: 100%;
    }
    #carouselExampleIndicators .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: fill !important;
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
        .uploadModal {
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
                <img class="d-block w-100 h-100" style="object-fit: cover;" src="<?php echo $row['content']; ?>" alt="Slide">

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
                <div class="modal-body d-flex flex-column">
                    <h5 class="mt-3 mx-3">Uploaded Images</h5>
                    <div id="uploadedImages" class="d-flex flex-column mb-3">
                        <?php
                        $result->data_seek(0);
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

<?php $conn->close(); ?>
