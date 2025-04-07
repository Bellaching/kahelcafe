<?php 
include './../../connection/connection.php';
include './../inc/topNav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">



    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        .modal-body {
            display: flex;
            flex-direction: column;
        }

        .update-down {
            display: flex;
            flex-direction: column;
        }

        .order-sum {
            display: flex;
            flex-direction: column;
        }
        
        @media (min-width: 768px) {
            .update-down {
                flex-direction: column;
            }
            .order-sum {
                flex-direction: row;
            }
        }

        .order-s {
            color: #FF902B;
        }

        .p1 {
            color: #FF902B;
        }

        .p {
            color: #616161;
        }

        .l {
            color: #000000;
        }

        .upsta {
            color: white;
            background-color: #FF902B;
            border: none;
        }

        .theadmodal {
            background-color: #FF902B;
        }

        .account-text {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        @media (min-width: 768px) {
            .account-text {
                font-size: 2rem;
            }
        }
        
        .management-underline {
            position: relative;
        }
        
        .management-underline::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 100%;
            height: 3px;
            background-color: #FF902B;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .info-label {
            min-width: 150px;
        }

        .editBtn,
        .deleteBtn {
            background: none;
            border: none;
        }

        .editBtn {
            color: #624DE3;
        }
        .deleteBtn {
            color: #A30D11;
        }
    </style>
</head>

<body>
<div class="container-fluid mb-3">
        <div class="row mt-5 ms-5">
            <div class="col-12 col-md-10 col-lg-8">
                <p class="account-text">
                    Reservation <span class="management-underline">Management</span>
                </p>
            </div>
        </div>
    </div>

    <div class="container-fluid px-3 px-md-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="table-container shadow p-3 bg-body-tertiary rounded">
                    <table id="userTable" class="display table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Transaction Code</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table data will be filled dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Delete Confirmation -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this order?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelDeleteBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

  <!-- Modal for Update User Status -->
<div class="modal fade" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            
            <!-- HEADER WITH CLOSE BUTTON -->
            <div style="background-color: #FF902B; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                <h5 class="mb-0 text-white">Order Summary</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="updateStatusForm">
                    <div class="update-down">

                        <div class="order-sum row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Client Name</label>
                                    <p id="client_full_name_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Transaction no.</label>
                                    <p id="transaction_code_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Party Size</label>
                                    <p id="party_size_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-1 align-items-baseline mt-2">
                                    <label class="l info-label fw-bold">Total Price</label>
                                    <p id="total_price_display" class="p1 mb-0 ms-2 fw-bold text-break"></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Reservation Date</label>
                                    <p id="reservation_date_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Time</label>
                                    <p id="reservation_time_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Reservation fee</label>
                                    <p id="reservation_fee_display" class="p mb-0 ms-2 text-break"></p>
                                </div>

                                <div class="d-flex flex-row mb-3 align-items-baseline">
                                    <label class="l info-label">Payment Receipt</label>
                                    <div id="receipt_display" class="ms-2">
                                        <!-- Receipt will be displayed here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <label for="status" class="fw-bold">Order Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="for confirmation">For Confirmation</option>
                                <option value="payment">Payment</option>
                                <option value="booked">Booked</option>
                                <option value="rate us">Complete</option>
                                <option value="cancel">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" id="transactionCode" name="transactionCode">
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="upsta btn btn-primary rounded-pill px-5" id="saveStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>


    <!-- jQuery, DataTable, and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>


    <!-- Custom JS -->
    <script src="./../js/reservation.js"></script>
</body>
</html>