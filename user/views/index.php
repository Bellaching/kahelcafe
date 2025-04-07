<?php
// Start output buffering
ob_start();



include './../../connection/connection.php';
include "./../views/banner.php";

$query = "SELECT * FROM menu1 ORDER BY date_created DESC LIMIT 3";  
$result = mysqli_query($conn, $query);

$menus = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menus[] = $row;
    }
} else {
    echo "No menu items found.";
}

$itemsPerPage = 6; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $itemsPerPage;

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

// Build the base query
$baseQuery = "SELECT * FROM menu1";
$countQuery = "SELECT COUNT(*) as count FROM menu1";

// Add category filter if selected
if (!empty($selectedCategory)) {
    $baseQuery .= " WHERE category = '$selectedCategory'";
    $countQuery .= " WHERE category = '$selectedCategory'";
}

// Get total items count
$totalItemsResult = $conn->query($countQuery);
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination to the main query
$sql = $baseQuery . " LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['cart_error'] = "You must be logged in to add items to your cart.";
        echo "<script>alert('Please log in to add items to your cart.');</script>";
    } else {
        $item_id = intval($_POST['item_id']);
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $size = isset($_POST['size']) ? $conn->real_escape_string($_POST['size']) : '';
        $temperature = isset($_POST['temperature']) ? $conn->real_escape_string($_POST['temperature']) : '';
        $price = isset($_POST['price']) ? $conn->real_escape_string($_POST['price']) : 0;

        // Fetch item details from the database
        $sql = "SELECT * FROM menu1 WHERE id = $item_id";
        $result = $conn->query($sql);
        $item = $result->fetch_assoc();

        if ($item) {
            $user_id = $_SESSION['user_id'];

            // Check if the item already exists in the cart
            $itemExists = false;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as &$cartItem) {
                    if ($cartItem['id'] == $item_id && $cartItem['size'] == $size && $cartItem['temperature'] == $temperature) {
                        // Update quantity if the item already exists
                        $cartItem['quantity'] += $quantity;
                        $itemExists = true;
                        break;
                    }
                }
            }

            // If the item doesn't exist in the cart, add it
            if (!$itemExists) {
                $_SESSION['cart'][] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $quantity,
                    'size' => $size,
                    'temperature' => $temperature
                ];
            }

            // Insert into the database cart table
            $insertSql = "INSERT INTO cart (user_id, item_id, quantity, size, temperature, price) 
                          VALUES ('$user_id', '$item_id', '$quantity', '$size', '$temperature', '$price')
                          ON DUPLICATE KEY UPDATE quantity = quantity + $quantity"; // Handle duplicates

            if ($conn->query($insertSql)) {
                $_SESSION['cart_success'] = "Item added to cart successfully!";
            } else {
                $_SESSION['cart_error'] = "Error: " . $conn->error;
            }

            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Get total count of all items or filtered items
