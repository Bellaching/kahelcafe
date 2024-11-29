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
        $user_id = $_SESSION['user_id']; // Replace with actual user ID from session

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
if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "You need to log in to add items to the cart.";
    header("Location: /login.php");
    exit();
}

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
    data-bs-toggle="modal" 
    data-bs-target="#itemModal<?php echo $item['id']; ?>" 
    onclick="checkVerification('<?php echo $userVerified; ?>')">
    <i class="fa-solid fa-cart-shopping"></i> Add to cart
</button>
</div>
    </div>
</div>
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verificationModalLabel">Verification Required</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Please log in to add items to your cart.</p>
        <a href="/login.php" class="btn btn-primary">Log In</a>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="itemModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light" id="itemModalLabel"><?php echo $item['name']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img src="<?php echo $item['image']; ?>" class="img-fluid mb-3" alt="<?php echo $item['name']; ?>">
                        <p><?php echo $item['description']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php 
                        if (in_array($item['category'], ['Coffee', 'Non-Coffee'])) {
                            if (!empty($item['size'])) {
                                $sizes = explode(',', $item['size']); // Assume sizes are stored as a comma-separated string
                                echo '<div class="mb-5 mt-5"><strong><label for="size">Size</label></strong><br>';
                                echo '<div class="d-flex gap-2">';
                                foreach ($sizes as $size) {
                                    echo '<input 
                                            type="radio" 
                                            name="size" 
                                            value="' . $size . '" 
                                            id="size' . $item['id'] . '_' . $size . '" 
                                            class="btn-check" 
                                            onclick="toggleButton(this)">
                                          <label for="size' . $item['id'] . '_' . $size . '" class="btn btn-outline-warning px-3 py-2 rounded-pill">
                                            ' . ucfirst(trim($size)) . '
                                          </label>';
                                }
                                echo '</div></div>';
                            }
                            if (!empty($item['temperature'])) {
                                $temperatures = explode(',', $item['temperature']); // Assume temperatures are stored as a comma-separated string
                                echo '<div class="mb-5"><strong><label for="temperature">Temperature</label></strong><br>';
                                echo '<div class="d-flex gap-2">';
                                foreach ($temperatures as $temperature) {
                                    echo '<input 
                                            type="radio" 
                                            name="temperature" 
                                            value="' . $temperature . '" 
                                            id="temperature' . $item['id'] . '_' . $temperature . '" 
                                            class="btn-check" 
                                            onclick="toggleButton(this)">
                                          <label for="temperature' . $item['id'] . '_' . $temperature . '" class="btn btn-outline-warning px-3 py-2 rounded-pill">
                                            ' . ucfirst(trim($temperature)) . '
                                          </label>';
                                }
                                echo '</div></div>';
                            }   
                        } else {
                            echo '<script>
                                document.getElementById("sizeSection' . $item['id'] . '").style.display = "none";
                                document.getElementById("temperatureSection' . $item['id'] . '").style.display = "none";
                              </script>';
                        }
                        ?>
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
                        <div class="mb-3" style="position: absolute; bottom: 10px; right: 15px; display: flex; align-items: center;">
                            <span style="color: green; font-weight: bold; margin-right: 5px;">Price:</span>
                            <span id="price" style="color: green; font-weight: bold;"><?php echo $item['price']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer container-fluid d-flex flex-row border-0">
                <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Close</button>
                <form method="POST" class="d-flex flex-row flex-fill">
    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
    <input type="hidden" name="quantity" id="quantityInput<?php echo $item['id']; ?>" value="1">
    <input type="hidden" name="size" id="sizeInput<?php echo $item['id']; ?>" value="">
    <input type="hidden" name="temperature" id="temperatureInput<?php echo $item['id']; ?>" value="">
    <input type="hidden" name="price" value="<?php echo $item['price']; ?>"> 
    <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Add to Cart</button>
</form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verificationModalLabel">Verification Required</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        You need to verify your account before adding items to the cart.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="verification-page.php" class="btn btn-primary">Go to Verification</a>
      </div>
    </div>
  </div>
</div>
<script>
    function checkVerification(isVerified) {
    if (isVerified === 'false') {
        const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
        modal.show();
    } else {
    }
}
function toggleButton(button) {
    const isActive = button.classList.contains('btn-warning');
    const buttons = button.closest('.d-flex').querySelectorAll('.btn');
    buttons.forEach(btn => btn.classList.remove('btn-warning', 'text-white'));
    if (!isActive) {
        button.classList.add('btn-warning', 'text-white');
    }
    const inputName = button.closest('.modal-body').querySelector('input[type="radio"]').name;
    const value = button.textContent.trim();
    if (inputName === 'size') {
        const sizeInput = document.getElementById('sizeInput' + button.closest('.modal').id.replace('itemModal', ''));
        sizeInput.value = value;
    }
    if (inputName === 'temperature') {
        const tempInput = document.getElementById('temperatureInput' + button.closest('.modal').id.replace('itemModal', ''));
        tempInput.value = value;
    }
}
function checkVerification(isVerified) {
    if (!isVerified) {
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
<script>
document.getElementById('quantity<?php echo $item['id']; ?>').addEventListener('input', function() {
    var quantity = this.value;
    document.getElementById('quantityLabel<?php echo $item['id']; ?>').textContent = quantity;
    document.getElementById('quantityInput<?php echo $item['id']; ?>').value = quantity;
});
</script>
<script>
document.getElementById('quantity<?php echo $item['id']; ?>').addEventListener('input', function() {
    var quantity = this.value;
    document.getElementById('quantityLabel<?php echo $item['id']; ?>').textContent = quantity;
    document.getElementById('quantityInput<?php echo $item['id']; ?>').value = quantity;
});
</script>
<script>
document.getElementById('quantity<?php echo $item['id']; ?>').addEventListener('input', function() {
    var quantity = this.value;
    document.getElementById('quantityLabel<?php echo $item['id']; ?>').textContent = quantity;
    document.getElementById('quantityInput<?php echo $item['id']; ?>').value = quantity;
});
</script>
            <?php } ?>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
    document.getElementById("search").addEventListener("input", function () {
    const searchText = this.value.toLowerCase();
    const menuItems = document.querySelectorAll(".menu-card");
    menuItems.forEach(item => {
        const itemName = item.querySelector("h5").innerText.toLowerCase();
        if (itemName.includes(searchText)) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
});
document.querySelectorAll('[name="size"]').forEach(button => {
    button.addEventListener('click', function() {
        const size = this.value; // Get the selected size
        const itemId = this.closest('form').querySelector('input[name="item_id"]').value; // Get the item ID
        document.getElementById('sizeInput' + itemId).value = size;
    });
});
document.querySelectorAll('[name="temperature"]').forEach(button => {
    button.addEventListener('click', function() {
        const temperature = this.value; // Get the selected temperature
        const itemId = this.closest('form').querySelector('input[name="item_id"]').value; // Get the item ID
        document.getElementById('temperatureInput' + itemId).value = temperature;
    });
});
</script>
</body>
</html>

 