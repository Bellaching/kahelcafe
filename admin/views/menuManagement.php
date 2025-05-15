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

$errors = [
    'menuImage' => '',
    'menuName' => '',
    'menuDescription' => '',
    'menuCategory' => '',
    'menuSize' => '',
    'menuTemperature' => '',
    'menuQuantity' => '',
    'menuPriceSmall' => '',
    'menuPriceMedium' => '',
    'menuPriceLarge' => '',
    'menuPriceFood' => '',
    'productStatus' => '',
    'general' => [],
    'editMenuImage' => '',
    'editMenuName' => '',
    'editMenuDescription' => '',
    'editMenuCategory' => '',
    'editMenuSize' => '',
    'editMenuTemperature' => '',
    'editMenuQuantity' => '',
    'editMenuPriceSmall' => '',
    'editMenuPriceMedium' => '',
    'editMenuPriceLarge' => '',
    'editMenuPriceFood' => '',
    'editProductStatus' => ''
];

$drinkCategories = ['Espresso', 'Non-Coffee', 'Signatures', 'Frappe'];
$foodCategories = ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons', 'Upsize'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
    $menuImage = '';
    $menuName = isset($_POST['menuName']) ? $conn->real_escape_string($_POST['menuName']) : '';
    $menuDescription = isset($_POST['menuDescription']) ? $conn->real_escape_string($_POST['menuDescription']) : '';
    $menuCategory = isset($_POST['menuCategory']) ? $conn->real_escape_string($_POST['menuCategory']) : '';
    $menuSize = isset($_POST['menuSize']) ? $_POST['menuSize'] : [];
    $menuTemperature = isset($_POST['menuTemperature']) ? $_POST['menuTemperature'] : [];
    $menuQuantity = isset($_POST['menuQuantity']) ? intval($_POST['menuQuantity']) : 0;
    $menuPriceSmall = isset($_POST['menuPriceSmall']) ? floatval($_POST['menuPriceSmall']) : 0;
    $menuPriceMedium = isset($_POST['menuPriceMedium']) ? floatval($_POST['menuPriceMedium']) : 0;
    $menuPriceLarge = isset($_POST['menuPriceLarge']) ? floatval($_POST['menuPriceLarge']) : 0;
    $menuPriceFood = isset($_POST['menuPriceFood']) ? floatval($_POST['menuPriceFood']) : 0;
    $productStatus = isset($_POST['productStatus']) ? $conn->real_escape_string($_POST['productStatus']) : 'Available';

    $menuType = in_array($menuCategory, $drinkCategories) ? 'drink' : 'food';
    $hasErrors = false;

    // Validation omitted for brevity...

    if (empty(array_filter($errors))) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            $errors['menuImage'] = "Error uploading image.";
            $hasErrors = true;
        }

        if (!$hasErrors) {
            $sizeStr = !empty($menuSize) ? implode(',', $menuSize) : '';
            $tempStr = !empty($menuTemperature) ? implode(',', $menuTemperature) : '';
            $priceData = [];
            
            if ($menuType === 'drink') {
                if (in_array('Small', $menuSize)) $priceData['Small'] = $menuPriceSmall;
                if (in_array('Medium', $menuSize)) $priceData['Medium'] = $menuPriceMedium;
                if (in_array('Large', $menuSize)) $priceData['Large'] = $menuPriceLarge;
            } else {
                $priceData['Regular'] = $menuPriceFood;
            }
            
            $priceStr = json_encode($priceData);

            $sql = "INSERT INTO menu1 (image, name, description, category, size, temperature, quantity, price, status, type)
                    VALUES ('$menuImage', '$menuName', '$menuDescription', '$menuCategory', '$sizeStr', '$tempStr', $menuQuantity, '$priceStr', '$productStatus', '$menuType')";

            if ($conn->query($sql)) {
                header("Location: menuManagement.php");
                exit();
            } else {
                $errors['general'][] = "Database error: " . $conn->error;
            }
        }
    }
}

