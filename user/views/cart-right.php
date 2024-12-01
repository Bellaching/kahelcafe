<?php


 
$stmt->close();
?>

<style>
    .proceedBtn{
        background-color: #FF902A;
        border: none;
        border-radius: 15px;
    }

    .order-h4{
        color: #FF902A;
        font-weight: bold;
    }
</style>





<div class="col-lg-4">
   
<div class="card mt-3">
       <!-- Seat Reservation -->
       <strong><h4 class="m-3 order-h4">Seat Reservation</h4></strong> 
        <div class="card-body">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #17A1FA; border-radius: 5px;">
                No Slots
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #07D090; border-radius: 5px;">
                Available
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #E60000; border-radius: 5px;">
                Fully Booked
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #9647FF; border-radius: 5px;">
                Your Reservation
              </div>
            </div>
          </div>
        </div>




    <!-- Order Summary Card -->
 
       <strong><h4 class="m-3 order-h4">Order Summary</h4></strong> 
        <div class="card-body">
            <p>
                <strong>Name:</strong> 
                <span class="float-end"><?php echo $clientFullName; ?></span>
            </p>
            <p>
                <strong>Transaction ID:</strong> 
                <span class="float-end"><?php echo htmlspecialchars($_SESSION['transaction_id']); ?></span>
            </p>
            <p class="card-text" id="totalAmount">
            <strong class="fs-5" style="color: #616161;">Total:</strong>

            <strong> <span class="float-end fs-5" style="color: #FF902B;">₱<?php echo number_format($totalPrice, 2); ?></span></strong> 
            </p>
            <div class="text-end">
                <a href="checkout.php" class="btn btn-success container-fluid proceedBtn">Proceed to Checkout</a>
            </div>
        </div>
    </div>
</div>

