<?php
// Fetch menu items based on category filter
$whereClause = !empty($selectedCategory) ? " WHERE category = '$selectedCategory'" : "";
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . $whereClause);
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <select class="form-select d-md-none" id="categoryFilter" onchange="filterByCategory()">
                <option value="" <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>All Categories</option>
                <option value="Espresso" <?php echo $selectedCategory == 'Espresso' ? 'selected' : ''; ?>>Espresso</option>
                <option value="Signatures" <?php echo $selectedCategory == 'Signatures' ? 'selected' : ''; ?>>Signatures</option>
                <option value="Frappe (espresso base)" <?php echo $selectedCategory == 'Frappe (espresso base)' ? 'selected' : ''; ?>>Frappe (espresso base)</option>
                <option value="Frappe (cream base)" <?php echo $selectedCategory == 'Frappe (cream base)' ? 'selected' : ''; ?>>Frappe (cream base)</option>
                <option value="Non-Coffee" <?php echo $selectedCategory == 'Non-Coffee' ? 'selected' : ''; ?>>Non-Coffee</option>
                <option value="Signature Frappe" <?php echo $selectedCategory == 'Signature Frappe' ? 'selected' : ''; ?>>Signature Frappe</option>
                <option value="Starters" <?php echo $selectedCategory == 'Starters' ? 'selected' : ''; ?>>Starters</option>
                <option value="Pasta" <?php echo $selectedCategory == 'Pasta' ? 'selected' : ''; ?>>Pasta</option>
                <option value="Sandwich" <?php echo $selectedCategory == 'Sandwich' ? 'selected' : ''; ?>>Sandwich</option>
                <option value="Rice Meal" <?php echo $selectedCategory == 'Rice Meal' ? 'selected' : ''; ?>>Rice Meal</option>
                <option value="All Day Breakfast" <?php echo $selectedCategory == 'All Day Breakfast' ? 'selected' : ''; ?>>All Day Breakfast</option>
                <option value="Add ons" <?php echo $selectedCategory == 'Add ons' ? 'selected' : ''; ?>>Add ons</option>
                <option value="Upsize" <?php echo $selectedCategory == 'Upsize' ? 'selected' : ''; ?>>Upsize</option>
            </select>
        </div>
    </div>
    
    <div class="row mb-3 category-list d-none d-md-flex">
        <div class="col-12 d-flex flex-wrap justify-content-center">
            <div class="me-3">
                <a href="order-now.php" class="btn btn-light border-0 <?php echo empty($selectedCategory) ? 'active' : ''; ?>">
                    <i class="fas fa-list me-2"></i> All
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Espresso" class="btn btn-light border-0 <?php echo $selectedCategory == 'Espresso' ? 'active' : ''; ?>">
                    <i class="fas fa-coffee me-2"></i> Espresso
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Signatures" class="btn btn-light border-0 <?php echo $selectedCategory == 'Signatures' ? 'active' : ''; ?>">
                    <i class="fas fa-star me-2"></i> Signatures
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Frappe (espresso base)" class="btn btn-light border-0 <?php echo $selectedCategory == 'Frappe (espresso base)' ? 'active' : ''; ?>">
                    <i class="fas fa-ice-cream me-2"></i> Frappe (espresso)
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Frappe (cream base)" class="btn btn-light border-0 <?php echo $selectedCategory == 'Frappe (cream base)' ? 'active' : ''; ?>">
                    <i class="fas fa-ice-cream me-2"></i> Frappe (cream)
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Non-Coffee" class="btn btn-light border-0 <?php echo $selectedCategory == 'Non-Coffee' ? 'active' : ''; ?>">
                    <i class="fas fa-glass-cheers me-2"></i> Non-Coffee
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Signature Frappe" class="btn btn-light border-0 <?php echo $selectedCategory == 'Signature Frappe' ? 'active' : ''; ?>">
                    <i class="fas fa-ice-cream me-2"></i> Signature Frappe
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Starters" class="btn btn-light border-0 <?php echo $selectedCategory == 'Starters' ? 'active' : ''; ?>">
                    <i class="fas fa-hamburger me-2"></i> Starters
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Pasta" class="btn btn-light border-0 <?php echo $selectedCategory == 'Pasta' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils me-2"></i> Pasta
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Sandwich" class="btn btn-light border-0 <?php echo $selectedCategory == 'Sandwich' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bread-slice"></i> Sandwich
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Rice Meal" class="btn btn-light border-0 <?php echo $selectedCategory == 'Rice Meal' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-r me-2"></i>Rice Meal
                </a>
            </div>
            <div class="me-3">
                <a href="?category=All Day Breakfast" class="btn btn-light border-0 <?php echo $selectedCategory == 'All Day Breakfast' ? 'active' : ''; ?>">
                    <i class="fas fa-bacon me-2"></i> All Day Breakfast
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Add ons" class="btn btn-light border-0 <?php echo $selectedCategory == 'Add ons' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-square-plus"></i> Add ons
                </a>
            </div>
            <div class="me-3">
                <a href="?category=Upsize" class="btn btn-light border-0 <?php echo $selectedCategory == 'Upsize' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-arrow-up"></i> Upsize
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function filterByCategory() {
    const categoryFilter = document.getElementById('categoryFilter');
    const selectedCategory = categoryFilter.value;
    
    if (selectedCategory === '') {
        window.location.href = 'order-now.php';
    } else {
        window.location.href = '?category=' + encodeURIComponent(selectedCategory);
    }
}
</script>