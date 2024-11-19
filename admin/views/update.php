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

        .add-menu{

        }


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




        .dropdown{
            display: flex;
            flex-direction: row;
        }

        .btn-delete{
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
            #categoryFilter {
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
            .btn-update{
               background-color: #FF902B important;
        }

        .menu-card{  
            margin: 0.5rem;
            
        }

        .add-menu{
              border-radius: 6rem !important;
        }

        .search-box{
            background-color: #F1F1F1 !important;
            border-radius: 6rem !important;
            padding: 1.3rem !important;
        }

        .container-s_add{
            width: 4rem;
        }

        .btn-add-item{
            background-color: #FF902B !important;
            border-radius: 7rem;
            border: 1px solid #FF902B;
            padding: 0.5rem;
        }

        .close-add{
            border-radius: 7rem;
            background:none !important;
            border: 1px solid #FF902B;
            padding: 0.5rem;
            color: #FF902B ;
        }

        #quantityValue,
        #priceValue{
            border-radius: 7rem;
            border: 1px solid #616161;
            color: #616161;
            padding: 0.5rem;
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateMenuItem'])) {
    $menuId = intval($_POST['menuId']);
    $menuName = $conn->real_escape_string($_POST['menuName']);
    $menuDescription = $conn->real_escape_string($_POST['menuDescription']);
    $menuCategory = $conn->real_escape_string($_POST['menuCategory']);
    $menuSize = isset($_POST['menuSize']) ? implode(',', $_POST['menuSize']) : '';
    $menuTemperature = isset($_POST['menuTemperature']) ? implode(',', $_POST['menuTemperature']) : '';
    $menuQuantity = intval($_POST['menuQuantity']);
    $menuPrice = floatval($_POST['menuPrice']);
    $productStatus = $conn->real_escape_string($_POST['productStatus']);
    
    // Handle image upload if a new image is uploaded
    if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
        $target_dir = "./uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            die("Failed to upload image.");
        }
    } else {
        // If no new image, keep the existing one
        $menuImage = $conn->real_escape_string($_POST['existingImage']);
    }

    $sql = "UPDATE menu SET 
            image = '$menuImage',
            name = '$menuName',
            description = '$menuDescription',
            category = '$menuCategory',
            size = '$menuSize',
            temperature = '$menuTemperature',
            quantity = '$menuQuantity',
            price = '$menuPrice',
            status = '$productStatus'
            WHERE id = $menuId";

    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}

// Handle form submission for updating menu items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateMenuItem'])) {
    $menuId = intval($_POST['menuId']);
    $menuName = $conn->real_escape_string($_POST['menuName']);
    $menuDescription = $conn->real_escape_string($_POST['menuDescription']);
    $menuCategory = $conn->real_escape_string($_POST['menuCategory']);
    $menuSize = isset($_POST['menuSize']) ? implode(',', $_POST['menuSize']) : '';
    $menuTemperature = isset($_POST['menuTemperature']) ? implode(',', $_POST['menuTemperature']) : '';
    $menuQuantity = intval($_POST['menuQuantity']);
    $menuPrice = floatval($_POST['menuPrice']);
    $productStatus = $conn->real_escape_string($_POST['productStatus']);

    $sql = "UPDATE menu SET 
            name='$menuName', 
            description='$menuDescription', 
            category='$menuCategory', 
            size='$menuSize', 
            temperature='$menuTemperature', 
            quantity='$menuQuantity', 
            price='$menuPrice', 
            status='$productStatus' 
            WHERE id='$menuId'";

    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='alert alert-danger'>Error: " . $conn->error . "</p>";
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
                <!-- Removed the category filter from the dropdown -->
                
                <div class="input-group">
            <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
        </div>
                
           
        

               <button class="btn btn-success add-menu  mx-3" data-bs-toggle="modal" data-bs-target="#addMenuModal" style="width: 200px;">+ Add Menu</button>

            </div>
        </div>
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
                $name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                $price = htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8');
                $image = htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8');
                $id = intval($row['id']); 
    
                $output .= '
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
                    <div class="card p-2 rounded-1" style="border: none;">
                        <div class="img-container" style="overflow: hidden; height: 150px;">
                            <img src="' . $image . '" class="card-img-top" alt="' . $name . '" style="height: 100%; width: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body text-center p-1">
                            <div class="card-title" style="font-size: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                <strong><h5 style="margin: 0;">' . $name . '</h5></strong>
                                <p class="card-text text-success" style="font-size: 0.9rem; margin: 0;">
                                    <strong>₱' . $price . '</strong>
                                </p>
                            </div>
                           <!-- Update button example -->
<button class="btn btn-warning btn-update" data-menu-id="<?php echo $menuItem(' . $id . '); ?>" data-bs-toggle="modal" data-bs-target="#updateMenuModal">Update</button>

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

        // Assuming $result is defined somewhere in your code
        echo renderMenuItems($result);
        ?>
    </div>