$whereClause = !empty($selectedCategory) ? " WHERE category = '$selectedCategory'" : "";
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1 $whereClause");
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items with pagination
$sql = "SELECT * FROM menu1 $whereClause ORDER BY name ASC LIMIT $offset, $itemsPerPage";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Layout</title>
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
    <header>
        <!-- Include your banner.php here -->
    </header>
    <div class="container my-5">
        <h2 class="fw-bold mb-6" style="text-align: left; margin-bottom: 3rem;">
            Try our new <span style="position: relative; color: #E48700;">
                Menu
                <span style="position: absolute; left: 0; bottom: -4px; width: 100%; height: 3px; background-color: #E48700; border-radius: 2px;"></span>
            </span>
        </h2>

        <div class="row justify-content-center mt-4">
            <?php foreach (array_chunk($menus, 3) as $menuRow): ?>
                <div class="col-12 mb-5 d-flex justify-content-center position-relative">
                    <!-- Orange background container -->
                    <div class="w-75 p-5 position-absolute top-50 start-50 translate-middle-x"
                         style="background-color: #FFCEA4; border-radius: 25px; height: 220px; z-index: 0;">
                    </div>

                    <div class="row w-100 position-relative z-1 gx-3">
                        <?php foreach ($menuRow as $index => $menu): ?>
                            <div class="col-md-4 d-flex justify-content-center mb-4">
    <div class="card flex-fill p-3 shadow-lg"
        style="border-radius: 15px; 
        margin-top: <?php echo $index == 1 ? '-30px' : '-20px'; ?>;
        margin-bottom: 20px;
        z-index: 1; position: relative;">
        
        <!-- Image container with rating badge -->
        <div style="position: relative;">
            <img src="<?php echo $menu['image']; ?>" 
                 class="card-img-top img-fluid rounded" 
                 alt="<?php echo $menu['name']; ?>" 
                 style="height: 200px; object-fit: cover;">

            <!-- Rating badge -->
            <div class="position-absolute top-0 start-0 m-2 px-2 py-1 d-flex align-items-center"
                 style="background-color: white; border-radius: 10px;">
                <span class="text-dark fw-bold me-1"><?php echo number_format($menu['rating'], 1); ?></span>
                <i class="fas fa-star text-warning"></i>
            </div>
        </div>

        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between">
                <h5 class="card-title text-dark"><?php echo $menu['name']; ?></h5>
                <p class="card-text text-success fw-bold">P<?php echo number_format($menu['price'], 2); ?></p>
            </div>
            <button class="btn mt-auto rounded-pill add-to-cart-btn" 
                    style="background-color: #E48700; color: white;" 
                    data-bs-toggle="modal" 
                    data-bs-target="#itemModal<?php echo $menu['id']; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Add to Cart
            </button>
        </div>
    </div>
</div>


                            <!-- Add to Cart Modal for Each Item -->
                            <div class="modal fade" id="itemModal<?php echo $menu['id']; ?>" tabindex="-1" aria-labelledby="itemModalLabel<?php echo $menu['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-light" id="itemModalLabel<?php echo $menu['id']; ?>"><?php echo $menu['name']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                            <div class="col-md-6 mb-4">
    <div class="card h-100 border-0">
        <!-- Fixed container with filled image -->
        <div style="height: 250px; overflow: hidden;">
            <img src="<?php echo $menu['image']; ?>" 
                 class="img-fluid h-100 w-100" 
                 style="object-fit: cover; object-position: center;"
                 alt="<?php echo $menu['name']; ?>">
        </div>
        <div class="card-body">
            <p class="card-text description-text"><?php echo $menu['description']; ?></p>
        </div>
    </div>
