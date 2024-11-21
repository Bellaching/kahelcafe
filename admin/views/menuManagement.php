<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="menu.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
</head>
<body>
    <?php 
    ob_start(); 
    include "./../../admin/views/banner.php";
    include "./../../connection/connection.php";

    $itemsPerPage = 6; // Items per page
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $itemsPerPage;
    $selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
        $menuImage = '';
        $menuName = $conn->real_escape_string($_POST['menuName']);
        $menuDescription = $conn->real_escape_string($_POST['menuDescription']);
        $menuCategory = $conn->real_escape_string($_POST['menuCategory']);
        $menuSize = isset($_POST['menuSize']) ? implode(',', $_POST['menuSize']) : '';
        $menuTemperature = isset($_POST['menuTemperature']) ? implode(',', $_POST['menuTemperature']) : '';
        $menuQuantity = intval($_POST['menuQuantity']);
        $menuPrice = floatval($_POST['menuPrice']);
        $productStatus = $conn->real_escape_string($_POST['productStatus']);

        // Handle image upload
        if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
            $target_dir = "././../../uploads/";
            $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);

            if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
                $menuImage = $image_file;
            } else {
                echo "<p class='alert alert-danger'>Error: Failed to upload image.</p>";
            }
        }

        // Insert into database
        $sql = "INSERT INTO menu1 (image, name, description, category, size, temperature, quantity, price, status)
                VALUES ('$menuImage', '$menuName', '$menuDescription', '$menuCategory', '$menuSize', '$menuTemperature', '$menuQuantity', '$menuPrice', '$productStatus')";

        if ($conn->query($sql) === TRUE) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<p class='alert alert-danger'>Error: " . $conn->error . "</p>";
        }
    }

    $totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
    $totalItems = $totalItemsResult->fetch_assoc()['count'] ?? 0;
    $totalPages = ceil($totalItems / $itemsPerPage);

    $sql = "SELECT * FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
    $result = $conn->query($sql);

    ob_end_flush();
    ?>

    <div class="container-fluid mb-5">
        <div class="row mt-5 ms-5 align-items-center">
            <div class="col-12 col-md-8">
                <p class="account-text">Our <span class="management-underline">Menu</span></p>
            </div>
            <div class="col-12 col-md-3 d-flex justify-content-md-end">
                <div class="dropdown">
                    <div class="input-group">
                        <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
                    </div>
                    <button class="btn btn-success add-menu mx-3" data-bs-toggle="modal" data-bs-target="#addMenuModal" style="width: 200px;">+ Add Menu</button>
                </div>
            </div>
        </div>

        <?php include "filter.php"; ?>

        <div class="container">
            <div class="row g-0" id="menu-card-container">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">';
                        echo '<div class="card p-2 rounded-1" style="border: none;">';
                        echo '<div class="img-container" style="overflow: hidden; height: 150px;">';
                        echo '<img src="' . htmlspecialchars($row['image']) . '" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '" style="height: 100%; width: 100%; object-fit: cover;">';
                        echo '</div>';
                        echo '<div class="card-body text-center p-1">';
                        echo '<h5>' . htmlspecialchars($row['name']) . '</h5>';
                        echo '<p class="card-text text-success">₱' . htmlspecialchars($row['price']) . '</p>';
                        echo '<button class="btn btn-delete btn-danger" onclick="confirmDelete(' . intval($row['id']) . ')">Delete</button>';
                        echo '</div></div></div>';
                    }
                } else {
                    echo '<p class="text-center">No menu items found.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Confirm Delete functionality
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
