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
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode([]);
    }

}

$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);
$sql = "SELECT * FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);
ob_end_flush();
?>
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
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                <!-- Add the close button (X) here -->
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this menu item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
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
</div></form></div></div></div></div>
<div class="modal fade" id="updateMenuModal" tabindex="-1" aria-labelledby="updateMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateMenuModalLabel">Update Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateMenuForm" enctype="multipart/form-data">
                    <input type="hidden" id="updateMenuId" name="menuId">
                    <input type="hidden" id="existingImage" name="existingImage">
                    <div class="mb-3">
                        <label for="updateMenuName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="updateMenuName" name="menuName" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateMenuDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="updateMenuDescription" name="menuDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="updateMenuCategory" class="form-label">Category</label>
                        <input type="text" class="form-control" id="updateMenuCategory" name="menuCategory" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateMenuImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="updateMenuImage" name="menuImage">
                    </div>
                    <div class="mb-3">
                        <label for="updateMenuQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="updateMenuQuantity" name="menuQuantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateMenuPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="updateMenuPrice" name="menuPrice" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateProductStatus" class="form-label">Status</label><select class="form-select" id="updateProductStatus" name="productStatus" required><option value="available">Available</option><option value="unavailable">Unavailable</option></select></div><button type="submit" name="updateMenuItem" class="btn btn-primary">Update Menu Item</button>
                </form></div></div></div></div>

                <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this menu item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST">
                    <input type="hidden" name="menuId" id="deleteMenuId" value="">
                    <button type="submit" name="deleteMenuItem" class="btn btn-danger">Delete</button>
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
            if (selectedCategory !== 'Coffee' && selectedCategory !== 'Non-Coffee') {
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
</script>

<script>
function openUpdateMenuModal(menuId) {
    fetch(`?id=${menuId}`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.getElementById('updateMenuId').value = data.id; document.getElementById('updateMenuName').value = data.name;document.getElementById('updateMenuDescription').value = data.description;document.getElementById('updateMenuCategory').value = data.category;document.getElementById('existingImage').value = data.image;
                $('#updateMenuModal').modal('show');
            } else {
                alert('Menu item not found.');
            }
        })
        .catch(error => console.error('Error fetching menu item:', error));
}
document.getElementById('updateMenuForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission
    const formData = new FormData(this);
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload(); // Reload the page after successful update
        } else {
            alert('Error updating menu item.');
        }
    })
    .catch(error => console.error('Error updating menu item:', error));
});
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
    $('#confirmDeleteModal').modal('show');
    $('#confirmDeleteButton').off('click').on('click', function() {
        // Send the delete request using AJAX or form submission
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
        
        // Close the modal after sending the request
        $('#confirmDeleteModal').modal('hide');
    });
}
function confirmDelete(menuId) {
        document.getElementById("deleteMenuId").value = menuId;
        var deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"));
        deleteModal.show();
    }

function filterByCategory() {var selectedCategory = document.getElementById('categoryFilter').value;window.location.href = '?category=' + selectedCategory}
</script><script>const quantitySlider = document.getElementById('menuQuantity');
const quantityValue = document.getElementById('quantityValue');quantitySlider.oninput = function() {quantityValue.innerText = this.value;};
const priceSlider = document.getElementById('menuPrice');const priceValue = document.getElementById('priceValue');
priceSlider.oninput = function() {priceValue.innerText = parseFloat(this.value).toFixed(2);};
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