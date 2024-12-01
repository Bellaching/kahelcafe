<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
</head>
<body>
<?php
ob_start(); // Start output buffering
include "banner.php";
include "./../../connection/connection.php";

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

        // Insert into cart
        $insertSql = "INSERT INTO cart (user_id, item_id, quantity, size, temperature, price) 
                      VALUES ('$user_id', '$item_id', '$quantity', '$size', '$temperature', '$price')";
        if ($conn->query($insertSql) === TRUE) {
            $_SESSION['cart_success'] = "Item added to cart successfully!";
        } else {
            $_SESSION['cart_error'] = "Error: " . $conn->error;
        }

        // Update session cart
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'][] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $quantity,
                'size' => $size,
                'temperature' => $temperature
            ];
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

// Redirect if not logged in
// if (!isset($_SESSION['user_id'])) {
//     $_SESSION['cart_error'] = "You need to log in to add items to the cart.";
//     header("Location: /login.php");
//     exit();
// }

// Pagination logic
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items
$sql = "SELECT * FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);

ob_end_flush();
?>

<div class="container-fluid mb-5">
     <div class="row mt-5 align-items-center">
        <div class="col-12 col-md-8">
            <p class="account-text">Our <span class="management-underline">Menu</span></p>
        </div>
        <div class="col-12 col-md-4 d-flex justify-content-center">
            <div class="input-group">
                <input type="text" id="search" class="form-control search-box" placeholder="Search item..." aria-label="Search item">
            </div>
        </div>
    </div>
    <div class="container-fluid">
    <?php include "filter.php";?>
        <div class="row g-4">
            <?php while ($item = $result->fetch_assoc()) { ?>

                <div class="col-12 col-sm-6 col-md-4 col-lg-2 menu-card shadow-sm">
                    <div class="card p-2 rounded-1 border-0">
                        <div class="img-container"  style="overflow: hidden; height: 150px;">
                            <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
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
                <h5 class="modal-title text-light" id="itemModalLabel"> <?php echo $item['name']; ?> </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img src="<?php echo $item['image']; ?>" class="img-fluid" alt="<?php echo $item['name']; ?>">
                        <p><?php echo $item['description']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

                            <?php if (in_array($item['category'], ['Coffee', 'Non-Coffee'])): ?>
                                <div class="mb-3">
                                    <label for="size" class="form-label">Size</label>
                                    <select class="form-select" name="size" id="size">
                                        <?php
                                        $sizes = explode(',', $item['size']); // Assuming sizes are stored as a comma-separated string in DB
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
                                        $temperatures = explode(',', $item['temperature']); // Assuming temperatures are stored as a comma-separated string in DB
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
                                class="mx-2 border border-dark border-3 rounded-circle d-inline-flex align-items-center justify-content-center" 
                                style="width: 50px; height: 50px; background-color: #f8f9fa; font-weight: bold;">
                                1
                            </span>

                            <input 
                                type="range" 
                                class="form-range flex-grow-1 " 
                                min="1" 
                                max="<?php echo $item['quantity']; ?>" 
                                value="1" 
                                id="quantity<?php echo $item['id']; ?>" 
                                name="quantity">
                        </div>

                         <div class="mb-3" >
                            <span style="color: green; font-weight: bold; margin-right: 5px;">Price:</span>
                            <span id="price" style="color: green; font-weight: bold;"><?php echo $item['price']; ?></span>
                        </div>

                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100 mt-5">Add to Cart</button>
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
                <a href="./../../user/views//login.php" class="btn btn-primary">Log In</a>
            </div>
        </div>
    </div>
</div>

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
</script>

</body>
</html>
