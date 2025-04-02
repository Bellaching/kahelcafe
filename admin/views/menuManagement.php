<?php 

ob_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "./../../admin/views/banner.php";
include "./../../connection/connection.php";
$itemsPerPage = 6; // Change this to the number of items you want per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addMenuItem'])) {
    // Initialize variables
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

        // Ensure the target directory exists and has correct permissions
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            echo "<p class='alert alert-danger'>Error: Failed to upload image.</p>";
            exit;
        }
    }

    // Prepare SQL statement to insert data into the menu1 table
    $sql = "INSERT INTO menu1 (image, name, description, category, size, temperature, quantity, price, status)
            VALUES ('$menuImage', '$menuName', '$menuDescription', '$menuCategory', '$menuSize', '$menuTemperature', '$menuQuantity', '$menuPrice', '$productStatus')";

    // Execute query
    if ($conn->query($sql) === TRUE) {
        header("Location: menuManagement.php"); // Redirect after success
        exit();
    } else {
        // Show detailed error message if insertion fails
        echo "<p class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteMenuItem'])) {
    $menuId = intval($_POST['menuId']);
    $sql = "DELETE FROM menu1 WHERE id = $menuId";
    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $errorMessages[] = "Error deleting item: " . $conn->error;
    }
}


if (isset($_GET['id'])) {
    $menuId = intval($_GET['id']);
    $sql = "SELECT * FROM menu1 WHERE id = $menuId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Clear any previous output
        ob_clean();
        // Set JSON header
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
        // Clear any previous output
        ob_clean();
        // Set JSON header
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    exit(); // Stop further execution
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editMenuItem'])) {
    $menuId = intval($_POST['editMenuId']);
    $menuName = $conn->real_escape_string($_POST['editMenuName']);
    $menuDescription = $conn->real_escape_string($_POST['editMenuDescription']);
    $menuCategory = $conn->real_escape_string($_POST['editMenuCategory']);
    $menuSize = isset($_POST['editMenuSize']) ? implode(',', $_POST['editMenuSize']) : '';
    $menuTemperature = isset($_POST['editMenuTemperature']) ? implode(',', $_POST['editMenuTemperature']) : '';
    $menuQuantity = intval($_POST['editMenuQuantity']);
    $menuPrice = floatval($_POST['editMenuPrice']);
    $productStatus = $conn->real_escape_string($_POST['editProductStatus']);

    // Handle image upload
    $menuImage = '';
    if (isset($_FILES['editMenuImage']) && $_FILES['editMenuImage']['error'] == 0) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["editMenuImage"]["name"]);
        if (move_uploaded_file($_FILES["editMenuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        }
    }

    // Prepare SQL statement
    $sql = "UPDATE menu1 SET 
            name = '$menuName', 
            description = '$menuDescription', 
            category = '$menuCategory', 
            size = '$menuSize', 
            temperature = '$menuTemperature', 
            quantity = $menuQuantity, 
            price = $menuPrice, 
            status = '$productStatus'";

    if (!empty($menuImage)) {
        $sql .= ", image = '$menuImage'";
    }

    $sql .= " WHERE id = $menuId";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='alert alert-danger'>Error updating item: " . $conn->error . "</p>";
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>

<div class="container-fluid mb-5">
    <div class="row mt-5 justify-content-center text-center text-md-start align-items-center">
        <div class="col-12 col-md-8">
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
<!-- Add this hidden input if it doesn't exist -->
<input type="hidden" id="deleteMenuId" name="deleteMenuId">

<!-- Ensure the modal exists -->
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
                                <input type="file" class="form-control" id="editMenuImage" name="editMenuImage" accept="image/*">
                                <small class="text-muted">Leave blank to keep the current image.</small>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editMenuDescription" name="editMenuDescription" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editMenuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control" id="editMenuName" name="editMenuName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuCategory" class="form-label">Category</label>
                                <select class="form-select" id="editMenuCategory" name="editMenuCategory" required>
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
                            <div class="mb-3 container-fluid" id="editMenuSizeContainer">
                                <label for="editMenuSize" class="form-label">Size</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="editMenuSize[]" value="Small" id="editSizeSmall">
                                    <label for="editSizeSmall" class="me-3">Small</label>
                                    <input type="checkbox" name="editMenuSize[]" value="Medium" id="editSizeMedium">
                                    <label for="editSizeMedium" class="me-3">Medium</label>
                                    <input type="checkbox" name="editMenuSize[]" value="Large" id="editSizeLarge">
                                    <label for="editSizeLarge">Large</label>
                                </div>
                            </div>
                            <div class="mb-3 container-fluid" id="editMenuTemperatureContainer">
                                <label for="editMenuTemperature" class="form-label">Temperature</label>
                                <div class="container-fluid">
                                    <input type="checkbox" name="editMenuTemperature[]" value="Hot" id="editTemperatureHot">
                                    <label for="editTemperatureHot" class="me-3">Hot</label>
                                    <input type="checkbox" name="editMenuTemperature[]" value="Warm" id="editTemperatureWarm">
                                    <label for="editTemperatureWarm" class="me-3">Warm</label>
                                    <input type="checkbox" name="editMenuTemperature[]" value="Cold" id="editTemperatureCold">
                                    <label for="editTemperatureCold">Cold</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuQuantity" class="form-label">Quantity</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="editQuantityValue">1</div>
                                    <input type="range" class="form-range" id="editMenuQuantity" name="editMenuQuantity" min="1" max="100" value="1" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuPrice" class="form-label">Price</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" id="editPriceValue">0.00</div>
                                    <input type="range" class="form-range" id="editMenuPrice" name="editMenuPrice" min="0" max="1000" value="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editProductStatus" class="form-label">Status</label>
                                <select class="form-select" id="editProductStatus" name="editProductStatus" required>
                                    <option value="Available">Available</option>
                                    <option value="Unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="editMenuId" name="editMenuId">
                </form>
            </div>
            <!-- Buttons at the bottom -->
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
                                <input type="file" class="form-control" id="menuImage" name="menuImage" accept="image/*" required>
                            </div>
                            <div class="mb-3">
                                <label for="menuDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="menuDescription" name="menuDescription" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control" id="menuName" name="menuName" required>
                            </div>
                            <div class="mb-3">
                                <label for="menuCategory" class="form-label">Category</label>
                                <select class="form-select" id="menuCategory" name="menuCategory" required><option value="Coffee">Coffee</option><option value="Non-Coffee">Non-Coffee</option><option value="Signature Frappe">Signature Frappe</option><option value="Starters">Starters</option><option value="Pasta">Pasta</option><option value="Sandwich">Sandwich</option><option value="Rice Meal">Rice Meal</option><option value="All Day Breakfast">All Day Breakfast</option>
                                </select>
                            </div>
                            <div class="mb-3 container-fluid" id="menuSizeContainer">
    <label for="menuSize" class="form-label">Size</label>
    <div class="container-fluid">
        <input type="checkbox" name="menuSize[]" value="Small" id="sizeSmall">
        <label for="sizeSmall" class="me-3">Small</label>
        <input type="checkbox" name="menuSize[]" value="Medium" id="sizeMedium">
        <label for="sizeMedium" class="me-3">Medium</label>
        <input type="checkbox" name="menuSize[]" value="Large" id="sizeLarge">
        <label for="sizeLarge">Large</label>
    </div>
</div>

<div class="mb-3 container-fluid" id="menuTemperatureContainer">
    <label for="menuTemperature" class="form-label">Temperature</label>
    <div class="container-fluid">
        <input type="checkbox" name="menuTemperature[]" value="Hot" id="temperatureHot">
        <label for="temperatureHot" class="me-3">Hot</label>
        <input type="checkbox" name="menuTemperature[]" value="Warm" id="temperatureWarm">
        <label for="temperatureWarm" class="me-3">Warm</label>
        <input type="checkbox" name="menuTemperature[]" value="Cold" id="temperatureCold">
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
  
  </div>            </div>        <div class="container">
    <div class="row">
        <div class="col-6">
            <button type="button" class=" container-fluid close-add" data-bs-dismiss="modal" aria-label="Close">Close</button>
        </div>
        <div class="col-6">
            <button type="submit" name="addMenuItem" class=" btn-add-item container-fluid text-light">Add Menu Item</button>
        </div>
    </div>
</div>
</div>
</form>
</div>
</div>
</div>
</div>



<div class="pagination-container d-flex justify-content-center my-4">
    <nav>
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>&category=<?= $selectedCategory ?>" style="background-color: #FF902B; color: #FFFFFF;">Previous</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" style="background-color: #FF902B; color: #FFFFFF;">Previous</span>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&category=<?= $selectedCategory ?>" style="background-color: #FF902B; color: #FFFFFF;"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>&category=<?= $selectedCategory ?>" style="background-color: #FF902B; color: #FFFFFF;">Next</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" style="background-color: #FF902B; color: #FFFFFF;">Next</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get the category select element
        const categorySelect = document.getElementById('menuCategory');
        const sizeContainer = document.getElementById('menuSizeContainer');
        const temperatureContainer = document.getElementById('menuTemperatureContainer');

        // Function to check the category and toggle the size and temperature fields
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

        // Run the function on page load to check the initial state
        toggleFields();

        // Add an event listener to run the function whenever the category changes
        categorySelect.addEventListener('change', toggleFields);
    });

    function openEditModal(id) {
    $.ajax({
        url: 'menuManagement.php?id=' + id,
        method: 'GET',
        success: function(response) {
            console.log(response); // Log the response to see what is being returned
            try {
                // No need to parse the response, it's already an object
                const data = response;

                // Populate the modal fields with the data
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
                sizeCheckboxes.forEach(checkbox => {
                    checkbox.checked = data.size.includes(checkbox.value);
                });

                 // Show or hide size and temperature containers based on the category
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

                // Handle checkboxes for temperature
                const temperatureCheckboxes = document.querySelectorAll('input[name="editMenuTemperature[]"]');
                temperatureCheckboxes.forEach(checkbox => {
                    checkbox.checked = data.temperature.includes(checkbox.value);
                });

                // Show the modal
                var editModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
                editModal.show();
            } catch (error) {
                console.error('Error processing response:', error);
                console.log('Server response:', response);
                alert('Error fetching item details. Please check the console for more information.');
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
    let menuIdToDelete;

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
        url: '', // This should be the same page
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

function filterByCategory() {var selectedCategory = document.getElementById('categoryFilter').value;window.location.href = '?category=' + selectedCategory}
</script>
<script>
const quantitySlider = document.getElementById('menuQuantity');
const quantityValue = document.getElementById('quantityValue');
quantitySlider.oninput = function() {quantityValue.innerText = this.value;};

const priceSlider = document.getElementById('menuPrice');
const priceValue = document.getElementById('priceValue');
priceSlider.oninput = function() {priceValue.innerText = parseFloat(this.value).toFixed(2);};



// Quantity Slider for Edit Menu
const editQuantitySlider = document.getElementById('editMenuQuantity');
const editQuantityValue = document.getElementById('editQuantityValue');
if (editQuantitySlider && editQuantityValue) {
    editQuantitySlider.oninput = function() {
        editQuantityValue.innerText = this.value;
    };
}

// Price Slider for Edit Menu
const editPriceSlider = document.getElementById('editMenuPrice');
const editPriceValue = document.getElementById('editPriceValue');
if (editPriceSlider && editPriceValue) {
    editPriceSlider.oninput = function() {
        editPriceValue.innerText = parseFloat(this.value).toFixed(2);
    };
}
</script>
<script>
    // Add event listeners to the dropdown to toggle the filter icon button visibility
    document.addEventListener('DOMContentLoaded', function () {
        const filterDropdown = document.querySelector('.dropdown');
        const filterIconButton = document.getElementById('filterIconButton'); // Add this ID to your filter icon button

        filterDropdown.addEventListener('show.bs.dropdown', function () {
            filterIconButton.style.display = 'none';
        });

        filterDropdown.addEventListener('hide.bs.dropdown', function () {
            filterIconButton.style.display = 'block';
        });
    });
</script>
</body>
</html>