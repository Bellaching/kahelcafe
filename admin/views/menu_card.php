<div class="container">
    <div class="row g-0" id="menu-card-container">
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
                    $food_price = htmlspecialchars($row['food_price'], ENT_QUOTES, 'UTF-8');
                    $id = intval($row['id']);

                    // Only display items in the "Rice Meal" category
                    if ($category === 'Rice Meal') {
                        $price_display = '';
                        if (!empty($price_small) && $price_small > 0 && !empty($price_medium) && $price_medium > 0 && !empty($price_large) && $price_large > 0) {
                            $price_display = "₱" . $price_small . " to ₱" . $price_large;
                        } elseif (!empty($price_small) && $price_small > 0 && !empty($price_medium) && $price_medium > 0) {
                            $price_display = "₱" . $price_small . " to ₱" . $price_medium;
                        } elseif (!empty($price_medium) && $price_medium > 0 && !empty($price_large) && $price_large > 0) {
                            $price_display = "₱" . $price_medium . " to ₱" . $price_large;
                        } elseif (!empty($price_small) && $price_small > 0) {
                            $price_display = "₱" . $price_small;
                        } elseif (!empty($price_medium) && $price_medium > 0) {
                            $price_display = "₱" . $price_medium;
                        } elseif (!empty($price_large) && $price_large > 0) {
                            $price_display = "₱" . $price_large;
                        } elseif (!empty($food_price) && $food_price > 0) {
                            $price_display = "₱" . $food_price;
                        }

                        // Build the output for each menu item
                        $output .= '
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-card shadow-sm">
                            <div class="card p-2 rounded-1" style="border: none;">
                                <div class="img-container" style="overflow: hidden; height: 150px;">
                                    <img src="' . $image . '" class="card-img-top" alt="' . $menu_name . '" style="height: 100%; width: 100%; object-fit: cover;">
                                </div>
                                <div class="card-body text-center p-1">
                                    <div class="menu-item-container">
                                        <div class="category-title">
                                            <h5>' . $menu_name . '</h5>
                                        </div>
                                        <div class="price-info">
                                            <p class="text-success"><strong>' . $price_display . '</strong></p>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm p-2" onclick="openUpdateMenuModal(' . $id . ')">
                                        <i class="fa-solid fa-cart-shopping"></i> Update Menu
                                    </button>

                                    <button class="btn btn-delete btn-danger" onclick="confirmDelete(' . $id . ')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>';
                    }
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