</div>

<!-- Modal for updating menu item -->
<!-- Update Menu Modal -->
<div class="modal fade" id="updateMenuModal" tabindex="-1" aria-labelledby="updateMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-light" id="updateMenuModalLabel">Update Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left Column: Image Upload and Description -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menuImage" class="form-label">Upload Image</label>
                                <input type="file" class="form-control" id="menuImage" name="menuImage" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="menuDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="menuDescription" name="menuDescription" rows="4" required></textarea>
                            </div>
                        </div>
                        <!-- Right Column: Form Fields -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control" id="menuName" name="menuName" required>
                            </div>
                            <div class="mb-3">
                                <label for="menuCategory" class="form-label">Category</label>
                                <select class="form-select" id="menuCategory" name="menuCategory" required>
                                    <option value="Coffee">Coffee</option>
                                    <option value="Non-Coffee">Non-Coffee</option>
                                    <option value="Signature Frappe">Signature Frappe</option>
                                    <option value="Starters">Starters</option>
                                    <option value="Pasta">Pasta</option>
                                    <option value="Sandwich">Sandwich</option>
                                    <option value="Rice Meal">Rice Meal</option>
                                    <option value="All Day Breakfast">All Day Breakfast</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="menuSize" class="form-label">Size</label>
                                <div>
                                    <input type="checkbox" name="menuSize[]" value="Small"> Small
                                    <input type="checkbox" name="menuSize[]" value="Medium"> Medium
                                    <input type="checkbox" name="menuSize[]" value="Large"> Large
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Temperature</label>
                                <div>
                                    <input type="checkbox" id="temperatureHot" name="menuTemperature[]" value="Hot">
                                    <label for="temperatureHot">Hot</label>
                                    
                                    <input type="checkbox" id="temperatureWarm" name="menuTemperature[]" value="Warm">
                                    <label for="temperatureWarm">Warm</label>
                                    
                                    <input type="checkbox" id="temperatureCold" name="menuTemperature[]" value="Cold">
                                    <label for="temperatureCold">Cold</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="menuQuantity" class="form-label">Quantity</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="quantityValue">1</div>
                                    <input type="range" class="form-range" id="menuQuantity" name="menuQuantity" min="1" max="100" value="1" required 
                                           style="accent-color: #FF902B;">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="menuPrice" class="form-label">Price</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="priceValue">0.00</div>
                                    <input type="range" class="form-range" id="menuPrice" name="menuPrice" min="0" max="1000" value="0" step="0.01" required 
                                           style="accent-color: #FF902B;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Product Status</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" id="statusAvailable" name="productStatus" value="Available" required>
                                        <label class="form-check-label" for="statusAvailable">
                                            Available
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" id="statusUnavailable" name="productStatus" value="Unavailable" required>
                                        <label class="form-check-label" for="statusUnavailable">
                                            Unavailable
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="container">
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="container-fluid close-add" data-bs-dismiss="modal" aria-label="Close">Close</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="updateMenuItem" class="btn-add-item container-fluid text-light">Update Menu Item</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    const quantitySlider = document.getElementById('menuQuantity');
    const quantityValue = document.getElementById('quantityValue');
    quantitySlider.oninput = function() {
        quantityValue.innerText = this.value;
    };

    const priceSlider = document.getElementById('menuPrice');
    const priceValue = document.getElementById('priceValue');
    priceSlider.oninput = function() {
        priceValue.innerText = parseFloat(this.value).toFixed(2);
    };



</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script>
    function updateQuantityValue(value) {
        document.getElementById('quantityValue').textContent = value;
    }

    function updatePriceValue(value) {
        document.getElementById('priceValue').textContent = parseFloat(value).toFixed(2);
    }
</script>
<script>


    // Fetch the menu item data from the server using AJAX
// JavaScript to handle opening the modal and populating it with data
document.addEventListener('DOMContentLoaded', function() {
    const updateButtons = document.querySelectorAll('.btn-update'); // Select all update buttons

    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const menuId = this.getAttribute('data-menu-id'); // Get the menu ID from a data attribute
            fetch(`./path/to/your/script.php?id=${menuId}`) // Fetch the item data
                .then(response => response.json())
                .then(data => {
                    // Populate the modal fields with the fetched data
                    document.getElementById('menuId').value = data.id;
                    document.getElementById('menuName').value = data.name;
                    document.getElementById('menuDescription').value = data.description;
                    document.getElementById('menuCategory').value = data.category;
                    document.getElementById('menuPrice').value = data.price;
                    // Show the modal
                    $('#updateMenuModal').modal('show');
                })
                .catch(error => console.error('Error fetching data:', error));
        });
    });
});


</script>



</body>
</html>