// DELETE ITEM
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
        
        // Decode the price JSON to get the food price if it exists
        $priceData = json_decode($row['price'], true);
        $foodPrice = isset($priceData['Regular']) ? $priceData['Regular'] : 0;
        
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
            'image' => $row['image'],
            'foodPrice' => $foodPrice  // Make sure this is included
        ]);
        exit();
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Item not found']);
        exit();
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editMenuItem'])) {
    $menuId = isset($_POST['editMenuId']) ? intval($_POST['editMenuId']) : 0;
    $menuName = isset($_POST['editMenuName']) ? $conn->real_escape_string($_POST['editMenuName']) : '';
    $menuDescription = isset($_POST['editMenuDescription']) ? $conn->real_escape_string($_POST['editMenuDescription']) : '';
    $menuCategory = isset($_POST['editMenuCategory']) ? $conn->real_escape_string($_POST['editMenuCategory']) : '';
    $menuSize = isset($_POST['editMenuSize']) ? $_POST['editMenuSize'] : [];
    $menuTemperature = isset($_POST['editMenuTemperature']) ? $_POST['editMenuTemperature'] : [];
    $menuQuantity = isset($_POST['editMenuQuantity']) ? intval($_POST['editMenuQuantity']) : 0;
    $menuPriceSmall = isset($_POST['editMenuPriceSmall']) ? floatval($_POST['editMenuPriceSmall']) : 0;
    $menuPriceMedium = isset($_POST['editMenuPriceMedium']) ? floatval($_POST['editMenuPriceMedium']) : 0;
    $menuPriceLarge = isset($_POST['editMenuPriceLarge']) ? floatval($_POST['editMenuPriceLarge']) : 0;
    $menuPriceFood = isset($_POST['editMenuPriceFood']) ? floatval($_POST['editMenuPriceFood']) : 0;
    $productStatus = isset($_POST['editProductStatus']) ? $conn->real_escape_string($_POST['editProductStatus']) : '';

    // Determine menu type (food or drink)
    $menuType = in_array($menuCategory, $drinkCategories) ? 'drink' : 'food';

    if (empty($menuName)) {
        $errors['editMenuName'] = "Menu name is required.";
    }
    if (empty($menuDescription)) {
        $errors['editMenuDescription'] = "Description is required.";
    }
    if (empty($menuCategory)) {
        $errors['editMenuCategory'] = "Category is required.";
    }
    
    if ($menuType === 'drink') {
        if (empty($menuSize)) {
            $errors['editMenuSize'] = "Please select at least one size.";
        }
        if (empty($menuTemperature)) {
            $errors['editMenuTemperature'] = "Please select at least one temperature.";
        }
        
        // Validate prices for selected sizes
        if (in_array('Small', $menuSize) && $menuPriceSmall <= 0) {
            $errors['editMenuPriceSmall'] = "Price for Small must be greater than 0.";
        }
        if (in_array('Medium', $menuSize) && $menuPriceMedium <= 0) {
            $errors['editMenuPriceMedium'] = "Price for Medium must be greater than 0.";
        }
        if (in_array('Large', $menuSize) && $menuPriceLarge <= 0) {
            $errors['editMenuPriceLarge'] = "Price for Large must be greater than 0.";
        }
    } else {
        if ($menuPriceFood <= 0) {
            $errors['editMenuPriceFood'] = "Price must be greater than 0.";
        }
    }
    
    if ($menuQuantity <= 0) {
        $errors['editMenuQuantity'] = "Quantity must be greater than 0.";
    }
    if (empty($productStatus)) {
        $errors['editProductStatus'] = "Product status is required.";
    }

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

    $editErrors = array_filter([
        $errors['editMenuName'],
        $errors['editMenuDescription'],
        $errors['editMenuCategory'],
        $errors['editMenuSize'],
        $errors['editMenuTemperature'],
        $errors['editMenuQuantity'],
        $errors['editMenuPriceSmall'],
        $errors['editMenuPriceMedium'],
        $errors['editMenuPriceLarge'],
        $errors['editMenuPriceFood'],
        $errors['editProductStatus'],
        $errors['editMenuImage']
    ]);

    if (empty($editErrors)) {
        $sizeStr = !empty($menuSize) ? implode(',', $menuSize) : '';
        $tempStr = !empty($menuTemperature) ? implode(',', $menuTemperature) : '';
        $priceData = [];
        
        if ($menuType === 'drink') {
            if (in_array('Small', $menuSize)) $priceData['Small'] = $menuPriceSmall;
            if (in_array('Medium', $menuSize)) $priceData['Medium'] = $menuPriceMedium;
            if (in_array('Large', $menuSize)) $priceData['Large'] = $menuPriceLarge;
        } else {
            $priceData['Regular'] = $menuPriceFood;
        }
        
        $priceStr = json_encode($priceData);
        
        $sql = "UPDATE menu1 SET 
                name = '$menuName', 
                description = '$menuDescription', 
                category = '$menuCategory', 
                size = '$sizeStr', 
                temperature = '$tempStr', 
                quantity = $menuQuantity, 
                price = '$priceStr', 
                status = '$productStatus',
                type = '$menuType'";

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  
    <style>
        body{
            display: flex;
            flex-direction: column;
        }
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
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .out-of-stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .price-input-container {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .price-input-container label {
            font-weight: bold;
        }
        .price-input {
            display: none;
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
            $priceData = json_decode($row['price'], true);
            $priceStr = '';

            // Determine if this is a food item
            $isFood = in_array($row['category'], ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons', 'Upsize']);
            
            if ($priceData) {
                if ($isFood) {
                    // For food items, just show the Regular price
                    $priceStr = isset($priceData['Regular']) ? '₱' . number_format($priceData['Regular'], 2) : '₱0.00';
                } else {
                    // For drinks, show all sizes with prices
                    $prices = [];
                    if (isset($priceData['Small'])) $prices[] = 'Small: ₱' . number_format($priceData['Small'], 2);
                    if (isset($priceData['Medium'])) $prices[] = 'Medium: ₱' . number_format($priceData['Medium'], 2);
                    if (isset($priceData['Large'])) $prices[] = 'Large: ₱' . number_format($priceData['Large'], 2);
                    $priceStr = implode(' | ', $prices);
                }
            }
            $image = htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8');
            $id = intval($row['id']);
            $quantity = intval($row['quantity']);
            $isOutOfStock = ($quantity <= 0);

            $output .= '
           <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
              <div class="card p-2 rounded-1" style="border: none; position: relative;">';
            
            if ($isOutOfStock) {
                $output .= '<span class="out-of-stock-badge">Out of Stock</span>';
            }

            $output .= '
                    <div class="img-container" style="overflow: hidden; height: 150px;">
                        <img src="' . $image . '" class="card-img-top" alt="' . $name . '" style="height: 100%; width: 100%; object-fit: cover;">
                    </div>
                    <div class="card-body text-center p-1">
                        <div class="card-title" style="font-size: 1rem; display: flex; flex-direction: column; align-items: flex-start;">
                            <strong><h5 style="margin: 0;">' . $name . '</h5></strong>
                            <p class="card-text text-success" style="font-size: 0.9rem; margin: 0; text-align: left;">
                                <strong>' . $priceStr . '</strong>
                            </p>
                        </div>
                        <button class="btn btn-edit btn-primary" style="background-color:#FF902B; border:none; border-radius:3rem; padding: 0.5rem;" onclick="openEditModal(' . $id . ')">
                            <i class="fa-solid fa-edit"></i> Edit
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
                                    <option value="Espresso" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Espresso') ? 'selected' : '' ?>>Espresso</option>
                                    <option value="Non-Coffee" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Non-Coffee') ? 'selected' : '' ?>>Non-Coffee</option>
                                    <option value="Signatures" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Signatures') ? 'selected' : '' ?>>Signatures</option>
                                    <option value="Frappe" <?php echo (isset($_POST['editMenuCategory']) && $_POST['editMenuCategory'] == 'Frappe') ? 'selected' : '' ?>>Frappe</option>
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
                                    <input type="checkbox" name="editMenuSize[]" value="Medium" id="editSizeMedium" <?php echo (isset($_POST['editMenuSize']) && in_array('Medium', $_POST['editMenuSize']) ? 'checked' : '' )?>>
                                    <label for="editSizeMedium" class="me-3">Medium</label>
                                    <input type="checkbox" name="editMenuSize[]" value="Large" id="editSizeLarge" <?php echo (isset($_POST['editMenuSize']) && in_array('Large', $_POST['editMenuSize']) ? 'checked' : '' )?>>
                                    <label for="editSizeLarge">Large</label>
                                </div>
                                <?php if (!empty($errors['editMenuSize'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['editMenuSize']); ?></div>
                                <?php endif; ?>
                            </div>


                            <div class="mb-3 container-fluid" id="editMenuTemperatureContainer">
                                <label for="editMenuTemperature" class="form-label">Temperature</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="editMenuTemperature[]" value="Hot" id="editTemperatureHot" <?php echo (isset($_POST['editMenuTemperature']) && in_array('Hot', $_POST['editMenuTemperature']) ? 'checked' : '' )?>>
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
                            <div class="mb-3 price-input-container" id="editPriceInputContainer">
                                <label class="form-label">Price</label>
                                <div class="mb-2 price-input" id="editPriceSmallContainer">
                                    <label for="editMenuPriceSmall" class="form-label">Small</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['editMenuPriceSmall']) ? 'is-invalid' : '' ?>" id="editMenuPriceSmall" name="editMenuPriceSmall" min="0" step="0.01" value="<?php echo isset($_POST['editMenuPriceSmall']) ? htmlspecialchars($_POST['editMenuPriceSmall']) : '0' ?>">
                                    <?php if (!empty($errors['editMenuPriceSmall'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['editMenuPriceSmall']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2 price-input" id="editPriceMediumContainer">
                                    <label for="editMenuPriceMedium" class="form-label">Medium</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['editMenuPriceMedium']) ? 'is-invalid' : '' ?>" id="editMenuPriceMedium" name="editMenuPriceMedium" min="0" step="0.01" value="<?php echo isset($_POST['editMenuPriceMedium']) ? htmlspecialchars($_POST['editMenuPriceMedium']) : '0' ?>">
                                    <?php if (!empty($errors['editMenuPriceMedium'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['editMenuPriceMedium']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="price-input" id="editPriceLargeContainer">
                                    <label for="editMenuPriceLarge" class="form-label">Large</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['editMenuPriceLarge']) ? 'is-invalid' : '' ?>" id="editMenuPriceLarge" name="editMenuPriceLarge" min="0" step="0.01" value="<?php echo isset($_POST['editMenuPriceLarge']) ? htmlspecialchars($_POST['editMenuPriceLarge']) : '0' ?>">
                                    <?php if (!empty($errors['editMenuPriceLarge'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['editMenuPriceLarge']); ?></div>
                                    <?php endif; ?>
                                </div>

                            </div>

                            
                                  
                            <div class="mb-3">
                                <label for="editMenuQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control <?php echo !empty($errors['editMenuQuantity']) ? 'is-invalid' : '' ?>" id="editMenuQuantity" name="editMenuQuantity" min="1" max="100" value="<?php echo isset($_POST['editMenuQuantity']) ? htmlspecialchars($_POST['editMenuQuantity']) : '1' ?>">
                                <?php if (!empty($errors['editMenuQuantity'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['editMenuQuantity']); ?></div>
                                <?php endif; ?>
                            </div>
<div class="food-price-input" id="editPriceFoodContainer" style="display: none;">
    <label for="editMenuPriceFood" class="form-label">Price</label>
    <input type="number" 
        class="form-control <?php echo !empty($errors['editMenuPriceFood']) ? 'is-invalid' : '' ?>" 
        id="editMenuPriceFood" 
        name="editMenuPriceFood" 
        min="0" 
        step="0.01" 
        value="<?php echo isset($_POST['editMenuPriceFood']) ? htmlspecialchars($_POST['editMenuPriceFood']) : '0' ?>">
    <?php if (!empty($errors['editMenuPriceFood'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($errors['editMenuPriceFood']); ?></div>
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
                                    <option value="Espresso" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Espresso') ? 'selected' : '' ?>>Espresso</option>
                                    <option value="Non-Coffee" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Non-Coffee') ? 'selected' : '' ?>>Non-Coffee</option>
                                    <option value="Signatures" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Signatures') ? 'selected' : '' ?>>Signatures</option>
                                  
                                    <option value="Frappe" <?php echo (isset($_POST['menuCategory']) && $_POST['menuCategory'] == 'Frappe') ? 'selected' : '' ?>>Frappe</option>
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

                           <!-- For checkboxes -->
<div class="mb-3 container-fluid" id="menuSizeContainer">
    <label for="menuSize" class="form-label">Size</label>
    <div class="container-fluid">
        <input type="checkbox" name="menuSize[]" value="Small" id="sizeSmall" <?php echo (isset($_POST['menuSize']) && in_array('Small', $_POST['menuSize']) ? 'checked' : '') ?>>
        <label for="sizeSmall" class="me-3">Small</label>
        <input type="checkbox" name="menuSize[]" value="Medium" id="sizeMedium" <?php echo (isset($_POST['menuSize']) && in_array('Medium', $_POST['menuSize']) ? 'checked' : '' )?>>
        <label for="sizeMedium" class="me-3">Medium</label>
        <input type="checkbox" name="menuSize[]" value="Large" id="sizeLarge" <?php echo (isset($_POST['menuSize']) && in_array('Large', $_POST['menuSize']) ? 'checked' : '' )?>>
        <label for="sizeLarge">Large</label>
    </div>
    <?php if (!empty($errors['menuSize'])): ?>
        <div class="checkbox-error"><?php echo htmlspecialchars($errors['menuSize']); ?></div>
    <?php endif; ?>
</div>
                            <div class="mb-3 container-fluid" id="menuTemperatureContainer">
                                <label for="menuTemperature" class="form-label">Temperature</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="menuTemperature[]" value="Hot" id="temperatureHot" <?php echo (isset($_POST['menuTemperature']) && in_array('Hot', $_POST['menuTemperature']) ? 'checked' : '' )?>>
                                    <label for="temperatureHot" class="me-3">Hot</label>
                                    <input type="checkbox" name="menuTemperature[]" value="Warm" id="temperatureWarm" <?php echo (isset($_POST['menuTemperature']) && in_array('Warm', $_POST['menuTemperature']) ? 'checked' : '' )?>>
                                    <label for="temperatureWarm" class="me-3">Warm</label>
                                    <input type="checkbox" name="menuTemperature[]" value="Cold" id="temperatureCold" <?php echo (isset($_POST['menuTemperature']) && in_array('Cold', $_POST['menuTemperature']) ? 'checked' : '' )?>>
                                    <label for="temperatureCold">Cold</label>
                                </div>
                                <?php if (!empty($errors['menuTemperature'])): ?>
                                    <div class="checkbox-error"><?php echo htmlspecialchars($errors['menuTemperature']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 price-input-container" id="priceInputContainer">
                                <label class="form-label">Price</label>
                                <div class="mb-2 price-input" id="priceSmallContainer">
                                    <label for="menuPriceSmall" class="form-label">Small</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['menuPriceSmall']) ? 'is-invalid' : '' ?>" id="menuPriceSmall" name="menuPriceSmall" min="0" step="0.01" value="<?php echo isset($_POST['menuPriceSmall']) ? htmlspecialchars($_POST['menuPriceSmall']) : '0' ?>">
                                    <?php if (!empty($errors['menuPriceSmall'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['menuPriceSmall']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2 price-input" id="priceMediumContainer">
                                    <label for="menuPriceMedium" class="form-label">Medium</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['menuPriceMedium']) ? 'is-invalid' : '' ?>" id="menuPriceMedium" name="menuPriceMedium" min="0" step="0.01" value="<?php echo isset($_POST['menuPriceMedium']) ? htmlspecialchars($_POST['menuPriceMedium']) : '0' ?>">
                                    <?php if (!empty($errors['menuPriceMedium'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['menuPriceMedium']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="price-input" id="priceLargeContainer">
                                    <label for="menuPriceLarge" class="form-label">Large</label>
                                    <input type="number" class="form-control <?php echo !empty($errors['menuPriceLarge']) ? 'is-invalid' : '' ?>" id="menuPriceLarge" name="menuPriceLarge" min="0" step="0.01" value="<?php echo isset($_POST['menuPriceLarge']) ? htmlspecialchars($_POST['menuPriceLarge']) : '0' ?>">
                                    <?php if (!empty($errors['menuPriceLarge'])): ?>
                                        <div class="error-message"><?php echo htmlspecialchars($errors['menuPriceLarge']); ?></div>
                                    <?php endif; ?>
                                </div>

                                 

                            </div>

                            <div class="mb-3">
                                <label for="menuQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control <?php echo !empty($errors['menuQuantity']) ? 'is-invalid' : '' ?>" id="menuQuantity" name="menuQuantity" min="1" max="100" value="<?php echo isset($_POST['menuQuantity']) ? htmlspecialchars($_POST['menuQuantity']) : '1' ?> ">
                                <?php if (!empty($errors['menuQuantity'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['menuQuantity']); ?></div>
                                <?php endif; ?>
                            </div>

                            
                             <div class="food-price-input" id="priceFoodContainer" style="display: block;">
    <label for="menuPriceFood" class="form-label">Price</label>
    <input type="number" class="form-control" id="menuPriceFood" name="menuPriceFood" min="0" step="0.01" disabled>
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

    <!-- jQuery, DataTable, and Bootstrap JS -->
     <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // For Add Form
        const addCategorySelect = document.getElementById('menuCategory');
        const addSizeContainer = document.getElementById('menuSizeContainer');
        const addTemperatureContainer = document.getElementById('menuTemperatureContainer');
        const addPriceContainer = document.getElementById('priceInputContainer');
        
        // For Edit Form
        const editCategorySelect = document.getElementById('editMenuCategory');
        const editSizeContainer = document.getElementById('editMenuSizeContainer');
        const editTemperatureContainer = document.getElementById('editMenuTemperatureContainer');
        const editPriceContainer = document.getElementById('editPriceFoodContainer');


      function toggleFields(selectElement, sizeContainer, tempContainer, priceContainer) {
    const selectedCategory = selectElement.value;

    const hasSizes = ['Espresso', 'Non-Coffee', 'Signatures', 'Frappe'].includes(selectedCategory);
    const hasSinglePrice = ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons', 'Upsize'].includes(selectedCategory);

    // Show/hide size and temperature sections
    sizeContainer.style.display = hasSizes ? 'block' : 'none';
    tempContainer.style.display = hasSizes ? 'block' : 'none';
    priceContainer.style.display = hasSizes ? 'block' : 'none';

    // Handle single price input
    const priceFoodInput = document.getElementById('menuPriceFood');
    const priceFoodContainer = document.getElementById('priceFoodContainer');

    if (hasSinglePrice) {
        priceFoodContainer.style.display = 'block';
        priceFoodInput.disabled = false;
    } else {
        priceFoodContainer.style.display = 'none';
        priceFoodInput.disabled = true;
    }
}



        // Initialize both forms
        toggleFields(addCategorySelect, addSizeContainer, addTemperatureContainer, addPriceContainer);
        toggleFields(editCategorySelect, editSizeContainer, editTemperatureContainer, editPriceContainer);
        
        // Add event listeners for both forms
        addCategorySelect.addEventListener('change', function() {
            toggleFields(addCategorySelect, addSizeContainer, addTemperatureContainer, addPriceContainer);
        });
        
        editCategorySelect.addEventListener('change', function() {
            toggleFields(editCategorySelect, editSizeContainer, editTemperatureContainer, editPriceContainer);
        });

        function handleSizeCheckboxChanges(prefix = '') {
            const smallCheckbox = document.getElementById(prefix + 'sizeSmall');
            const mediumCheckbox = document.getElementById(prefix + 'sizeMedium');
            const largeCheckbox = document.getElementById(prefix + 'sizeLarge');
            
            const smallPriceContainer = document.getElementById(prefix + 'priceSmallContainer');
            const mediumPriceContainer = document.getElementById(prefix + 'priceMediumContainer');
            const largePriceContainer = document.getElementById(prefix + 'priceLargeContainer');
            
            function updatePriceVisibility() {
                smallPriceContainer.style.display = smallCheckbox.checked ? 'block' : 'none';
                mediumPriceContainer.style.display = mediumCheckbox.checked ? 'block' : 'none';
                largePriceContainer.style.display = largeCheckbox.checked ? 'block' : 'none';
                
                // Clear price value when unchecked
                if (!smallCheckbox.checked) {
                    document.getElementById(prefix + 'menuPriceSmall').value = '';
                }
                if (!mediumCheckbox.checked) {
                    document.getElementById(prefix + 'menuPriceMedium').value = '';
                }
                if (!largeCheckbox.checked) {
                    document.getElementById(prefix + 'menuPriceLarge').value = '';
                }
            }
            
            // Set initial state
            updatePriceVisibility();
            
            // Add event listeners
            smallCheckbox.addEventListener('change', updatePriceVisibility);
            mediumCheckbox.addEventListener('change', updatePriceVisibility);
            largeCheckbox.addEventListener('change', updatePriceVisibility);
        }

        // Initialize for both forms
        handleSizeCheckboxChanges(); // Add form
        handleSizeCheckboxChanges('edit'); // Edit form
    });

function openEditModal(id) {
    $.ajax({
        url: 'menuManagement.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Populate basic form fields
            document.getElementById('editMenuName').value = data.name;
            document.getElementById('editMenuDescription').value = data.description;
            document.getElementById('editMenuCategory').value = data.category;
            document.getElementById('editMenuQuantity').value = data.quantity;
            document.getElementById('editProductStatus').value = data.status;
            document.getElementById('editMenuId').value = data.id;

            // Parse the price JSON from the database
            let priceData = {};
            try {
                priceData = JSON.parse(data.price);
            } catch (e) {
                console.error('Error parsing price data:', e);
            }

            // Determine if this is a food or drink item
            const isFood = ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons', 'Upsize'].includes(data.category);
            
            // Get references to the price containers
            const editPriceInputContainer = document.getElementById('editPriceInputContainer');
            const editPriceFoodContainer = document.getElementById('editPriceFoodContainer');
            
            // Show/hide appropriate price fields
            if (isFood) {
                // Hide drink price fields and show food price field
                editPriceInputContainer.style.display = 'none';
                editPriceFoodContainer.style.display = 'block';
                
                // Set the food price - use 'Regular' price from priceData
                document.getElementById('editMenuPriceFood').value = priceData.Regular || 0;
            } else {
                // Hide food price field and show drink price fields
                editPriceInputContainer.style.display = 'block';
                editPriceFoodContainer.style.display = 'none';
                
                // Handle size checkboxes and their corresponding price fields
                const sizes = data.size ? data.size.split(',') : [];
                
                // Small size
                const editSizeSmall = document.getElementById('editSizeSmall');
                const editPriceSmallContainer = document.getElementById('editPriceSmallContainer');
                if (sizes.includes('Small')) {
                    editSizeSmall.checked = true;
                    editPriceSmallContainer.style.display = 'block';
                    document.getElementById('editMenuPriceSmall').value = priceData.Small || '';
                } else {
                    editSizeSmall.checked = false;
                    editPriceSmallContainer.style.display = 'none';
                    document.getElementById('editMenuPriceSmall').value = '';
                }

                // Medium size
                const editSizeMedium = document.getElementById('editSizeMedium');
                const editPriceMediumContainer = document.getElementById('editPriceMediumContainer');
                if (sizes.includes('Medium')) {
                    editSizeMedium.checked = true;
                    editPriceMediumContainer.style.display = 'block';
                    document.getElementById('editMenuPriceMedium').value = priceData.Medium || '';
                } else {
                    editSizeMedium.checked = false;
                    editPriceMediumContainer.style.display = 'none';
                    document.getElementById('editMenuPriceMedium').value = '';
                }

                // Large size
                const editSizeLarge = document.getElementById('editSizeLarge');
                const editPriceLargeContainer = document.getElementById('editPriceLargeContainer');
                if (sizes.includes('Large')) {
                    editSizeLarge.checked = true;
                    editPriceLargeContainer.style.display = 'block';
                    document.getElementById('editMenuPriceLarge').value = priceData.Large || '';
                } else {
                    editSizeLarge.checked = false;
                    editPriceLargeContainer.style.display = 'none';
                    document.getElementById('editMenuPriceLarge').value = '';
                }
            }

            // Handle temperature checkboxes
            const temperatureCheckboxes = document.querySelectorAll('input[name="editMenuTemperature[]"]');
            if (data.temperature) {
                const temps = data.temperature.split(',');
                temperatureCheckboxes.forEach(checkbox => {
                    checkbox.checked = temps.includes(checkbox.value);
                });
            }

            // Show/hide size and temperature sections based on category
            const editSizeContainer = document.getElementById('editMenuSizeContainer');
            const editTemperatureContainer = document.getElementById('editMenuTemperatureContainer');

            if (isFood) {
                editSizeContainer.style.display = 'none';
                editTemperatureContainer.style.display = 'none';
            } else {
                editSizeContainer.style.display = 'block';
                editTemperatureContainer.style.display = 'block';
            }

            // Show the modal
            var editModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
            editModal.show();
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error fetching item details. Please try again.');
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


    const foodPriceContainer = document.getElementById('priceFoodContainer');
const allowedFoodCategories = ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons', 'Upsize'];

function toggleFoodPriceContainer() {
    const selected = addCategorySelect.value;
   
        foodPriceContainer.style.display = 'block';
   
}

// Initialize and add listener
toggleFoodPriceContainer();
addCategorySelect.addEventListener('change', toggleFoodPriceContainer);

const priceFoodInputEdit = document.getElementById('editMenuPriceFood');
const priceFoodContainerEdit = document.getElementById('editPriceFoodContainer');

if (hasSinglePrice) {
    priceFoodContainerEdit.style.display = 'block';
    priceFoodInputEdit.disabled = false;
} else {
    priceFoodContainerEdit.style.display = 'none';
    priceFoodInputEdit.disabled = true;
}


</script>
</body>
</html>