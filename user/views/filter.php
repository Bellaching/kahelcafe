<?php

    // Fetch menu items based on category filter
$totalItemsResult = $conn->query("SELECT COUNT(*) as count FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : ""));
$totalItems = $totalItemsResult->fetch_assoc()['count'];
$totalPages = ceil($totalItems / $itemsPerPage);

$sql = "SELECT * FROM menu1" . ($selectedCategory ? " WHERE category = '$selectedCategory'" : "") . " LIMIT $offset, $itemsPerPage";

?>
<style>
    /* Responsive adjustments */
@media (max-width: 768px) {
    .category-list {
        display: none ; /* Force hiding */
    }
    #categoryFilter {
        display: block ; /* Force showing */
    }
}
@media (min-width: 769px) {
    .category-list {
        display: block ; /* Force showing */
    }
    #categoryFilter {
        display: none ; /* Force hiding */
    }
}
</style>
<div class="container-fluid">
        <div class="row mb-3 category-list container-fluid">

            <div class="col-12 d-flex flex-wrap justify-content-center"> <!-- Center the buttons -->
                <!-- Category Filter -->
                <div class="mb-3 me-3 ">
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                        <option value="Coffe,Non-Coffe ">All</option> <!-- Added Show All option -->
                        <option value="Coffee">Coffee</option>
                        <option value="Non-Coffee">Non-Coffee</option>
                        <option value="Signature Frappe">Signature Frappe</option>
                        <option value="Starters">Starters</option>
                        <option value="Pasta">Pasta</option>
                        <option value="Sandwich">Sandwich</option>
                        <option value="Rice Meal">Rice Meal</option>
                        <option value="All Day Breakfast">All Day Breakfast</option>
                        <option value="Add ons">All Day Breakfast</option>
                        <option value="Upsize">All Day Breakfast</option>
                    </select>
                </div>
                

                <div class="me-3">
    <a href="?category=Popular Now" class="btn btn-light border-0 <?php echo $selectedCategory == 'Popular Now' ? 'active' : ''; ?>">
        <i class="fas fa-star me-2"></i> Popular Now
    </a>
</div>
<div class="me-3">
    <a href="?category=Coffee" class="btn btn-light border-0 <?php echo $selectedCategory == 'Coffee' ? 'active' : ''; ?>">
        <i class="fas fa-coffee me-2"></i> Coffee
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