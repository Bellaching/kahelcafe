<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php 
ob_start(); // Start output buffering
include "banner.php";
include "./../../connection/connection.php";

// Pagination and Filter Settings
$itemsPerPage = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Fetch total menu items count with category filter
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu" . ($selectedCategory ? " WHERE menu_category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items for the current page with category filter
$sql = "SELECT * FROM menu" . ($selectedCategory ? " WHERE menu_category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
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
            </div>
        </div>
    </div>

    <?php include './filter.php'; ?>

    <div class="container">
        <div class="row g-4" id="menu-card-container">
            <?php
                function renderMenuItems($result) {
                    $output = '';
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $menu_name = htmlspecialchars($row['menu_name'], ENT_QUOTES, 'UTF-8');
                            $price_small = htmlspecialchars($row['menu_price_small'], ENT_QUOTES, 'UTF-8');
                            $price_medium = htmlspecialchars(trim($row['menu_price_medium']), ENT_QUOTES, 'UTF-8');
                            $price_large = htmlspecialchars($row['menu_price_large'], ENT_QUOTES, 'UTF-8');
                            $image = htmlspecialchars($row['menu_image_path'], ENT_QUOTES, 'UTF-8');
                            $category = htmlspecialchars($row['menu_category'], ENT_QUOTES, 'UTF-8');
                            $id = intval($row['id']);

                            $price_display = '';
                            if (!empty($price_small) && !empty($price_large)) {
                                $price_display = "₱" . $price_small . " - ₱" . $price_large;
                            } elseif (!empty($price_small)) {
                                $price_display = "₱" . $price_small;
                            }

                            $output .= '
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
                                    <div class="card p-2 rounded-1 border-0">
                                        <div class="img-container" style="overflow: hidden; height: 150px;">
                                            <img src="' . $image . '" class="card-img-top" alt="' . $menu_name . '" style="height: 100%; width: 100%; object-fit: cover;">
                                        </div>
                                        <div class="card-body text-center p-3">
                                            <div class="menu-item-container d-flex flex-row gap-3 flex-nowrap align-items-center justify-content-between">
                                                <div class="category-title text-truncate">
                                                    <h5 class="mb-0">' . $menu_name . '</h5>
                                                </div>
                                                <div class="price-info text-nowrap mt-2 mb-2">
                                                    <p class="text-success mb-0"><strong>' . $price_display . '</strong></p>
                                                </div>
                                            </div>
                                          <button class="btn btn-sm btn-primary p-2 mt-2 border-0" onclick="openCartMenuModal(' . $id . ')" data-bs-toggle="modal" data-bs-target="#addCartModal">
                                              <i class="fa-solid fa-cart-shopping"></i> Add to cart
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
</div>

<!-- Modal for Add to Cart -->
<div class="modal fade" id="addCartModal" tabindex="-1" aria-labelledby="addCartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCartModalLabel">Menu Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content will be inserted here based on the fetched data -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
function openCartMenuModal(menuId) {
    // Fetch the menu details using the menuId
    fetch(`getMenuDetails.php?id=${menuId}`)
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('modalBody');
            let sizeOptions = '';
            let temperatureOptions = '';
            let priceDisplay = `₱${data.menu_price_small}`;
            let priceSmall = parseFloat(data.menu_price_small);
            let priceMedium = parseFloat(data.menu_price_medium);
            let priceLarge = parseFloat(data.menu_price_large);

            // Quantity is based on available stock in the database
            let maxQuantity = data.menu_quantity; // Assume `menu_quantity` is in the database
            
            // Check category and build modal structure accordingly
            if (data.menu_category === 'coffee' || data.menu_category === 'non-coffee') {
                // For 'coffee' and 'non-coffee', display Size and Temperature dropdowns
                sizeOptions = `
                    <label class="form-label">Size</label>
                    <select class="form-select" id="menuSize" onchange="updatePriceBasedOnSize()">
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>`;

                temperatureOptions = `
                    <label class="form-label">Temperature</label>
                    <select class="form-select" id="menuTemperature">
                        <option value="hot">Hot</option>
                        <option value="cold">Cold</option>
                    </select>`;

                // Display price based on the selected size (small/medium/large)
                priceDisplay = `
                    <label class="form-label">Price</label>
                    <input type="text" class="form-control" id="menuPrice" value="₱${priceSmall}" readonly>`;
            } else {
                // For other categories, just show price based on quantity
                priceDisplay = `
                    <label class="form-label">Price</label>
                    <input type="text" class="form-control" id="menuPrice" value="₱${priceSmall}" readonly>`;
            }

            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <img src="${data.menu_image_path}" class="img-fluid" alt="${data.menu_name}">
                        <div class="mt-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" readonly>${data.menu_description}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        ${sizeOptions}
                        ${temperatureOptions}
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="menuQuantity" min="1" max="${maxQuantity}" value="1">
                        </div>
                        ${priceDisplay}
                    </div>
                </div>`;

            // Update price if size changes
            function updatePriceBasedOnSize() {
                let selectedSize = document.getElementById('menuSize').value;
                let newPrice = 0;
                if (selectedSize === 'small') {
                    newPrice = priceSmall;
                } else if (selectedSize === 'medium') {
                    newPrice = priceMedium;
                } else if (selectedSize === 'large') {
                    newPrice = priceLarge;
                }
                document.getElementById('menuPrice').value = `₱${newPrice.toFixed(2)}`;
            }
        })
        .catch(error => {
            console.error('Error fetching menu details:', error);
        });
}


function addToCart() {
    const quantity = document.getElementById('menuQuantity').value;
    const size = document.getElementById('menuSize') ? document.getElementById('menuSize').value : '';
    const temperature = document.getElementById('menuTemperature') ? document.getElementById('menuTemperature').value : '';

    console.log(`Adding ${quantity} item(s) to cart with size: ${size} and temperature: ${temperature}`);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js"></script>

</body>
</html>
