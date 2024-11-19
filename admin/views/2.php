<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        .account-text {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .management-underline {
            position: relative;
        }
        .management-underline::after {
            content: '';
            position: absolute;
            bottom: -10px; 
            left: 0;
            width: 100%;
            height: 5px; 
            background-color: #FC8E29;
            border-radius: 3px;
        }
        .menu-card {
            border: 1px solid #e0e0e0;
            border-radius: 0.5rem;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .menu-card:hover {
            transform: scale(1.05);
        }
        .btn-sm {
            background-color: #FF902B !important;
            border-radius: 3rem !important;
            color: #ffff !important;
        }

        .add-menu { }

        #search-icon {
            position: absolute; /* Positions the icon absolutely */
            left: 10px; /* Adjusts the icon's position inside the input */
            top: 50%; /* Centers the icon vertically */
            transform: translateY(-50%); /* Adjusts vertical alignment */
            pointer-events: none; /* Allows clicks to pass through to the input */
        }

        .input-group {
            position: relative; /* Makes the input group a positioning context */
        }

        .dropdown {
            display: flex;
            flex-direction: row;
        }

        .btn-delete {
            border-radius: 3rem !important;
            padding: 0.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .category-list {
                display: none; /* Hide category list by default */
            }
            #categoryFilter {
                display: block; /* Show select in mobile */
            }
        }
        @media (min-width: 769px) {
            .category-list {
                display: block; /* Show category list in larger screens */
            }
           .categoryFilters {
                display: none; /* Hide select in larger screens */
            }
        }

        .btn-light {
            border: none;
            background-color: #ffff !important; /* Remove background color */
            color: #000; /* Set default text color */
        }
        .btn-light.active {
            color: #FF902B !important; /* Active button color */
            color: 
        }
        .btn-update {
            background-color: #FF902B important;
        }

        .menu-card {  
            margin: 0.5rem;
        }

        .add-menu {
            border-radius: 6rem !important;
        }

        .search-box {
            background-color: #F1F1F1 !important;
            border-radius: 6rem !important;
            padding: 1.3rem !important;
        }

        .container-s_add {
            width: 4rem;
        }

        .page-link {
            background-color: #FF902B;
        }
    </style>
</head>
<body>

<?php 
ob_start(); // Start output buffering

include "./../../admin/views/banner.php";
include "./../../connection/connection.php";

// Pagination settings
$itemsPerPage = 6; // Change this to the number of items you want per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;

// Initialize filter variables
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Initialize error messages
$errorMessages = [];
$successMessage = "";

// Handle form submission for adding menu items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
    $menuImage = "";
    if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
        $target_dir = "./uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            $errorMessages[] = "Failed to upload image.";
        }
    }

    $menuName = trim($_POST['menuName']);
    $menuDescription = trim($_POST['menuDescription']);
    $menuCategory = trim($_POST['menuCategory']);
    $menuSize = isset($_POST['menuSize']) ? implode(',', $_POST['menuSize']) : '';
    $menuTemperature = isset($_POST['menuTemperature']) ? implode(',', $_POST['menuTemperature']) : '';
    $menuQuantity = intval($_POST['menuQuantity']);
    $menuPrice = floatval($_POST['menuPrice']);
    $productStatus = trim($_POST['productStatus']);

    // Validation
    if (empty($menuName)) {
        $errorMessages[] = "Menu name is required.";
    }
    if (empty($menuDescription)) {
        $errorMessages[] = "Menu description is required.";
    }
    if (empty($menuCategory)) {
        $errorMessages[] = "Menu category is required.";
    }
    if ($menuQuantity <= 0) {
        $errorMessages[] = "Menu quantity must be a positive number.";
    }
    if ($menuPrice <= 0) {
        $errorMessages[] = "Menu price must be a positive number.";
    }

    // If there are no validation errors, proceed to insert into the database
    if (empty($errorMessages)) {
        $sql = "INSERT INTO menu (image, name, description, category, size, temperature, quantity, price, status)
                VALUES ('$menuImage', '$menuName', '$menuDescription', '$menuCategory', '$menuSize', '$menuTemperature', '$menuQuantity', '$menuPrice', '$productStatus')";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "Menu item added successfully!";
            // Redirect after successful insertion
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $errorMessages[] = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle deletion of menu items
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $deleteSql = "DELETE FROM menu WHERE id = $deleteId";
    if ($conn->query($deleteSql) === TRUE) {
        $successMessage = "Menu item deleted successfully!";
    } else {
        $errorMessages[] = "Error deleting item: " . $conn->error;
    }
}

