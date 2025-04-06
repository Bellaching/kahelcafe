<?php 
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./../../admin/views/banner.php";
include "./../../connection/connection.php";

$itemsPerPage = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Initialize error array
$errors = [
    'menuImage' => '',
    'menuName' => '',
    'menuDescription' => '',
    'menuCategory' => '',
    'menuSize' => '',
    'menuTemperature' => '',
    'menuQuantity' => '',
    'menuPrice' => '',
    'productStatus' => '',
    'general' => [],
    // Edit form errors
    'editMenuImage' => '',
    'editMenuName' => '',
    'editMenuDescription' => '',
    'editMenuCategory' => '',
    'editMenuSize' => '',
    'editMenuTemperature' => '',
    'editMenuQuantity' => '',
    'editMenuPrice' => '',
    'editProductStatus' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
    // Initialize variables
    $menuImage = '';
    $menuName = isset($_POST['menuName']) ? $conn->real_escape_string($_POST['menuName']) : '';
    $menuDescription = isset($_POST['menuDescription']) ? $conn->real_escape_string($_POST['menuDescription']) : '';
    $menuCategory = isset($_POST['menuCategory']) ? $conn->real_escape_string($_POST['menuCategory']) : '';
    $menuSize = isset($_POST['menuSize']) ? $_POST['menuSize'] : [];
    $menuTemperature = isset($_POST['menuTemperature']) ? $_POST['menuTemperature'] : [];
    $menuQuantity = isset($_POST['menuQuantity']) ? intval($_POST['menuQuantity']) : 0;
    $menuPrice = isset($_POST['menuPrice']) ? floatval($_POST['menuPrice']) : 0;
    $productStatus = isset($_POST['productStatus']) ? $conn->real_escape_string($_POST['productStatus']) : '';

    // Validation
    if (empty($menuName)) {
        $errors['menuName'] = "Menu name is required.";
    }
    if (empty($menuDescription)) {
        $errors['menuDescription'] = "Description is required.";
    }
    if (empty($menuCategory)) {
        $errors['menuCategory'] = "Category is required.";
    }
    
    // Validate size and temperature based on category
    if ($menuCategory === 'Coffee' || $menuCategory === 'Non-Coffee' || $menuCategory === 'Signature Frappe') {
        if (empty($menuSize)) {
            $errors['menuSize'] = "Please select at least one size.";
        }
        if (empty($menuTemperature)) {
            $errors['menuTemperature'] = "Please select at least one temperature.";
        }
    }
    
    if ($menuQuantity <= 0) {
        $errors['menuQuantity'] = "Quantity must be greater than 0.";
    }
    if ($menuPrice <= 0) {
        $errors['menuPrice'] = "Price must be greater than 0.";
    }
    if (empty($productStatus)) {
        $errors['productStatus'] = "Product status is required.";
    }

    // Handle image upload
    if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        
        // Check if file is an image
        $check = getimagesize($_FILES["menuImage"]["tmp_name"]);
        if($check === false) {
            $errors['menuImage'] = "File is not an image.";
        }
        
        // Check file size (5MB max)
        if ($_FILES["menuImage"]["size"] > 5000000) {
            $errors['menuImage'] = "Image is too large. Maximum size is 5MB.";
        }
        
        if(empty($errors['menuImage'])) {
            if (!move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
                $errors['menuImage'] = "Error uploading image.";
            } else {
                $menuImage = $image_file;
            }
        }
    } else {
        $errors['menuImage'] = "Image is required.";
    }

    // If no errors, proceed with database insertion
    if (empty(array_filter($errors))) {
        $sizeStr = !empty($menuSize) ? implode(',', $menuSize) : '';
        $tempStr = !empty($menuTemperature) ? implode(',', $menuTemperature) : '';
        
        $sql = "INSERT INTO menu1 (image, name, description, category, size, temperature, quantity, price, status)
                VALUES ('$menuImage', '$menuName', '$menuDescription', '$menuCategory', '$sizeStr', '$tempStr', $menuQuantity, $menuPrice, '$productStatus')";

        if ($conn->query($sql)) {
            header("Location: menuManagement.php");
            exit();
        } else {
            $errors['general'][] = "Database error: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteMenuItem'])) {
    $menuId = isset($_POST['menuId']) ? intval($_POST['menuId']) : 0;
    if ($menuId > 0) {
        $sql = "DELETE FROM menu1 WHERE id = $menuId";
        if ($conn->query($sql)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $errors['general'][] = "Error deleting item: " . $conn->error;
        }
    } else {
        $errors['general'][] = "Invalid menu item ID.";
    }
}

if (isset($_GET['id'])) {
    $menuId = intval($_GET['id']);
    $sql = "SELECT * FROM menu1 WHERE id = $menuId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'size' => $row['size'],
            'temperature' => $row['temperature'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'status' => $row['status'],
            'image' => $row['image']
        ]);
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editMenuItem'])) {
    $menuId = isset($_POST['editMenuId']) ? intval($_POST['editMenuId']) : 0;
    $menuName = isset($_POST['editMenuName']) ? $conn->real_escape_string($_POST['editMenuName']) : '';
    $menuDescription = isset($_POST['editMenuDescription']) ? $conn->real_escape_string($_POST['editMenuDescription']) : '';
    $menuCategory = isset($_POST['editMenuCategory']) ? $conn->real_escape_string($_POST['editMenuCategory']) : '';
    $menuSize = isset($_POST['editMenuSize']) ? $_POST['editMenuSize'] : [];
    $menuTemperature = isset($_POST['editMenuTemperature']) ? $_POST['editMenuTemperature'] : [];
    $menuQuantity = isset($_POST['editMenuQuantity']) ? intval($_POST['editMenuQuantity']) : 0;
    $menuPrice = isset($_POST['editMenuPrice']) ? floatval($_POST['editMenuPrice']) : 0;
    $productStatus = isset($_POST['editProductStatus']) ? $conn->real_escape_string($_POST['editProductStatus']) : '';

    // Validation
    if (empty($menuName)) {
        $errors['editMenuName'] = "Menu name is required.";
    }
    if (empty($menuDescription)) {
        $errors['editMenuDescription'] = "Description is required.";
    }
    if (empty($menuCategory)) {
        $errors['editMenuCategory'] = "Category is required.";
    }
    
    // Validate size and temperature based on category
    if ($menuCategory === 'Coffee' || $menuCategory === 'Non-Coffee' || $menuCategory === 'Signature Frappe') {
        if (empty($menuSize)) {
            $errors['editMenuSize'] = "Please select at least one size.";
        }
        if (empty($menuTemperature)) {
            $errors['editMenuTemperature'] = "Please select at least one temperature.";
        }
    }
    
    if ($menuQuantity <= 0) {
        $errors['editMenuQuantity'] = "Quantity must be greater than 0.";
    }
    if ($menuPrice <= 0) {
        $errors['editMenuPrice'] = "Price must be greater than 0.";
    }
    if (empty($productStatus)) {
        $errors['editProductStatus'] = "Product status is required.";
    }

    // Handle image upload if a new image was provided
    $menuImage = '';
    if (isset($_FILES['editMenuImage']) && $_FILES['editMenuImage']['error'] == 0) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["editMenuImage"]["name"]);
        
        $check = getimagesize($_FILES["editMenuImage"]["tmp_name"]);
        if($check === false) {
            $errors['editMenuImage'] = "File is not an image.";
        }
        
        if ($_FILES["editMenuImage"]["size"] > 5000000) {
            $errors['editMenuImage'] = "Image is too large. Maximum size is 5MB.";
        }
        
        if(empty($errors['editMenuImage'])) {
            if (!move_uploaded_file($_FILES["editMenuImage"]["tmp_name"], $image_file)) {
                $errors['editMenuImage'] = "Error uploading image.";
            } else {
                $menuImage = $image_file;
            }
        }
    }

    // Check if we have any edit-specific errors
    $editErrors = array_filter([
        $errors['editMenuName'],
        $errors['editMenuDescription'],
        $errors['editMenuCategory'],
        $errors['editMenuSize'],
        $errors['editMenuTemperature'],
        $errors['editMenuQuantity'],
        $errors['editMenuPrice'],
        $errors['editProductStatus'],
        $errors['editMenuImage']
    ]);

    if (empty($editErrors)) {
        $sizeStr = !empty($menuSize) ? implode(',', $menuSize) : '';
        $tempStr = !empty($menuTemperature) ? implode(',', $menuTemperature) : '';
        
        $sql = "UPDATE menu1 SET 
                name = '$menuName', 
                description = '$menuDescription', 
                category = '$menuCategory', 
                size = '$sizeStr', 
                temperature = '$tempStr', 
                quantity = $menuQuantity, 
                price = $menuPrice, 
                status = '$productStatus'";

        if (!empty($menuImage)) {
            $sql .= ", image = '$menuImage'";
        }

        $sql .= " WHERE id = $menuId";

        if ($conn->query($sql)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $errors['general'][] = "Error updating item: " . $conn->error;
        }
    }
}

