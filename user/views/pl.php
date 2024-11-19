<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
 
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
 
</head>
<body>

<?php 
ob_start(); // Start output buffering

include "banner.php";
include "./../../connection/connection.php";

// Pagination settings
$itemsPerPage = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;

// Initialize filter variables
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Handle add to cart functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    
    // Fetch item details
    $sql = "SELECT * FROM menu1 WHERE id = $item_id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();

    // Add item to cart if not already present
    if (isset($_SESSION['cart'])) {
        $item_exists = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $item_id) {
                $cart_item['quantity'] += $quantity;
                $item_exists = true;
                break;
            }
        }
        if (!$item_exists) {
            $_SESSION['cart'][] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $quantity
            ];
        }
    } else {
        $_SESSION['cart'] = [
            [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $quantity
            ]
        ];
    }
}

// Fetch menu items based on category filter
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
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
            <div class="input-group">
                <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="container-fluid">
        <div class="row mb-3 category-list container-fluid">
            <!-- Desktop category buttons -->
           
            </div>
        </div>

        <!-- Menu Cards -->
        <div class="row">
            <?php while ($item = $result->fetch_assoc()) { ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
                <div class="card p-2 rounded-1 border-0">
                    <div class="img-container" style="overflow: hidden; height: 150px;">
                        <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="card-body text-center p-3">
                        <div class="menu-item-container d-flex flex-row gap-3 flex-nowrap align-items-center justify-content-between">
                        <div class="category-title text-truncate">
                                                    <h5 class="mb-0"><?php echo $item['name']; ?></h5>
                                                </div>
                                                <div class="price-info text-nowrap mt-2 mb-2">
                                                    <p class="text-success mb-0"><strong><?php echo $item['price']; ?></strong></p>
                                                </div>
                        </div>
                        <button class="btn btn-sm btn-primary p-2 mt-2 border-0" onclick="openCartMenuModal(' . $id . ')" data-bs-toggle="modal" data-bs-target="#addCartModal">
                                              <i class="fa-solid fa-cart-shopping"></i> Add to cart
                                          </button>
                        </div>
                    </div>
                </div>

                <!-- Modal for Each Item -->
                <div class="modal fade" id="itemModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="itemModalLabel"><?php echo $item['name']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="left-side">
                                    <img src="<?php echo $item['image']; ?>" class="img-fluid mb-3" alt="<?php echo $item['name']; ?>">
                                    <p><?php echo $item['description']; ?></p>
                                </div>
                                <div class="right-side">
                                    <!-- If the item is a Drink -->
                                    <?php if (in_array($item['category'], ['Coffee', 'Non-Coffee', 'Signature Frappe', 'Upsize'])) { ?>
                                        <label for="size">Size</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="size" id="small" value="small">
                                            <label class="form-check-label" for="small">Small</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="size" id="medium" value="medium">
                                            <label class="form-check-label" for="medium">Medium</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="size" id="large" value="large">
                                            <label class="form-check-label" for="large">Large</label>
                                        </div>
                                        <br>

                                        <label for="temperature">Temperature</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="temperature" id="hot" value="hot">
                                            <label class="form-check-label" for="hot">Hot</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="temperature" id="cold" value="cold">
                                            <label class="form-check-label" for="cold">Cold</label>
                                        </div>
                                    <?php } ?>

                                    <!-- If the item is Food -->
                                    <?php if (in_array($item['category'], ['Starters', 'Pasta', 'Sandwich', 'Rice Meal', 'All Day Breakfast', 'Add ons'])) { ?>
                                        <label for="quantity">Quantity</label>
                                        <input type="number" id="quantity" class="form-control" value="1" min="1" max="10">
                                    <?php } ?>

                                    <p>Price: ₱<?php echo $item['price']; ?></p>
                                    <form action="" method="POST">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                </li>
                <li class="page-item <?php echo $current_page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<script>
    function filterByCategory(category) {
        var categoryFilter = category || document.getElementById("categoryFilter").value;
        window.location.href = "?category=" + categoryFilter;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
</body>
</html>
