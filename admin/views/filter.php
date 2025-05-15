<link rel="stylesheet" href="menu.css">
<div class="container-fluid">

<div class="mb-3 me-3 mt-3">
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                        <option value="">All</option> <!-- Added Show All option -->
                        <option value="Espresso">Espresso</option>
                        <option value="Non-Coffee">Non-Coffee</option>
                        <option value="Signatures">Signatures</option>
                        <option value="Frappe">Frappe</option>
                        <option value="Starters">Starters</option>
                        <option value="Pasta">Pasta</option>
                        <option value="Sandwich">Sandwich</option>
                        <option value="Rice Meal">Rice Meal</option>
                        <option value="All Day Breakfast">All Day Breakfast</option>
                        <option value="Add ons">Add ons</option>
                       
                    </select>
                </div>
                
        <div class="row mb-3 category-list container-fluid">

            <div class="col-12 d-flex flex-wrap justify-content-center"> <!-- Center the buttons -->
                <!-- Category Filter -->
             
<div class="me-3">
    <a href="?category=Espresso" class="btn btn-light border-0 <?php echo $selectedCategory == 'Espresso' ? 'active' : ''; ?>">
        <i class="fas fa-coffee me-2"></i> Espresso
    </a>
</div>
<div class="me-3">
    <a href="?category=Non-Coffee" class="btn btn-light border-0 <?php echo $selectedCategory == 'Non-Coffee' ? 'active' : ''; ?>">
        <i class="fas fa-glass-cheers me-2"></i> Non-Coffee
    </a>
</div>
<div class="me-3">
    <a href="?category=Signatures" class="btn btn-light border-0 <?php echo $selectedCategory == 'Signatures' ? 'active' : ''; ?>">
        <i class="fas fa-star me-2"></i> Signatures
    </a>
</div>

<div class="me-3">
    <a href="?category=Frappe (cream base)" class="btn btn-light border-0 <?php echo $selectedCategory == 'Frappe (cream base)' ? 'active' : ''; ?>">
        <i class="fas fa-ice-cream me-2"></i> Frappe (cream)
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



            </div>
        </div>