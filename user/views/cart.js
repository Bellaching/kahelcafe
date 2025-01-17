$(document).ready(function() {
    // Update quantity in the cart
    $('.btn-increase, .btn-decrease').on('click', function() {
        var itemId = $(this).data('id');
        var currentQuantity = parseInt($('input[data-id="' + itemId + '"]').val());
        var newQuantity = currentQuantity;

        if ($(this).hasClass('btn-increase')) {
            newQuantity++;
        } else if ($(this).hasClass('btn-decrease') && currentQuantity > 1) {
            newQuantity--;
        }

        // Update the quantity in the input field
        $('input[data-id="' + itemId + '"]').val(newQuantity);

        // Send the updated quantity to the server
        $.ajax({
            url: 'cart.php',
            type: 'POST',
            data: {
                update_quantity: true,
                item_id: itemId,
                quantity: newQuantity
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    console.log("Quantity updated");
                }
            },
            error: function() {
                alert('An error occurred while updating the quantity.');
            }
        });
    });

    // Remove item from the cart
    $('.btn-remove').on('click', function() {
        var itemId = $(this).data('id');
        
        // Remove the item visually from the cart
        $(this).closest('tr.cart-item').remove();

        // Send request to remove the item from the session
        $.ajax({
            url: 'cart.php',
            type: 'POST',
            data: {
                remove_item: true,
                item_id: itemId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    console.log("Item removed from cart");
                }
            },
            error: function() {
                alert('An error occurred while removing the item.');
            }
        });
    });

    // Checkout process
    $('form[name="checkout_form"]').on('submit', function(event) {
        event.preventDefault();

        var userNote = $('#user-note').val();
        var reservationType = $('input[name="reservation_type"]:checked').val();

        $.ajax({
            url: 'cart.php',
            type: 'POST',
            data: {
                checkout: true,
                note: userNote,
                reservation_type: reservationType
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    window.location.href = 'order-confirmation.php'; // Redirect to confirmation page
                } else {
                    alert(data.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your checkout.');
            }
        });
    });
});
