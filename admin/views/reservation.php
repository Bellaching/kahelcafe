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
    <!-- Add necessary CSS for DataTable and Modal -->
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap for Modal -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

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
            flex-direction: row;
        }

        .order-sum {
            display: flex;
            flex-direction: row;
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
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid mb-3">
        <div class="row mt-5 ms-5">
            <div class="col-12 col-md-10 col-lg-8">
                <p class="account-text">
                    Order <span class="management-underline">Management</span>
                </p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center w-100">
        <div class="container-fluid shadow p-3 mx-5 bg-body-tertiary rounded">
            <table id="userTable" class="display">
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

    <!-- Modal for Delete Confirmation -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete Confirmation</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
    <div class="modal fade container-fluid" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content container-fluid">
                <div class="modal-body container-fluid">
                    <form id="updateStatusForm">
                        <div class="form-group update-down d-flex flex-column">
                            <h5 class="order-s">Order Summary</h5>
                            <div class="form-group order-sum d-flex flex-row">
                                <div class="d-flex flex-column gap-3 m-3">
                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Client Name</label>
                                        <p id="client_full_name_display" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Transaction no.</label>
                                        <p id="transaction_id" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Reservation Type</label>
                                        <p id="reservation_type" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Party Size</label>
                                        <p id="party_size" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-1 align-items-baseline mt-2">
                                        <label class="l mr-5 fs-5 bold">Total Price</label>
                                        <p id="total_price1" class="p1 mb-0 ml-auto bold text-right">P</p>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-3 m-3">
                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Date</label>
                                        <p id="created_at" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Time</label>
                                        <p id="transaction_id" class="p mb-0 ml-auto text-right"></p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Sub total</label>
                                        <p id="total_price" class="p mb-0 ml-auto bold text-right">P</p>
                                    </div>

                                    <div class="d-flex flex-row mb-3 align-items-baseline">
                                        <label class="l mr-5">Reservation fee</label>
                                        <p id="party_size" class="p mb-0 ml-auto text-right"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group p-3 mb-5 mx-5">
                                <label for="status" class="fw-bold">Order Status</label>
                                <select class="form-control container-fluid" id="status" name="status">
                                    <option value="for confirmation">For Confirmation</option>
                                    <option value="for payment">Payment</option>
                                    <option value="booked">Booked</option>
                                    <option value="rate us">Rate Us</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="transactionCode" name="transactionCode">
                    </form>
                </div>
                <div class="mx-auto m-5">
                    <button type="button" class="upsta btn btn-primary rounded-pill px-5 container-fluid" id="saveStatusBtn">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, DataTable, and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="./../js/reservation.js"></script>
</body>
</html>