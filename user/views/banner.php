<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';


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
       
    </div>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
 
</body>
</html>

<?php
$conn->close();
?>