$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);
$sql = "SELECT * FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="menu.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
  
    <style>
        .add-index {
            background-color: #FF902A;
            border-radius: 7rem;
        }
        .banner-img, .sched-banner-img {
            width: 100%;
            height: auto;
        }
        .special-offers-container {
            background-color: #ffcea4;
            padding: 30px;
            border-radius: 30px;
        }
        .virtual-tour {
            position: relative;
            height: 70vh;
            overflow: hidden;
        }
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sched-reservation {
            position: relative;
            margin-bottom: 1280px;
        }
        .calendar iframe {
            width: 100%;
            height: 80%;
            border: none;
        }
        .modal-backdrop {
            display: none !important;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .general-errors {
            color: #dc3545;
            margin-bottom: 1rem;
            padding: 0.75rem 1.25rem;
            border: 1px solid #f5c6cb;
            border-radius: 0.25rem;
            background-color: #f8d7da;
        }
        .checkbox-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
    </style>
</head>
<body>

<!-- General Error Display -->
<?php if (!empty($errors['general'])): ?>
    <div class="container mt-3 general-errors">
        <ul class="mb-0">
            <?php foreach ($errors['general'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="container-fluid mb-5">
    <div class="row mt-5 align-items-center">
        <div class="col-12 col-md-8 text-center text-md-start ms-md-5">
            <p class="account-text">
                Our <span class="management-underline">Menu</span>
            </p>
        </div>
        <div class="col-12 col-md-3 d-flex flex-column flex-md-row align-items-center justify-content-center justify-content-md-end gap-2">
            <div class="input-group w-100 w-md-auto">
                <input type="text" id="search" class="form-control search-box" 
                    placeholder="Search item..." 
                    aria-label="Search item"
                    style="min-width: 200px; height: 35px; padding: 5px 10px; border-radius: 5px;">
            </div> 
            <button class="btn btn-success add-menu w-100 w-md-auto" 
                data-bs-toggle="modal" 
                data-bs-target="#addMenuModal" 
                style="min-width: 200px; white-space: nowrap; display: inline-block;">
                + Add Menu
            </button>
        </div>
    </div>
</div>

<?php include "filter.php";?>
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
                        <button class="btn btn-edit btn-primary" style="background-color:#FF902B; border:none; border-radius:3rem; padding: 0.5rem;" onclick="openEditModal(' . $id . ', \'' . $name . '\')">
                            <i class="fa-solid fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-delete btn-danger" onclick="confirmDelete(' . $id . ')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            ';
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

<input type="hidden" id="deleteMenuId" name="deleteMenuId">

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this menu item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="deleteMenuItem()">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editMenuModal" tabindex="-1" aria-labelledby="editMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light" id="editMenuModalLabel">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMenuForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editMenuImage" class="form-label">Upload Image</label>
                                <input type="file" class="form-control <?php echo !empty($errors['editMenuImage']) ? 'is-invalid' : '' ?>" id="editMenuImage" name="editMenuImage" accept="image/*">
                                <?php if (!empty($errors['editMenuImage'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuImage']); ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Leave blank to keep the current image.</small>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuDescription" class="form-label">Description</label>
                                <textarea class="form-control <?php echo !empty($errors['editMenuDescription']) ? 'is-invalid' : '' ?>" id="editMenuDescription" name="editMenuDescription" rows="4"><?php echo isset($_POST['editMenuDescription']) ? htmlspecialchars($_POST['editMenuDescription']) : '' ?></textarea>
                                <?php if (!empty($errors['editMenuDescription'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuDescription']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editMenuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control <?php echo !empty($errors['editMenuName']) ? 'is-invalid' : '' ?>" id="editMenuName" name="editMenuName" value="<?php echo isset($_POST['editMenuName']) ? htmlspecialchars($_POST['editMenuName']) : '' ?>">
                                <?php if (!empty($errors['editMenuName'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuName']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuCategory" class="form-label">Category</label>
                                <select class="form-select <?php echo !empty($errors['editMenuCategory']) ? 'is-invalid' : '' ?>" id="editMenuCategory" name="editMenuCategory">
                                    <option value="Coffee" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Coffee') ? 'selected' : '' ?>>Coffee</option>
                                    <option value="Non-Coffee" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Non-Coffee') ? 'selected' : '' ?>>Non-Coffee</option>
                                    <option value="Signature Frappe" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Signature Frappe') ? 'selected' : '' ?>>Signature Frappe</option>
                                    <option value="Starters" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Starters') ? 'selected' : '' ?>>Starters</option>
                                    <option value="Pasta" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Pasta') ? 'selected' : '' ?>>Pasta</option>
                                    <option value="Sandwich" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Sandwich') ? 'selected' : '' ?>>Sandwich</option>
                                    <option value="Rice Meal" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Rice Meal') ? 'selected' : '' ?>>Rice Meal</option>
                                    <option value="All Day Breakfast" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'All Day Breakfast') ? 'selected' : '' ?>>All Day Breakfast</option>
                                </select>
                                <?php if (!empty($errors['editMenuCategory'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuCategory']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 container-fluid" id="editMenuSizeContainer">
                                <label for="editMenuSize" class="form-label">Size</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="editMenuSize[]" value="Small" id="editSizeSmall" <?php echo (isset($_POST['editMenuSize']) && in_array('Small', $_POST['editMenuSize']) ? 'checked' : '' )?>>
                                    <label for="editSizeSmall" class="me-3">Small</label>
                                    <input type="checkbox" name="editMenuSize[]" value="Medium" id="editSizeMedium" <?php echo (isset($_POST['editMenuSize']) && in_array('Medium', $_POST['editMenuSize']) ? 'checked' : '') ?>>
                                    <label for="editSizeMedium" class="me-3">Medium</label>
                                    <input type="checkbox" name="editMenuSize[]" value="Large" id="editSizeLarge" <?php echo (isset($_POST['editMenuSize']) && in_array('Large', $_POST['editMenuSize']) ? 'checked' : '') ?>>
                                    <label for="editSizeLarge">Large</label>
                                </div>
                                <?php if (!empty($errors['editMenuSize'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['editMenuSize']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 container-fluid" id="editMenuTemperatureContainer">
                                <label for="editMenuTemperature" class="form-label">Temperature</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="editMenuTemperature[]" value="Hot" id="editTemperatureHot" <?php echo (isset($_POST['editMenuTemperature']) && in_array('Hot', $_POST['editMenuTemperature']) ? 'checked' : '') ?>>
                                    <label for="editTemperatureHot" class="me-3">Hot</label>
                                    <input type="checkbox" name="editMenuTemperature[]" value="Warm" id="editTemperatureWarm" <?php echo (isset($_POST['editMenuTemperature']) && in_array('Warm', $_POST['editMenuTemperature']) ? 'checked' : '' )?>>
                                    <label for="editTemperatureWarm" class="me-3">Warm</label>
                                    <input type="checkbox" name="editMenuTemperature[]" value="Cold" id="editTemperatureCold" <?php echo (isset($_POST['editMenuTemperature']) && in_array('Cold', $_POST['editMenuTemperature']) ? 'checked' : '' )?>>
                                    <label for="editTemperatureCold">Cold</label>
                                </div>
                                <?php if (!empty($errors['editMenuTemperature'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['editMenuTemperature']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuQuantity" class="form-label">Quantity</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="editQuantityValue"><?php echo isset($_POST['editMenuQuantity']) ? htmlspecialchars($_POST['editMenuQuantity']) : '1' ?></div>
                                    <input type="range" class="form-range <?php echo !empty($errors['editMenuQuantity']) ? 'is-invalid' : '' ?>" id="editMenuQuantity" name="editMenuQuantity" min="1" max="100" value="<?php echo isset($_POST['editMenuQuantity']) ? htmlspecialchars($_POST['editMenuQuantity']) : '1' ?>">
                                </div>
                                <?php if (!empty($errors['editMenuQuantity'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuQuantity']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuPrice" class="form-label">Price</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="editPriceValue"><?php echo isset($_POST['editMenuPrice']) ? number_format($_POST['editMenuPrice'], 2) : '0.00' ?></div>
                                    <input type="range" class="form-range <?php echo !empty($errors['editMenuPrice']) ? 'is-invalid' : '' ?>" id="editMenuPrice" name="editMenuPrice" min="0" max="1000" value="<?php echo isset($_POST['editMenuPrice']) ? htmlspecialchars($_POST['editMenuPrice']) : '0' ?>" step="0.01">
                                </div>
                                <?php if (!empty($errors['editMenuPrice'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuPrice']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="editProductStatus" class="form-label">Status</label>
                                <select class="form-select <?php echo !empty($errors['editProductStatus']) ? 'is-invalid' : '' ?>" id="editProductStatus" name="editProductStatus">
                                    <option value="Available" <?php echo (isset($_POST['editProductStatus']) && $_POST['editProductStatus'] == 'Available') ? 'selected' : '' ?>>Available</option>
                                    <option value="Unavailable" <?php echo (isset($_POST['editProductStatus']) && $_POST['editProductStatus'] == 'Unavailable') ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                                <?php if (!empty($errors['editProductStatus'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editProductStatus']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="editMenuId" name="editMenuId">
                </form>
            </div>
            <div class="row m-2 mb-3">
                <div class="col-6">
                    <button type="button" class="container-fluid close-add" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn-add-item container-fluid text-light" onclick="saveEdit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Menu Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-light" id="addMenuModalLabel">Add Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menuImage" class="form-label">Upload Image</label>
                                <input type="file" class="form-control <?php echo !empty($errors['menuImage']) ? 'is-invalid' : '' ?>" id="menuImage" name="menuImage" accept="image/*">
                                <?php if (!empty($errors['menuImage'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuImage']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="menuDescription" class="form-label">Description</label>
                                <textarea class="form-control <?php echo !empty($errors['menuDescription']) ? 'is-invalid' : '' ?>" id="menuDescription" name="menuDescription" rows="4"><?php echo isset($_POST['menuDescription']) ? htmlspecialchars($_POST['menuDescription']) : '' ?></textarea>
                                <?php if (!empty($errors['menuDescription'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuDescription']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control <?php echo !empty($errors['menuName']) ? 'is-invalid' : '' ?>" id="menuName" name="menuName" value="<?php echo isset($_POST['menuName']) ? htmlspecialchars($_POST['menuName']) : '' ?>">
                                <?php if (!empty($errors['menuName'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuName']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="menuCategory" class="form-label">Category</label>
                                <select class="form-select <?php echo !empty($errors['menuCategory']) ? 'is-invalid' : '' ?>" id="menuCategory" name="menuCategory">
                                    <option value="Coffee" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Coffee') ? 'selected' : '' ?>>Coffee</option>
                                    <option value="Non-Coffee" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Non-Coffee') ? 'selected' : '' ?>>Non-Coffee</option>
                                    <option value="Signature Frappe" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Signature Frappe') ? 'selected' : '' ?>>Signature Frappe</option>
                                    <option value="Starters" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Starters') ? 'selected' : '' ?>>Starters</option>
                                    <option value="Pasta" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Pasta') ? 'selected' : '' ?>>Pasta</option>
                                    <option value="Sandwich" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Sandwich') ? 'selected' : '' ?>>Sandwich</option>
                                    <option value="Rice Meal" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Rice Meal') ? 'selected' : '' ?>>Rice Meal</option>
                                    <option value="All Day Breakfast" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'All Day Breakfast') ? 'selected' : '' ?>>All Day Breakfast</option>
                                </select>
                                <?php if (!empty($errors['menuCategory'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuCategory']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 container-fluid" id="menuSizeContainer">
                                <label for="menuSize" class="form-label">Size</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="menuSize[]" value="Small" id="sizeSmall" <?php echo (isset($_POST['menuSize']) && in_array('Small', $_POST['menuSize'])) ? 'checked' : '' ?>>
                                    <label for="sizeSmall" class="me-3">Small</label>
                                    <input type="checkbox" name="menuSize[]" value="Medium" id="sizeMedium" <?php echo (isset($_POST['menuSize']) && in_array('Medium', $_POST['menuSize'])) ? 'checked' : '' ?>>
                                    <label for="sizeMedium" class="me-3">Medium</label>
                                    <input type="checkbox" name="menuSize[]" value="Large" id="sizeLarge" <?php echo (isset($_POST['menuSize']) && in_array('Large', $_POST['menuSize'])) ? 'checked' : '' ?>>
                                    <label for="sizeLarge">Large</label>
                                </div>
                                <?php if (!empty($errors['menuSize'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['menuSize']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 container-fluid" id="menuTemperatureContainer">
                                <label for="menuTemperature" class="form-label">Temperature</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="menuTemperature[]" value="Hot" id="temperatureHot" <?php echo (isset($_POST['menuTemperature']) && in_array('Hot', $_POST['menuTemperature'])) ? 'checked' : '' ?>>
                                    <label for="temperatureHot" class="me-3">Hot</label>
                                    <input type="checkbox" name="menuTemperature[]" value="Warm" id="temperatureWarm" <?php echo (isset($_POST['menuTemperature']) && in_array('Warm', $_POST['menuTemperature'])) ? 'checked' : '' ?>>
                                    <label for="temperatureWarm" class="me-3">Warm</label>
                                    <input type="checkbox" name="menuTemperature[]" value="Cold" id="temperatureCold" <?php echo (isset($_POST['menuTemperature']) && in_array('Cold', $_POST['menuTemperature'])) ? 'checked' : '' ?>>
                                    <label for="temperatureCold">Cold</label>
                                </div>
                                <?php if (!empty($errors['menuTemperature'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['menuTemperature']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="menuQuantity" class="form-label">Quantity</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="quantityValue"><?php echo isset($_POST['menuQuantity']) ? htmlspecialchars($_POST['menuQuantity']) : '1' ?></div>
                                    <input type="range" class="form-range <?php echo !empty($errors['menuQuantity']) ? 'is-invalid' : '' ?>" id="menuQuantity" name="menuQuantity" min="1" max="100" value="<?php echo isset($_POST['menuQuantity']) ? htmlspecialchars($_POST['menuQuantity']) : '1' ?>">
                                </div>
                                <?php if (!empty($errors['menuQuantity'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuQuantity']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="menuPrice" class="form-label">Price</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="priceValue"><?php echo isset($_POST['menuPrice']) ? number_format($_POST['menuPrice'], 2) : '0.00' ?></div>
                                    <input type="range" class="form-range <?php echo !empty($errors['menuPrice']) ? 'is-invalid' : '' ?>" id="menuPrice" name="menuPrice" min="0" max="1000" value="<?php echo isset($_POST['menuPrice']) ? htmlspecialchars($_POST['menuPrice']) : '0' ?>" step="0.01">
                                </div>
                                <?php if (!empty($errors['menuPrice'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuPrice']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="productStatus" class="form-label">Status</label>
                                <select class="form-select <?php echo !empty($errors['productStatus']) ? 'is-invalid' : '' ?>" id="productStatus" name="productStatus">
                                    <option value="Available" <?php echo (isset($_POST['productStatus']) && $_POST['productStatus'] == 'Available') ? 'selected' : '' ?>>Available</option>
                                    <option value="Unavailable" <?php echo (isset($_POST['productStatus']) && $_POST['productStatus'] == 'Unavailable') ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                                <?php if (!empty($errors['productStatus'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['productStatus']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="container">
                        <div class="row">
                            <div class="col-6">
                                <button type="button" class="container-fluid close-add" data-bs-dismiss="modal" aria-label="Close">Close</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="addMenuItem" class="btn-add-item container-fluid text-light">Add Menu Item</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="pagination-container d-flex justify-content-center my-4">
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?><?= !empty($selectedCategory) ? '&category='.$selectedCategory : '' ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($selectedCategory) ? '&category='.$selectedCategory : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($current_page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?><?= !empty($selectedCategory) ? '&category='.$selectedCategory : '' ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categorySelect = document.getElementById('menuCategory');
        const sizeContainer = document.getElementById('menuSizeContainer');
        const temperatureContainer = document.getElementById('menuTemperatureContainer');

        function toggleFields() {
            const selectedCategory = categorySelect.value;
            if (selectedCategory !== 'Coffee' && selectedCategory !== 'Non-Coffee' && selectedCategory !== 'Signature Frappe')  {
                sizeContainer.style.display = 'none';
                temperatureContainer.style.display = 'none';
            } else {
                sizeContainer.style.display = 'block';
                temperatureContainer.style.display = 'block';
            }
        }

        // Run on page load to set initial state
        toggleFields();
        
        // Add event listener for changes
        categorySelect.addEventListener('change', toggleFields);
    });

    function openEditModal(id) {
        $.ajax({
            url: 'menuManagement.php?id=' + id,
            method: 'GET',
            success: function(response) {
                try {
                    const data = response;

                    // Populate form fields
                    document.getElementById('editMenuName').value = data.name;
                    document.getElementById('editMenuDescription').value = data.description;
                    document.getElementById('editMenuCategory').value = data.category;
                    document.getElementById('editMenuQuantity').value = data.quantity;
                    document.getElementById('editQuantityValue').innerText = data.quantity;
                    document.getElementById('editMenuPrice').value = data.price;
                    document.getElementById('editPriceValue').innerText = parseFloat(data.price).toFixed(2);
                    document.getElementById('editProductStatus').value = data.status;
                    document.getElementById('editMenuId').value = data.id;

                    // Handle checkboxes for size
                    const sizeCheckboxes = document.querySelectorAll('input[name="editMenuSize[]"]');
                    if (data.size) {
                        const sizes = data.size.split(',');
                        sizeCheckboxes.forEach(checkbox => {
                            checkbox.checked = sizes.includes(checkbox.value);
                        });
                    }

                    // Handle checkboxes for temperature
                    const temperatureCheckboxes = document.querySelectorAll('input[name="editMenuTemperature[]"]');
                    if (data.temperature) {
                        const temps = data.temperature.split(',');
                        temperatureCheckboxes.forEach(checkbox => {
                            checkbox.checked = temps.includes(checkbox.value);
                        });
                    }

                    // Show or hide size and temperature containers based on category
                    const selectedCategory = data.category;
                    const editSizeContainer = document.getElementById('editMenuSizeContainer');
                    const editTemperatureContainer = document.getElementById('editMenuTemperatureContainer');

                    if (selectedCategory === 'Signature Frappe' || selectedCategory === 'Non-Coffee' || selectedCategory === 'Coffee') {
                        editSizeContainer.style.display = 'block';
                        editTemperatureContainer.style.display = 'block';
                    } else {
                        editSizeContainer.style.display = 'none';
                        editTemperatureContainer.style.display = 'none';
                    }

                    // Show the modal
                    var editModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
                    editModal.show();
                } catch (error) {
                    console.error('Error processing response:', error);
                    alert('Error fetching item details. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error fetching item details: ' + error);
            }
        });
    }



    function saveEdit() {
        const formData = new FormData(document.getElementById('editMenuForm'));
        formData.append('editMenuItem', true);

        $.ajax({
            url: '',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                alert('Error updating item: ' + error);
            }
        });
    }
</script>

<script>
    $(document).ready(function () {
        $('#search').on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $('.menu-card').filter(function () {
                $(this).toggle($(this).find('.card-title').text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    
    function confirmDelete(menuId) {
        const deleteMenuIdElement = document.getElementById("deleteMenuId");
        if (deleteMenuIdElement) {
            deleteMenuIdElement.value = menuId;
        } else {
            console.error("Element with ID 'deleteMenuId' not found.");
        }
        var deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"));
        deleteModal.show();
    }

    function deleteMenuItem() {
        const menuId = document.getElementById("deleteMenuId").value;
        $.ajax({
            url: '',
            method: 'POST',
            data: { 
                deleteMenuItem: true,
                menuId: menuId 
            },
            success: function(response) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                alert('Error deleting item: ' + error);
            }
        });
    } 

    function filterByCategory() {
        var selectedCategory = document.getElementById('categoryFilter').value;
        window.location.href = '?category=' + selectedCategory;
    }
</script>
<script>
    // Quantity and price slider value display
    const quantitySlider = document.getElementById('menuQuantity');
    const quantityValue = document.getElementById('quantityValue');
    if (quantitySlider && quantityValue) {
        quantitySlider.oninput = function() {
            quantityValue.innerText = this.value;
        };
    }

    const priceSlider = document.getElementById('menuPrice');
    const priceValue = document.getElementById('priceValue');
    if (priceSlider && priceValue) {
        priceSlider.oninput = function() {
            priceValue.innerText = parseFloat(this.value).toFixed(2);
        };
    }

    // Edit form sliders
    const editQuantitySlider = document.getElementById('editMenuQuantity');
    const editQuantityValue = document.getElementById('editQuantityValue');
    if (editQuantitySlider && editQuantityValue) {
        editQuantitySlider.oninput = function() {
            editQuantityValue.innerText = this.value;
        };
    }

    const editPriceSlider = document.getElementById('editMenuPrice');
    const editPriceValue = document.getElementById('editPriceValue');
    if (editPriceSlider && editPriceValue) {
        editPriceSlider.oninput = function() {
            editPriceValue.innerText = parseFloat(this.value).toFixed(2);
        };
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterDropdown = document.querySelector('.dropdown');
        const filterIconButton = document.getElementById('filterIconButton');

        if (filterDropdown && filterIconButton) {
            filterDropdown.addEventListener('show.bs.dropdown', function () {
                filterIconButton.style.display = 'none';
            });

            filterDropdown.addEventListener('hide.bs.dropdown', function () {
                filterIconButton.style.display = 'block';
            });
        }
    });
</script>
</body>
</html>