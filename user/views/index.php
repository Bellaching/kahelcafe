<?php

include './../inc/header.php';
include './../inc/topNav.php';
?>
<?php

include './../../connection/connection.php';


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

ob_start(); 

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

// Check if the user is logged in before allowing them to add items to the cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, show an error message and prompt with an alert
        $_SESSION['cart_error'] = "You must be logged in to add items to your cart.";
        echo "<script>alert('Please log in to add items to your cart.');</script>"; // JavaScript alert
    } else {
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
        }
    }
}

// Pagination logic
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);
mysqli_close($conn);

?>

<style>
  .add-index{

background-color: #FF902A;
border-radius: 7rem;
}
</style>
<!-- Banner -->
<div class="banner">
    
    <img src="./../asset/img/kahel-cafe-banner-hd.jpg" class="banner-img" alt="kahel cafe banner"/>
    <h1 id="banner_title">Kahel Cafe</h1>
</div>



    <div class="special-offers">
    <h2 class="title-text">Latest Menu</h2>
    <div class="orange-line">
        <img src="./../asset/img/special-offers/orange-line.png" class="orange-line-img" alt="orange line"/>
    </div>

    <div class="special-offers-container">
    <?php foreach ($menus as $menu): ?>
        <div class="special-offers-menu">
            <div class="special-offers-image-container">
                <img src="<?php echo $menu['image']; ?>" class="menu" alt="<?php echo $menu['name']; ?>" width="309.42px" height="226px"/>
                
                <div class="latest-menu">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <h5 class="category-title text-truncate"><?php echo $menu['name']; ?></h5>
                        <p class="price-info text-success mb-0 fw-bold">P<?php echo number_format($menu['price'], 2); ?></p>
                    </div>

                    <!-- Add Order Button that triggers Modal -->
                    <button class="special-offers-btn" 
    id="addOrderBtn"
    data-bs-toggle="modal" 
    data-bs-target="#itemModal<?php echo $menu['id']; ?>" 
    onclick="checkVerification(<?php echo $userVerified; ?>, '<?php echo $menu['id']; ?>')">
    <img src="./../asset/img/special-offers/cart.png" class="cart" alt="cart"/>
    Add Order
</button>
                </div>
            </div>
        </div>

        <!-- Modal for Each Item -->
        <div class="modal fade" id="itemModal<?php echo $menu['id']; ?>" tabindex="-1" aria-labelledby="itemModalLabel<?php echo $menu['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-light" id="itemModalLabel<?php echo $menu['id']; ?>"><?php echo $menu['name']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <img src="<?php echo $menu['image']; ?>" class="img-fluid" alt="<?php echo $menu['name']; ?>">
                                <p><?php echo $menu['description']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <!-- Form for Adding to Cart -->
                                <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                    <input type="hidden" name="item_id" value="<?php echo $menu['id']; ?>">
                                    <input type="hidden" name="price" value="<?php echo $menu['price']; ?>">

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


                        <button type="submit" name="add_to_cart" class="add-index w-100 mt-5 p-3 text-light border-0">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<!-- Virtual Tour -->
<div class="virtual-tour">
    <div class="slide">
        <img src="./../asset/img/virtual-tour/vt1.png" alt="vt-img-1"/>
    </div>

    <div class="slide">
        <img src="./../asset/img/virtual-tour/vt2.jpg" alt="vt-img-2"/>
    </div>

    <div class="slide">
        <img src="./../asset/img/virtual-tour/vt3.jpg" alt="vt-img-3"/>
    </div>

    <div class="slide-text">
        <h2>Virtual Tour</h2>
        <p class="text-1">Explore our cafe to know more about</p>
        <p class="text-2">our space</p>
        <p class="text-3">and environment!</p>
    </div>

    <button class="prev" onclick="changeSlide(-1)">&#10094;</button>
    <button class="next" onclick="changeSlide(1)">&#10095;</button>

    <div class="dots">
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>
</div>
<script src="./../asset/js/virtual-tour.js"></script>

<!--How to order and reserve-->
<div class="container text-center my-4">
    <div class="order-and-reserve">
        <h2>How to order and reserve</h2>
        <div class="how d-flex justify-content-center flex-wrap">
            <div class="mx-2 d-flex flex-column align-items-center">
                <img src="./../asset/img/order-and-reserve/choose-order.png" class="img-fluid" alt="choose order">
            </div>
            <div class="mx-2 d-flex flex-column align-items-center">
                <img src="./../asset/img/order-and-reserve/Advance.png" class="img-fluid" alt="arrow-1">
            </div>
            <div class="mx-2 d-flex flex-column align-items-center">
                <img src="./../asset/img/order-and-reserve/make-order.png" class="img-fluid" alt="make order">
            </div>
            <div class="mx-2 d-flex flex-column align-items-center">
                <img src="./../asset/img/order-and-reserve/Advance.png" class="img-fluid" alt="arrow-2">
            </div>
            <div class="mx-2 d-flex flex-column align-items-center">
                <img src="./../asset/img/order-and-reserve/receive.png" class="img-fluid" alt="receive">
            </div>
        </div>
    </div>
</div>

          
<div class="sched-reservation">
    <div class="sched-banner">
        <img src="./../asset/img/sched-reservation/sched-banner.png" class="sched-banner-img" alt="sched-reservation-banner">
    </div>
    
    <div class="calendar">
                <iframe src="../inc/calendar.php"></iframe>
            </div>

    <div class="sched-res-text">
        <h2>Schedule your reservation</h2>
        <p class="sched-res-text-1">Reserve Your Order Today – Get Ahead of</p>
        <p class="sched-res-text-2" id="srtext-2">the Queue!</p>
    </div>

    <div class="sched-res-btn">
        <button class="reserve-btn" onclick="window.location.href='./Reservation.php'">
            <img src="./../asset/img/sched-reservation/Reserve.png" class="reserve-img" alt="cart"/>    
            Reserve now
        </button>

        <button class="order-btn" onclick="window.location.href='./try.php'">
            <img src="./../asset/img/sched-reservation/Add Shopping Cart.png" class="order-now-img" alt="cart"/>    
            Order now
        </button>
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
<?php include './../inc/footer.php'; ?>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Loop through each item modal to attach an event listener to each quantity input range
    <?php foreach ($menus as $menu): ?>
        const quantityInput<?php echo $menu['id']; ?> = document.getElementById('quantity<?php echo $menu['id']; ?>');
        const quantityLabel<?php echo $menu['id']; ?> = document.getElementById('quantityLabel<?php echo $menu['id']; ?>');

        // Update the label value when the slider is changed
        quantityInput<?php echo $menu['id']; ?>.addEventListener('input', function() {
            quantityLabel<?php echo $menu['id']; ?>.textContent = quantityInput<?php echo $menu['id']; ?>.value;
        });
    <?php endforeach; ?>
});


function checkVerification(userVerified, itemId) {
    if (userVerified) {
       
        
    } else {
    
    
   
    sessionStorage.setItem('fromLoginRedirect', 'true');

    // Redirect to login page
    window.location.href = './../../user/views/login.php';
}
}
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
<script>

document.querySelectorAll('.form-range').forEach(range => {
    range.addEventListener('input', (e) => {
        const quantityLabel = document.getElementById('quantityLabel' + e.target.id.replace('quantity', ''));
        quantityLabel.textContent = e.target.value;
        const hiddenQuantityInput = document.getElementById('quantityInput' + e.target.id.replace('quantity', ''));
        hiddenQuantityInput.value = e.target.value;
    });

});
</script>