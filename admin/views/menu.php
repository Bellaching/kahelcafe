<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="menu.css">
    <style>
        /* Additional styling for layout */
        .modal-body {
            display: flex;
            flex-wrap: wrap;
        }
        .modal-body .form-left {
            flex: 1;
            margin-right: 20px;
        }
        .modal-body .form-right {
            flex: 1;
        }
        .price-range, .quantity-input {
            display: none;
        }
        .category-title {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .menu-item-container {
            display: flex;
            justify-content: space-between;
        }
     
    </style>
</head>
<body>

<?php 
ob_start(); // Start output buffering
include "./../../admin/views/banner.php";
include "./../../connection/connection.php";

// Pagination settings
$itemsPerPage = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;
 
// Initialize filter variables
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Handle form submission for adding menu items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
    if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            die("Failed to upload image.");
        }
    }

    $menuName = $conn->real_escape_string($_POST['menuName']);
    $menuDescription = $conn->real_escape_string($_POST['menuDescription']);
    $menuCategory = $conn->real_escape_string($_POST['menuCategory']);
    $menuSize = isset($_POST['menuSize']) ? implode(',', $_POST['menuSize']) : '';
    $menuTemperature = isset($_POST['menuTemperature']) ? implode(',', $_POST['menuTemperature']) : '';
    $menuQuantity = intval($_POST['menuQuantity']);
    $menuPriceSmall = floatval($_POST['menuPriceSmall']);
    $menuPriceMedium = floatval($_POST['menuPriceMedium']);
    $menuPriceLarge = floatval($_POST['menuPriceLarge']);
    $productStatus = $conn->real_escape_string($_POST['productStatus']);

    $sql = "INSERT INTO menu (menu_name, menu_description, menu_category, menu_size, menu_temperature, menu_quantity, menu_price_small, menu_price_medium, menu_price_large, product_status, menu_image_path)
            VALUES ('$menuName', '$menuDescription', '$menuCategory', '$menuSize', '$menuTemperature', '$menuQuantity', '$menuPriceSmall', '$menuPriceMedium', '$menuPriceLarge', '$productStatus', '$menuImage')";

    if ($conn->query($sql) === TRUE) {
        // Redirect after successful insertion
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}

// Handle form submission for deleting menu items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteMenuItem'])) {
    $menuId = intval($_POST['menuId']);
    $sql = "DELETE FROM menu WHERE id = $menuId";
    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $errorMessages[] = "Error deleting item: " . $conn->error;
    }
}

if (isset($_GET['id'])) {
    $menuId = intval($_GET['id']);
    $sql = "SELECT * FROM menu WHERE id = $menuId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode([]);
    }
}

