<?php

include './../inc/header.php';
include './../inc/topNav.php';
?>







<!-- Banner -->
<div class="banner">
    
    <img src="./../asset/img/kahel-cafe-banner-hd.jpg" class="banner-img" alt="kahel cafe banner"/>
    <h1 id="banner_title">Kahel Cafe</h1>
</div>

<!-- Special Offers -->
<!-- <div class="special-offers">
    <h2 class="title-text">Popular Now</h2>
    
    <div class="orange-line">
        <img src="./../asset/img/special-offers/orange-line.png" class="orange-line-img" alt="orange line"/>
    </div> -->

    <!-- Special Offers -->
<div class="special-offers">
    <h2 class="title-text">Popular Now</h2>
    
    <div class="orange-line">
        <img src="./../asset/img/special-offers/orange-line.png" class="orange-line-img" alt="orange line"/>
    </div>

    <div class="special-offers-container">
        <!-- Cappuccino -->
        <div class="special-offers-menu">
            <div class="special-offers-image-container">
                <img src="./../asset/img/special-offers/cappuccino.png" class="menu" alt="cappuccino" width="309.42px" height="226px"/>

                <div class="special-offers-rating">
                    <span class="rating">4.8<span>
                    <img src="./../asset/img/special-offers/star.png" class="star" alt="star"/>
                </div>

                <div class="special-offers-details">
                    <h3>Cappuccino</h3>
                    <p>P110</p>
                    <button class="special-offers-btn">
                        <img src="./../asset/img/special-offers/cart.png" class="cart" alt="cart"/>
                        Add Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Espresso -->
        <div class="special-offers-menu">
            <div class="special-offers-image-container">
                <img class="menu" src="./../asset/img/special-offers/espresso.jpg" alt="espresso" width="309.42px" height="226px"/>

                <div class="special-offers-rating">
                    <span class="rating">4.8<span>
                    <img src="./../asset/img/special-offers/star.png" class="star" alt="star"/>
                </div>
                
                <div class="special-offers-details">
                    <h3>Espresso</h3>
                    <p>P140</p>
                    
                    <button class="special-offers-btn">
                        <img src="./../asset/img/special-offers/cart.png" class="cart" alt="cart"/>
                        Add Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Caffe Latte -->
        <div class="special-offers-menu">
            <div class="special-offers-image-container">
                <img src="./../asset/img/special-offers/caffe-latte.jpg" class="menu" alt="Caffe Latte" width="309.42px" height="226px"/>

                <div class="special-offers-rating">
                    <span class="rating">4.8<span>
                    <img src="./../asset/img/special-offers/star.png" class="star" alt="star"/>
                </div>

                <div class="special-offers-details">
                    <h3>Caffe Latte</h3>
                    <p>P110</p>
                    <button class="special-offers-btn">
                        <img src="./../asset/img/special-offers/cart.png" class="cart" alt="cart"/>
                        Add Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- <div class="special-offers-menu">
            <script>
                document.addEventListener("DOMContentLoaded", () => {
    fetch('../user/top-menus.php')
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('.special-offers-container');
            container.innerHTML = ''; // Clear existing items

            data.forEach(menu => {
                const menuHtml = `
                    <div class="special-offers-menu">
                        <div class="special-offers-image-container">
                            <img src="./../asset/img/special-offers/${menu.image}" class="menu" alt="${menu.name}" width="309.42px" height="226px"/>
                            
                            <div class="special-offers-rating">
                                <span class="rating">${menu.avg_rating.toFixed(1)}</span>
                                <img src="./../asset/img/special-offers/star.png" class="star" alt="star"/>
                            </div>
                            
                            <div class="special-offers-details">
                                <h3>${menu.name}</h3>
                                <p>P${menu.price.toFixed(2)}</p>
                                <button class="special-offers-btn">
                                    <img src="./../asset/img/special-offers/cart.png" class="cart" alt="cart"/>
                                    Add Order
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += menuHtml;
            });
        })
        .catch(error => console.error('Error fetching top menus:', error));
});

            </script>
        </div> -->
        


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


<!--Sched reservation-->
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

<?php include './../inc/footer.php'; ?>
