<?php 
include './../../connection/connection.php';
include './../inc/topNav.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gcash_number = trim($_POST['gcash_number']);
    $gcash_name = trim($_POST['gcash_name']);
    $reservation_fee = trim($_POST['reservation_fee']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($gcash_number)) {
        $errors['gcash_number'] = 'GCash number is required';
    } elseif (!preg_match('/^(09|\+639)\d{9}$/', $gcash_number)) {
        $errors['gcash_number'] = 'Please enter a valid GCash number (e.g. 09123456789)';
    }
    
    if (empty($gcash_name)) {
        $errors['gcash_name'] = 'GCash name is required';
    }
    
    if (empty($reservation_fee)) {
        $errors['reservation_fee'] = 'Reservation fee is required';
    } elseif (!is_numeric($reservation_fee) || $reservation_fee <= 0) {
        $errors['reservation_fee'] = 'Reservation fee must be a positive number';
    }
    
    if (empty($errors)) {
        // Check if record exists
        $check_sql = "SELECT COUNT(*) FROM payment_settings";
        $check_result = $conn->query($check_sql);
        $row = $check_result->fetch_row();
        $count = $row[0];
        
        if ($count > 0) {
            // Update existing record
            $sql = "UPDATE payment_settings SET gcash_number = ?, gcash_name = ?, reservation_fee = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $gcash_number, $gcash_name, $reservation_fee);
        } else {
            // Insert new record
            $sql = "INSERT INTO payment_settings (gcash_number, gcash_name, reservation_fee) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $gcash_number, $gcash_name, $reservation_fee);
        }
        
        if ($stmt->execute()) {
            $success_message = "Payment settings updated successfully!";
        } else {
            $error_message = "Error updating payment settings: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get current settings
$current_settings = [
    'gcash_number' => '',
    'gcash_name' => '',
    'reservation_fee' => ''
];

$sql = "SELECT gcash_number, gcash_name, reservation_fee FROM payment_settings LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $current_settings = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF902B;
            --primary-hover: #E07D22;
        }
        
        body {
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        
        .settings-card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .settings-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .gcash-logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
        }
        
        .was-validated .form-control:invalid ~ .invalid-feedback {
            display: block;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .settings-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card settings-card mb-4">
                    <div class="card-header settings-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-cog settings-icon"></i> Payment Settings</h4>
                        
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="paymentSettingsForm" method="POST" novalidate>
                            <div class="mb-3">
                                <label for="gcash_number" class="form-label">GCash Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                    <input type="text" class="form-control <?php echo isset($errors['gcash_number']) ? 'is-invalid' : ''; ?>" 
                                           id="gcash_number" name="gcash_number" 
                                           value="<?php echo htmlspecialchars($current_settings['gcash_number']); ?>" 
                                           placeholder="e.g. 09123456789" required>
                                </div>
                                <?php if (isset($errors['gcash_number'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['gcash_number']; ?></div>
                                <?php else: ?>
                                    <div class="invalid-feedback">Please enter a valid GCash number (e.g. 09123456789)</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gcash_name" class="form-label">GCash Account Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control <?php echo isset($errors['gcash_name']) ? 'is-invalid' : ''; ?>" 
                                           id="gcash_name" name="gcash_name" 
                                           value="<?php echo htmlspecialchars($current_settings['gcash_name']); ?>" 
                                           placeholder="e.g. Juan Dela Cruz" required>
                                </div>
                                <?php if (isset($errors['gcash_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['gcash_name']; ?></div>
                                <?php else: ?>
                                    <div class="invalid-feedback">Please enter the GCash account name</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <label for="reservation_fee" class="form-label">Reservation Fee (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['reservation_fee']) ? 'is-invalid' : ''; ?>" 
                                           id="reservation_fee" name="reservation_fee" 
                                           value="<?php echo htmlspecialchars($current_settings['reservation_fee']); ?>" 
                                           placeholder="e.g. 100.00" required>
                                </div>
                                <?php if (isset($errors['reservation_fee'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['reservation_fee']; ?></div>
                                <?php else: ?>
                                    <div class="invalid-feedback">Please enter a valid reservation fee amount</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Client-side validation
            $('#paymentSettingsForm').on('submit', function(e) {
                const form = this;
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                $(form).addClass('was-validated');
                
                // Custom validation for GCash number
                const gcashNumber = $('#gcash_number').val().trim();
                const gcashRegex = /^(09|\+639)\d{9}$/;
                if (!gcashRegex.test(gcashNumber)) {
                    $('#gcash_number').addClass('is-invalid');
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                // Custom validation for reservation fee
                const reservationFee = $('#reservation_fee').val().trim();
                if (isNaN(reservationFee) || parseFloat(reservationFee) <= 0) {
                    $('#reservation_fee').addClass('is-invalid');
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            // Real-time validation for GCash number
            $('#gcash_number').on('input', function() {
                const gcashNumber = $(this).val().trim();
                const gcashRegex = /^(09|\+639)\d{9}$/;
                
                if (gcashRegex.test(gcashNumber)) {
                    $(this).removeClass('is-invalid');
                } else {
                    $(this).addClass('is-invalid');
                }
            });
            
            // Real-time validation for reservation fee
            $('#reservation_fee').on('input', function() {
                const fee = $(this).val().trim();
                
                if (!isNaN(fee) && parseFloat(fee) > 0) {
                    $(this).removeClass('is-invalid');
                } else {
                    $(this).addClass('is-invalid');
                }
            });
        });
    </script>
</body>
</html>