// Fetch total menu items count with category filter
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu" . ($selectedCategory ? " WHERE menu_category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items for the current page with category filter
$sql = "SELECT * FROM menu" . ($selectedCategory ? " WHERE menu_category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
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
        <div class="col-12 col-md-3 d-flex justify-content-md-end">
            <div class="dropdown">
                <div class="input-group">
                    <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
                </div>
                <button class="btn btn-success add-menu mx-3" data-bs-toggle="modal" data-bs-target="#addMenuModal" style="width: 200px;">+ Add Menu</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-0" id="menu-card-container">
            <?php
            function renderMenuItems($result) {
                $output = '';
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $menu_name = htmlspecialchars($row['menu_name'], ENT_QUOTES, 'UTF-8');
                        $price_small = htmlspecialchars($row['menu_price_small'], ENT_QUOTES, 'UTF-8');
                        $price_large = htmlspecialchars($row['menu_price_large'], ENT_QUOTES, 'UTF-8');
                        $image = htmlspecialchars($row['menu_image_path'], ENT_QUOTES, 'UTF-8');
                        $category = htmlspecialchars($row['menu_category'], ENT_QUOTES, 'UTF-8');
                        $id = intval($row['id']); 

                        // Check if the item is food or drink and apply the necessary styles
                        $isFood = in_array($category, ['Rice Meal', 'Pasta', 'Sandwich', 'Starters', 'All Day Breakfast']);
                        $isDrink = in_array($category, ['Coffee', 'Non-Coffee', 'Signature Frappe']);
                        
                        $output .= '
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
                            <div class="card p-2 rounded-1" style="border: none;">
                                <div class="img-container" style="overflow: hidden; height: 150px;">
                                    <img src="' . $image . '" class="card-img-top" alt="' . $menu_name . '" style="height: 100%; width: 100%; object-fit: cover;">
                                </div>
                                <div class="card-body text-center p-1">
                                    <div class="menu-item-container">
                                        <div class="category-title">
                                            <h5>' . $menu_name . '</h5>
                                        </div>
                                        <div class="price-info">
                                            <p><strong>₱' . $price_small . ' - ' . $price_large . '</strong></p>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm p-2" onclick="openUpdateMenuModal(' . $id . ')">
                                        <i class="fa-solid fa-cart-shopping"></i> Update Menu
                                    </button>
                                    <button class="btn btn-delete btn-danger" onclick="confirmDelete(' . $id . ')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    $output .= '<div class="container d-flex justify-content-center"><p class="text-center">No menu items found.</p></div>';
                }
                return $output; 
            }

            echo renderMenuItems($result);
            ?>
        </div>
    </div>

    <div class="modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
    <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
            <h5 class="modal-title" id="addMenuModalLabel">Add New Menu Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <!-- Left Side: Image and Description -->
            <div class="form-left">
                <!-- Image input -->
                <div class="mb-3">
                    <label for="menuImage" class="form-label">Image</label>
                    <input type="file" class="form-control" id="menuImage" name="menuImage" accept="image/*" required>
                </div>

                <!-- Description input -->
                <div class="mb-3">
                    <label for="menuDescription" class="form-label">Menu Description</label>
                    <textarea class="form-control" id="menuDescription" name="menuDescription" rows="3" required></textarea>
                </div>
            </div>

            <!-- Right Side: Menu Name, Category, and Price Info -->
            <div class="form-right">
                <!-- Menu Name input -->
                <div class="mb-3">
                    <label for="menuName" class="form-label">Menu Name</label>
                    <input type="text" class="form-control" id="menuName" name="menuName" required>
                </div>

                <!-- Category dropdown -->
                <div class="mb-3">
                    <label for="menuCategory" class="form-label">Menu Category</label>
                    <select class="form-control" id="menuCategory" name="menuCategory" required onchange="toggleCategory()">
                        <option value="">Select Category</option>
                        <option value="Rice Meal">Rice Meal</option>
                        <option value="Pasta">Pasta</option>
                        <option value="Sandwich">Sandwich</option>
                        <option value="Starters">Starters</option>
                        <option value="All Day Breakfast">All Day Breakfast</option>
                        <option value="Coffee">Coffee</option>
                        <option value="Non-Coffee">Non-Coffee</option>
                        <option value="Signature Frappe">Signature Frappe</option>
                    </select>
                </div>

                <!-- Size Selection (Checkboxes for Small, Medium, Large) -->
                <div class="mb-3" id="sizeSelectionWrapper">
                    <label class="form-label">Select Size</label>
                    <div>
                        <input type="checkbox" id="sizeSmall" name="size[]" value="Small" onclick="toggleSize('Small')"> Small
                        <input type="checkbox" id="sizeMedium" name="size[]" value="Medium" onclick="toggleSize('Medium')"> Medium
                        <input type="checkbox" id="sizeLarge" name="size[]" value="Large" onclick="toggleSize('Large')"> Large
                    </div>
                </div>

                <!-- Price and Quantity for Selected Size -->
                <div id="priceWrapper">
                    <!-- Price and Quantity for Small -->
                    <div id="smallPriceWrapper" style="display:none;">
                        <div class="mb-3">
                            <label for="menuPriceSmall" class="form-label">Price (Small)</label>
                            <input type="number" class="form-control" id="menuPriceSmall" name="menuPriceSmall" min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="menuQuantitySmall" class="form-label">Quantity (Small)</label>
                            <input type="number" class="form-control" id="menuQuantitySmall" name="menuQuantitySmall" min="1" value="1">
                        </div>
                    </div>

                    <!-- Price and Quantity for Medium -->
                    <div id="mediumPriceWrapper" style="display:none;">
                        <div class="mb-3">
                            <label for="menuPriceMedium" class="form-label">Price (Medium)</label>
                            <input type="number" class="form-control" id="menuPriceMedium" name="menuPriceMedium" min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="menuQuantityMedium" class="form-label">Quantity (Medium)</label>
                            <input type="number" class="form-control" id="menuQuantityMedium" name="menuQuantityMedium" min="1" value="1">
                        </div>
                    </div>

                    <!-- Price and Quantity for Large -->
                    <div id="largePriceWrapper" style="display:none;">
                        <div class="mb-3">
                            <label for="menuPriceLarge" class="form-label">Price (Large)</label>
                            <input type="number" class="form-control" id="menuPriceLarge" name="menuPriceLarge" min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="menuQuantityLarge" class="form-label">Quantity (Large)</label>
                            <input type="number" class="form-control" id="menuQuantityLarge" name="menuQuantityLarge" min="1" value="1">
                        </div>
                    </div>
                </div>

                <!-- Food Price for Food Categories -->
                <div id="foodPriceWrapper" style="display:none;">
                    <div class="mb-3">
                        <label for="menuFoodPrice" class="form-label">Food Price</label>
                        <input type="number" class="form-control" id="menuFoodPrice" name="menuFoodPrice" min="0" step="0.01" required>
                    </div>
                </div>

                <!-- Temperature selection for non-food categories -->
                <div id="sizeTemperatureWrapper" style="display:none;">
                    <div class="mb-3">
                        <label for="menuTemperature" class="form-label">Temperature</label>
                        <div>
                            <input type="checkbox" id="hotTemperature" name="menuTemperature[]" value="hot"> Too Hot
                            <input type="checkbox" id="warmTemperature" name="menuTemperature[]" value="warm"> Warm
                            <input type="checkbox" id="coldTemperature" name="menuTemperature[]" value="cold"> Cold
                        </div>
                    </div>
                </div>

                <!-- Product Status dropdown -->
                <div class="mb-3">
                    <label for="productStatus" class="form-label">Product Status</label>
                    <select class="form-control" id="productStatus" name="productStatus" required>
                        <option value="Available">Available</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success" name="addMenuItem">Save Menu Item</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