</div>
                                                <div class="col-md-6">
                                                    <!-- Form for Adding to Cart -->
                                                    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                                        <input type="hidden" name="item_id" value="<?php echo $menu['id']; ?>">
                                                        <input type="hidden" name="price" value="<?php echo $menu['price']; ?>">
                                                        <div>

                                                      

                                                        <?php if (in_array($menu['category'], ['Coffee', 'Non-Coffee'])): ?>
                                                            <div class="mb-3">
                                                            
                                                                <label for="size" class="form-label">Size</label>
                                                                <select class="form-select" name="size" id="size<?php echo $menu['id']; ?>">
                                                                    <?php
                                                                    $sizes = explode(',', $menu['size']); // Assuming sizes are stored as a comma-separated string in DB
                                                                    foreach ($sizes as $size) {
                                                                        echo "<option value='" . trim($size) . "'>" . trim($size) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="temperature" class="form-label">Temperature</label>
                                                                <select class="form-select" name="temperature" id="temperature<?php echo $menu['id']; ?>">
                                                                    <?php
                                                                    $temperatures = explode(',', $menu['temperature']); // Assuming temperatures are stored as a comma-separated string in DB
                                                                    foreach ($temperatures as $temp) {
                                                                        echo "<option value='" . trim($temp) . "'>" . trim($temp) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Quantity Field -->
                                                        <strong><label for="quantity<?php echo $menu['id']; ?>">Quantity</label></strong> <br>
                                                        <div class="mb-3 d-flex align-items-center">
                                                            <span 
                                                                id="quantityLabel<?php echo $menu['id']; ?>" 
                                                                class="mx-2 border  border-0 rounded-circle text-light d-inline-flex align-items-center justify-content-center" 
                                                                style="width: 50px; height: 50px; background-color: #FF902A; font-weight: bold;">
                                                                1
                                                            </span>

                                                            <input 
                                                                type="range" 
                                                                class="form-range flex-grow-1 " 
                                                                min="1" 
                                                                max="<?php echo $menu['quantity']; ?>" 
                                                                value="1" 
                                                                id="quantity<?php echo $menu['id']; ?>" 
                                                                name="quantity">
                                                        </div>
                                                        
                                                        </div>

                                                        <div class="d-flex justify-content-between">
    <div class="cancel-div container-fluid">
        <button type="button" class="add-index w-100 mt-5 p-3    " style="border: #E48700 1px solid; background-color: white; color: #FF902A;" data-bs-dismiss="modal">Cancel</button>
    </div>
    <div class="order-div container-fluid">
        <button type="submit" name="add_to_cart" class="add-index w-100 mt-5 p-3 text-light border-0">Add order</button>
    </div>
</div>

                                                        
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Login Required Modal -->
    <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-labelledby="loginRequiredModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginRequiredModalLabel">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You need to be logged in to add items to the cart. Please log in first.</p>
                </div>
                <div class="modal-footer">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

      <!-- Virtual Tour and Other Sections -->
      <div class="container text-center my-4">
      <?php 
        include "./../views/virt.php";
      ?>
    </div>

    <!-- Virtual Tour and Other Sections -->
    <div class="container text-center my-4">
        <h2>How to order and reserve</h2>
        <div class="row justify-content-center">
            <div class="col-md-2">
                <img src="./../asset/img/order-and-reserve/choose-order.png" class="img-fluid" alt="choose order">
            </div>
            <div class="col-md-2">
                <img src="./../asset/img/order-and-reserve/Advance.png" class="img-fluid" alt="arrow-1">
            </div>
            <div class="col-md-2">
                <img src="./../asset/img/order-and-reserve/make-order.png" class="img-fluid" alt="make order">
            </div>
            <div class="col-md-2">
                <img src="./../asset/img/order-and-reserve/Advance.png" class="img-fluid" alt="arrow-2">
            </div>
            <div class="col-md-2">
                <img src="./../asset/img/order-and-reserve/receive.png" class="img-fluid" alt="receive">
            </div>
        </div>
    </div>

    <div class="sched-banner position-relative mb-5 mt-5" style="background-image: url('./../asset/img/sched-reservation/sched-banner.png'); background-size: cover; background-position: center; min-height: 600px;">
        <div class="container position-relative z-index-1">
            <div class="row">
                <!-- Calendar on the left -->
                <div class="col-12 col-md-6 d-flex justify-content-center">
                    <div class="calendar w-100">
                        <iframe src="../inc/calendar.php" style="width: 100%; height: 500px; border: none;"></iframe>
                    </div>
                </div>

                <!-- Text and buttons -->
                <div class="col-12 col-md-6 d-flex flex-column justify-content-center align-items-center text-white text-center text-md-start">
                    <div class="sched-res-text mb-4">
                        <h1 style="color: #E48700; border: none;">Schedule your reservation</h1>
                        <p>Reserve Your Order Today – Get Ahead of the Queue!</p>
                    </div>

                    <div class="sched-res-btn mb-4 mb-md-0 d-flex flex-column flex-md-row">
                        <button class="btn btn-primary mb-3 mb-md-0 me-md-2" style="background-color: #E48700; border: none;" onclick="window.location.href='reservation.php';">
                            Reserve now
                        </button>
                        <button class="btn btn-secondary mb-3 mb-md-0" style="background-color: #ffff; color: #E48700; border: none;" onclick="window.location.href='index.php';">
                            Order now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3">
        <p>Follow us on social media</p>
        <div class="socmed">
            <a href="#" class="text-white mx-2"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-white mx-2"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-white mx-2"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>



    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if the user is logged in before showing the add to cart modal
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const userVerified = <?php echo $userVerified; ?>;
                    if (!userVerified) {
                        e.preventDefault();
                        $('#loginRequiredModal').modal('show');
                    }
                });
            });

            // Update quantity label when slider changes
            document.querySelectorAll('.form-range').forEach(range => {
                range.addEventListener('input', (e) => {
                    const quantityLabel = document.getElementById('quantityLabel' + e.target.id.replace('quantity', ''));
                    quantityLabel.textContent = e.target.value;
                });
            });
        });
    </script>
</body>
</html>
<?php
// Flush the output buffer
ob_end_flush();
?>