// Fetch total menu items count with category filter
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items for the current page with category filter
$sql = "SELECT * FROM menu" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);

ob_end_flush();
?>

<div class="container-fluid mb-5 ">
    <div class="row mt-5 ms-5 align-items-center">
        <div class="col-12 col-md-8">
            <p class="account-text">
                Our <span class="management-underline">Menu</span>
            </p>
        </div>
        <div class="col-12 col-md-3 d-flex justify-content-md-end ">
            <div class="dropdown ">
                <div class="input-group">
                    <input type="text" id="search" class="form-control search-box" placeholder="Search your cravings!" aria-label="Search item">
                </div>
                <button class="btn btn-success add-menu mx-3" data-bs-toggle="modal" data-bs-target="#addMenuModal" style="width: 200px;">+ Add Menu</button>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="container-fluid">
        <div class="row mb-3 category-list container-fluid">
            <div class="col-12 d-flex flex-wrap justify-content-center">
                <!-- Category Filter -->
                <div class="mb-3 me-3">
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                        <option value="">All</option>
                        <option value="Coffee">Coffee</option>
                        <option value="Non-Coffee">Non-Coffee</option>
                        <option value="Signature Frappe">Signature Frappe</option>
                        <option value="Snacks">Snacks</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Items Display -->
    <div class="row">
        <?php while ($menu = $result->fetch_assoc()) { ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card menu-card">
                    <img src="<?php echo $menu['image']; ?>" class="card-img-top" alt="<?php echo $menu['name']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $menu['name']; ?></h5>
                        <p class="card-text"><?php echo $menu['description']; ?></p>
                        <p class="card-text">Price: ₱<?php echo number_format($menu['price'], 2); ?></p>
                        <p class="card-text">Quantity: <?php echo $menu['quantity']; ?></p>
                        <p class="card-text">Category: <?php echo $menu['category']; ?></p>
                        <p class="card-text">Size: <?php echo $menu['size']; ?></p>
                        <p class="card-text">Temperature: <?php echo $menu['temperature']; ?></p>
                        <p class="card-text">Status: <?php echo $menu['status']; ?></p>
                        <a href="#" class="btn btn-primary">Update</a>
                        <a href="?delete_id=<?php echo $menu['id']; ?>" class="btn btn-delete btn-danger">Delete</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $selectedCategory; ?>"><?php echo $i; ?></a>
                </li>
            <?php } ?>
        </ul>
    </nav>

    <!-- Add Menu Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuModalLabel">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="menuImage" class="form-label">Menu Image</label>
                            <input type="file" class="form-control" id="menuImage" name="menuImage" required>
                        </div>
                        <div class="mb-3">
                            <label for="menuName" class="form-label">Menu Name</label>
                            <input type="text" class="form-control" id="menuName" name="menuName" required>
                        </div>
                        <div class="mb-3">
                            <label for="menuDescription" class="form-label">Menu Description</label>
                            <textarea class="form-control" id="menuDescription" name="menuDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="menuCategory" class="form-label">Category</label>
                            <select class="form-select" id="menuCategory" name="menuCategory" required>
                                <option value="">Choose...</option>
                                <option value="Coffee">Coffee</option>
                                <option value="Non-Coffee">Non-Coffee</option>
                                <option value="Signature Frappe">Signature Frappe</option>
                                <option value="Snacks">Snacks</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="menuSize" class="form-label">Size</label>
                            <select class="form-select" id="menuSize" name="menuSize[]" multiple required>
                                <option value="Small">Small</option>
                                <option value="Medium">Medium</option>
                                <option value="Large">Large</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="menuTemperature" class="form-label">Temperature</label>
                            <select class="form-select" id="menuTemperature" name="menuTemperature[]" multiple required>
                                <option value="Hot">Hot</option>
                                <option value="Cold">Cold</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="menuQuantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="menuQuantity" name="menuQuantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="menuPrice" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="menuPrice" name="menuPrice" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="productStatus" class="form-label">Status</label>
                            <select class="form-select" id="productStatus" name="productStatus" required>
                                <option value="Available">Available</option>
                                <option value="Not Available">Not Available</option>
                            </select>
                        </div>
                        <button type="submit" name="addMenuItem" class="btn btn-success">Add Menu Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Display error or success messages -->
    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errorMessages as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <p><?php echo $successMessage; ?></p>
        </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
    function filterByCategory() {
        var selectedCategory = document.getElementById('categoryFilter').value;
        window.location.href = '?category=' + selectedCategory;
    }

    // Search functionality
    $(document).ready(function () {
        $('#search').on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $('.menu-card').filter(function () {
                $(this).toggle($(this).find('.card-title').text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
</body>
</html>