</div>



        </div>
    </div>

    <!-- Pagination Controls -->
    <div class="d-flex justify-content-center">
        <nav>
            <ul class="pagination">
                <?php
                // Previous page button
                if ($current_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($current_page - 1) . '">&laquo;</a></li>';
                }

                // Page numbers
                for ($i = 1; $i <= $totalPages; $i++) {
                    echo '<li class="page-item' . ($i == $current_page ? ' active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                }

                // Next page button
                if ($current_page < $totalPages) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($current_page + 1) . '">&raquo;</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmDelete(menuId) {
        if (confirm("Are you sure you want to delete this item?")) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'menuId';
            input.value = menuId;
            form.appendChild(input);
            var deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'deleteMenuItem';
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function openUpdateMenuModal(menuId) {
        // Fetch the menu item details and populate the modal fields
        fetch(`?id=${menuId}`)
            .then(response => response.json())
            .then(data => {
                // Pre-fill the modal fields with the data
                document.getElementById('menuName').value = data.menu_name;
                document.getElementById('menuDescription').value = data.menu_description;
                // Populate other fields as required
            });
    }
</script>
<script>
    // Function to toggle the visibility of size price and quantity fields based on checkbox selection
    function toggleSize(size) {
        // Hide all size price and quantity fields first
        document.getElementById('smallPriceWrapper').style.display = 'none';
        document.getElementById('mediumPriceWrapper').style.display = 'none';
        document.getElementById('largePriceWrapper').style.display = 'none';

        // Show the corresponding price and quantity if the size checkbox is checked
        if (size === 'Small' && document.getElementById('sizeSmall').checked) {
            document.getElementById('smallPriceWrapper').style.display = 'block';
        } else if (size === 'Medium' && document.getElementById('sizeMedium').checked) {
            document.getElementById('mediumPriceWrapper').style.display = 'block';
        } else if (size === 'Large' && document.getElementById('sizeLarge').checked) {
            document.getElementById('largePriceWrapper').style.display = 'block';
        }
    }

    // Function to toggle category-related fields
    function toggleCategory() {
        const category = document.getElementById('menuCategory').value;

        // Show the size and temperature checkboxes for non-food categories
        if (category === "Rice Meal" || category === "Pasta" || category === "Sandwich" || category === "Starters" || category === "All Day Breakfast") {
            // Food Category: Disable size selection and temperature, show food price
            document.getElementById('sizeSelectionWrapper').style.display = 'none';
            document.getElementById('foodPriceWrapper').style.display = 'block';
            document.getElementById('sizeTemperatureWrapper').style.display = 'none';
        } else {
            // Non-Food Category: Enable size selection, show temperature selection, hide food price
            document.getElementById('sizeSelectionWrapper').style.display = 'block';
            document.getElementById('foodPriceWrapper').style.display = 'none';
            document.getElementById('sizeTemperatureWrapper').style.display = 'block';
        }
    }

    // Initialize the category toggle logic when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        toggleCategory(); // Run once to set the initial state
    });
</script>

</body>
</html> 