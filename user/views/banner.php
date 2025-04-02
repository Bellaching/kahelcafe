<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';
include './../inc/header.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = './../../uploads'; 

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
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
 
</body>
</html>

