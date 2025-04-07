<?php
include './../../connection/connection.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Virtual Tour</title>

  <!-- Bootstrap CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

  <style>
    #carouselExampleIndicators {
      width: 100%;
      height: 500px;
      margin: 30px auto;
      border: 2px solid #ccc;
      border-radius: 10px;
      overflow: hidden;
    }

    .carousel-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Customizing dot indicators */
    .carousel-indicators li {
      background-color: #FF6F00; /* Orange color */
    }

    .tour-header {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
    }

    .tour-header span {
      color: #FF6F00; /* Orange color */
      text-decoration: underline;
      text-decoration-thickness: 3px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="tour-header">
    Virtual
    <span class="mt-3">Tour</span>
  </div>

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

    <!-- Dot indicators -->
    <ol class="carousel-indicators">
      <?php
      // Reset the result to get the total number of items for dots
      $result = $conn->query("SELECT * FROM virt");
      $i = 0;
      while ($row = $result->fetch_assoc()): ?>
        <li data-target="#carouselExampleIndicators" data-slide-to="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"></li>
        <?php $i++; ?>
      <?php endwhile; ?>
    </ol>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

<!-- Bootstrap JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
  // Initialize the carousel
  $('#carouselExampleIndicators').carousel({
    interval: 3000,  // Adjust the interval for slide transition (in ms)
  });
</script>

</body>
</html>

<?php $conn->close(); ?>
