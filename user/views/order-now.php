
<?php

ob_start(); // Start output buffering

include "./../../connection/connection.php";
include './../inc/topNav.php';
include "./../views/banner.php";


$itemsPerPage = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT firstname, lastname, email, contact_number FROM client WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $email, $contactNumber);
    $stmt->fetch();
    $stmt->close();
}

$userVerified = isset($_SESSION['user_id']) ? 1 : 0;
$selectedCategory = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $size = isset($_POST['size']) ? $conn->real_escape_string($_POST['size']) : '';
    $temperature = isset($_POST['temperature']) ? $conn->real_escape_string($_POST['temperature']) : '';
    $price = isset($_POST['price']) ? $conn->real_escape_string($_POST['price']) : 0;
 
    // Fetch item from the database
    $sql = "SELECT * FROM menu1 WHERE id = $item_id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();

    if ($item) {
        $user_id = $_SESSION['user_id'];

        // Check if the item already exists in the cart
        $checkSql = "SELECT * FROM cart WHERE user_id = '$user_id' AND item_id = '$item_id' AND size = '$size' AND temperature = '$temperature'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            // Item exists, update the quantity
            $existingItem = $checkResult->fetch_assoc();
            $newQuantity = $existingItem['quantity'] + $quantity;

            $updateSql = "UPDATE cart SET quantity = '$newQuantity' WHERE id = " . $existingItem['id'];
            if ($conn->query($updateSql) === TRUE) {
                $_SESSION['cart_success'] = "Item quantity updated in cart successfully!";
            } else {
                $_SESSION['cart_error'] = "Error updating item quantity: " . $conn->error;
            }
        } else {
            // Item does not exist, insert new row
            $insertSql = "INSERT INTO cart (user_id, item_id, quantity, size, temperature, price) 
                          VALUES ('$user_id', '$item_id', '$quantity', '$size', '$temperature', '$price')";
            if ($conn->query($insertSql) === TRUE) {
                $_SESSION['cart_success'] = "Item added to cart successfully!";
            } else {
                $_SESSION['cart_error'] = "Error: " . $conn->error;
            }
        }

        // Update session cart
        if (isset($_SESSION['cart'])) {
            $itemFound = false;
            foreach ($_SESSION['cart'] as &$cartItem) {
                if ($cartItem['id'] == $item['id'] && $cartItem['size'] == $size && $cartItem['temperature'] == $temperature) {
                    $cartItem['quantity'] += $quantity;
                    $itemFound = true;
                    break;
                }
            }
            if (!$itemFound) {
                $_SESSION['cart'][] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $quantity,
                    'size' => $size,
                    'temperature' => $temperature
                ];
            }
        } else {
            $_SESSION['cart'] = [
                [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $quantity,
                    'size' => $size,
                    'temperature' => $temperature
                ]
            ];
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Pagination logic
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items
$sql = "SELECT * FROM menu1 WHERE status = 'Available'" . ($selectedCategory ? " AND category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";

$result = $conn->query($sql);

ob_end_flush();
 
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
   
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

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
        /* Hide the modal backdrop */
        .modal-backdrop {
            display: none !important;
        }
    </style>
   
</head>
<body>

<div class="container-fluid mb-5">
     <div class="row mt-5 align-items-center">
        <div class="col-12 col-md-8">
            <p class="account-text">Our <span class="management-underline">Menu</span></p>
        </div>
        <div class="col-12 col-md-4 d-flex justify-content-center">
            <div class="input-group mb-3">
                <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
            </div>
        </div>
    </div>
    <div class="container-fluid">
    <?php include "filter.php";?>
        <div class="row g-4">
        <?php while ($item = $result->fetch_assoc()) {
    if ($item['status'] !== 'Available') continue;
?>

                <div class="col-12 col-sm-6 col-md-4 col-lg-2 menu-card shadow-sm">
    <div class="card p-2 rounded-1 border-0 position-relative">
        
        <!-- Image container with rating -->
        <div class="img-container position-relative" style="overflow: hidden; height: 150px;">
            <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
            
            <!-- Rating badge -->
            <div class="position-absolute top-0 start-0 m-2 px-2 py-1 d-flex align-items-center"
                 style="background-color: white; border-radius: 10px;">
                <span class="text-dark fw-bold me-1"><?php echo number_format($item['rating'], 1); ?></span>
                <i class="fas fa-star text-warning"></i>
            </div>
        </div>

        <div class="card-body text-center p-3">
            <div class="menu-item-container d-flex flex-row gap-3 flex-nowrap align-items-center justify-content-between">
                <div class="category-title text-truncate">
                    <h5 class="mb-0"><?php echo $item['name']; ?></h5>
                </div>
                <div class="price-info text-success">
                    <p class="mb-0"><strong><?php echo $item['price']; ?></strong></p>
                </div>
            </div>
            <button 
                class="btn btn-sm btn-primary p-2 mt-2 border-0 w-100" 
                onclick="checkVerification(<?php echo $userVerified; ?>, '<?php echo $item['id']; ?>')">
                <i class="fa-solid fa-cart-shopping"></i> Add to cart
            </button>
        </div>
    </div>
</div>

                <div class="modal fade" id="itemModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light" id="itemModalLabel"><?php echo $item['name']; ?></h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="h-100">
                <div class="row">


                <div class="col-md-6 mb-4">
    <div style="height: 200px; overflow: hidden;" class="mb-3">
        <img src="<?php echo $item['image']; ?>" 
             class="img-fluid h-100 w-100 object-fit-cover" 
             alt="<?php echo $item['name']; ?>">
    </div>
    <p class="text-muted mb-0" style="..."><?php echo $item['description']; ?></p>
</div>

                    

                    
                    <div class="col-md-6">
                    
                        
                            
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

                            <?php if (in_array($item['category'], ['Coffee', 'Non-Coffee'])): ?>
                                <div class="mb-3">
                                    <label for="size" class="form-label">Size</label>
                                    <select class="form-select" name="size" id="size">
                                        <?php
                                        $sizes = explode(',', $item['size']);
                                        foreach ($sizes as $size) {
                                            echo "<option value='" . trim($size) . "'>" . trim($size) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="temperature" class="form-label">Temperature</label>
                                    <select class="form-select" name="temperature" id="temperature">
                                        <?php
                                        $temperatures = explode(',', $item['temperature']);
                                        foreach ($temperatures as $temp) {
                                            echo "<option value='" . trim($temp) . "'>" . trim($temp) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <strong><label for="quantity<?php echo $item['id']; ?>">Quantity</label></strong> <br>
                            <div class="mb-3 d-flex align-items-center">
                                <span 
                                    id="quantityLabel<?php echo $item['id']; ?>" 
                                    class="mx-2 border  rounded-circle d-inline-flex align-items-center text-light justify-content-center" 
                                    style="width: 50px; height: 50px; background-color: #FF902A; font-weight: bold;">
                                    1
                                </span>

                                <input 
                                    type="range" 
                                    class="form-range flex-grow-1" 
                                    min="1" 
                                    max="<?php echo $item['quantity']; ?>" 
                                    value="1" 
                                    id="quantity<?php echo $item['id']; ?>" 
                                    name="quantity">
                            </div>

                            <div class="mb-3">
                                <span style="color: green; font-weight: bold; margin-right: 5px;">Price:</span>
                                <span id="price" style="color: green; font-weight: bold;"><?php echo $item['price']; ?></span>
                            </div>

                            

                            <!-- Footer Buttons -->
                           
</div>
<div class="modal-body d-flex flex-column" style="height: 100%;">
    <!-- Your modal content goes here -->

    <!-- Buttons at the bottom -->
    <div class="row">
        <!-- Close button - dismisses the modal -->
        <div class="col-6">
            <button type="button" class="close-add container-fluid" data-dismiss="modal">Close</button>
        </div>

        <div class="col-6">
            <!-- Add to Cart button - submits the form -->
            <button type="submit" name="add_to_cart" class="btn-add-item text-light container-fluid">Add to Cart</button>
        </div>
    </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>







            <?php } ?>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Verification Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>
            <div class="modal-body text-center">
                <p>Please log in to add items to your cart.</p>
                <a href="./../../user/views/login.php" class="btn btn-primary">Log In</a>
            </div>
        </div>
    </div>
</div>


<script>
    <?php if (isset($_SESSION['cart_success'])): ?>
        let alertBox = document.createElement('div');
        alertBox.textContent = '<?php echo $_SESSION['cart_success']; ?>';
        alertBox.style.position = 'fixed';
        alertBox.style.top = '20px';
        alertBox.style.left = '50%';
        alertBox.style.transform = 'translateX(-50%)';
        alertBox.style.backgroundColor = '#4CAF50';
        alertBox.style.color = 'white';
        alertBox.style.padding = '10px 20px';
        alertBox.style.borderRadius = '5px';
        alertBox.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        alertBox.style.opacity = '0';
        alertBox.style.transition = 'opacity 0.5s ease';
        document.body.appendChild(alertBox);
        setTimeout(() => { alertBox.style.opacity = '1'; }, 10);
        setTimeout(() => { alertBox.style.opacity = '0'; setTimeout(() => { alertBox.remove(); }, 500); }, 4500);
        <?php unset($_SESSION['cart_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['cart_error'])): ?>
        let alertBox = document.createElement('div');
        alertBox.textContent = '<?php echo $_SESSION['cart_error']; ?>';
        alertBox.style.position = 'fixed';
        alertBox.style.top = '20px';
        alertBox.style.left = '50%';
        alertBox.style.transform = 'translateX(-50%)';
        alertBox.style.backgroundColor = '#f44336';
        alertBox.style.color = 'white';
        alertBox.style.padding = '10px 20px';
        alertBox.style.borderRadius = '5px';
        alertBox.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        alertBox.style.opacity = '0';
        alertBox.style.transition = 'opacity 0.5s ease';
        document.body.appendChild(alertBox);
        setTimeout(() => { alertBox.style.opacity = '1'; }, 10);
        setTimeout(() => { alertBox.style.opacity = '0'; setTimeout(() => { alertBox.remove(); }, 500); }, 4500);
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>
</script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
<script>
function checkVerification(isLoggedIn, itemId) {
    if (isLoggedIn) {
        const itemModal = new bootstrap.Modal(document.getElementById(`itemModal${itemId}`));
        itemModal.show();
    } else {
        const verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
        verificationModal.show();
    }
}

document.querySelectorAll('.form-range').forEach(range => {
    range.addEventListener('input', (e) => {
        const quantityLabel = document.getElementById('quantityLabel' + e.target.id.replace('quantity', ''));
        quantityLabel.textContent = e.target.value;
        const hiddenQuantityInput = document.getElementById('quantityInput' + e.target.id.replace('quantity', ''));
        hiddenQuantityInput.value = e.target.value;
    });

});

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
