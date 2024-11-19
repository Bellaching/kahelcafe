<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateMenuItem'])) {
    $menuId = intval($_POST['menuId']);
    $menuName = $conn->real_escape_string($_POST['menuName']);
    $menuDescription = $conn->real_escape_string($_POST['menuDescription']);
    $menuCategory = $conn->real_escape_string($_POST['menuCategory']);

    // Handle image upload (if a new image is provided)
    if (isset($_FILES['menuImage']) && $_FILES['menuImage']['error'] == 0) {
        $target_dir = "././../../uploads/";
        $image_file = $target_dir . basename($_FILES["menuImage"]["name"]);
        if (move_uploaded_file($_FILES["menuImage"]["tmp_name"], $image_file)) {
            $menuImage = $image_file;
        } else {
            die("Failed to upload image.");
        }
    } else {
        $menuImage = $_POST['existingMenuImage'];
    }

    // Update the menu item in the database
    $sql = "UPDATE menu SET menu_name = '$menuName', menu_description = '$menuDescription', 
            menu_category = '$menuCategory', menu_image_path = '$menuImage' WHERE id = $menuId";

    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p class='alert alert-danger'>Error updating item: " . $conn->error . "</p>";
    }
}

// Fetch data to display in modal
if (isset($_GET['id'])) {
    $menuId = intval($_GET['id']);
    $sql = "SELECT * FROM menu WHERE id = $menuId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $menuData = $result->fetch_assoc();
        echo json_encode($menuData);
    } else {
        echo json_encode(['id' => null]); // If no data found, send null
    }
    exit(); // End script here after sending data
}

?>

<div class="modal fade" id="updateMenuModal" tabindex="-1" aria-labelledby="updateMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="updateMenuForm" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title text-light text-center" id="updateMenuModalLabel">Update Menu Item</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="menuId" id="updateMenuId">
                    <div class="row">
                        <!-- Left Section: Image and Description -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="updateMenuImage" class="form-label">Menu Image</label>
                                <input type="file" class="form-control" id="updateMenuImage" name="menuImage">
                                <img id="menuImagePreview" src="" alt="Current Image" style="width: 100%; margin-top: 10px;">
                            </div>
                            <div class="mb-3">
                                <label for="updateMenuDescription" class="form-label">Menu Description</label>
                                <textarea class="form-control" id="updateMenuDescription" name="menuDescription" rows="5"></textarea>
                            </div>
                        </div>
                        <!-- Right Section: Dynamic Fields -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="updateMenuName" class="form-label">Menu Name</label>
                                <input type="text" class="form-control" id="updateMenuName" name="menuName" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateMenuCategory" class="form-label">Menu Category</label>
                                <select class="form-control" id="updateMenuCategory" name="menuCategory" required onchange="updateFields()">
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
                            <!-- Conditional Fields -->
                            <div class="mb-3" id="sizeField" style="display: none;">
                                <label for="menuSize" class="form-label">Size</label>
                                <select class="form-control" id="menuSize" name="menuSize">
                                    <option value="Small">Small</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Large">Large</option>
                                </select>
                            </div>
                            <div class="mb-3" id="temperatureField" style="display: none;">
                                <label for="menuTemperature" class="form-label">Temperature</label>
                                <select class="form-control" id="menuTemperature" name="menuTemperature">
                                    <option value="Hot">Hot</option>
                                    <option value="Cold">Cold</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="menuQuantity" class="form-label">Quantity</label>
                                <input type="range" class="form-range" id="menuQuantity" name="menuQuantity" min="1" max="10">
                            </div>
                            <div class="mb-3">
                                <label for="menuPrice" class="form-label">Price</label>
                                <input type="text" class="form-control" id="menuPrice" name="menuPrice">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="updateMenuItem">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateFields() {
    const category = document.getElementById('updateMenuCategory').value;
    const isCoffee = (category === 'Coffee' || category === 'Non-Coffee');
    document.getElementById('sizeField').style.display = isCoffee ? 'block' : 'none';
    document.getElementById('temperatureField').style.display = isCoffee ? 'block' : 'none';
}

// Populate modal with data and adjust fields based on category
function openUpdateMenuModal(menuId) {
    fetch(`?id=${menuId}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.id) {
                document.getElementById('updateMenuId').value = data.id;
                document.getElementById('updateMenuName').value = data.menu_name;
                document.getElementById('updateMenuCategory').value = data.menu_category;
                document.getElementById('updateMenuDescription').value = data.menu_description || '';
                document.getElementById('menuImagePreview').src = data.menu_image_path || '';
                document.getElementById('menuImagePreview').style.display = data.menu_image_path ? 'block' : 'none';

                updateFields(); // Adjust visibility based on the current category
                $('#updateMenuModal').modal('show');
            }
        })
        .catch(error => console.error('Error fetching menu item:', error));
}



function openUpdateMenuModal(id) {
    const modal = document.getElementById('updateMenuModal');
    if (modal) {
        modal.style.display = 'block'; // Or use any other modal display mechanism
        console.log('Opening modal with ID:', id); // Optional, for debugging
        $('#updateMenuModal').modal('show');
    }
}
</script>