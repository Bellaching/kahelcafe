<?php
include './../inc/topNav.php';
include './../../connection/connection.php';
include '../../admin/user/profile_header.php'; 
$clientId = $_SESSION['user_id'] ?? 0;
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

// Pagination 
$recordsPerPage = isset($_GET['records']) ? (int)$_GET['records'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --orange: #FF902B;
            --light-text: #6c757d;
            --lighter-text: #adb5bd;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
            scrollbar-width: thin;
            scrollbar-color: var(--orange) #f1f1f1;
            color: var(--light-text);
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #e07d1a;
        }
        
        .container {
            max-width: 1400px;
        }
        
        .history-header {
            margin-top: 30px;
            margin-bottom: 20px;
        }
        
        .history-header h2 {
            font-weight: bold;
            color: #495057;
        }
        
        .history-header .underline {
            height: 3px;
            width: 80px;
            background-color: var(--orange);
            margin-top: 5px;
            margin-bottom: 20px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .table {
            margin-bottom: 0;
            border: none;
        }
        
        .table thead th {
            border-bottom: none;
            background-color: #FFF8F2;
            color: #495057;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #FFF8F2;
        }
        
        .table tbody tr:nth-child(odd) {
            background-color: white;
        }
        
        .table tbody tr:hover {
            background-color: #FFEEDD;
        }
        
        .table th, .table td {
            border: none;
        }
        
        .nav-tabs .nav-link {
            color: var(--light-text);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--orange);
            font-weight: 600;
            border-bottom: 3px solid var(--orange);
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .alert-danger {
            margin-top: 20px;
        }
        
        /* Status badge colors */
        .badge-for-confirmation { background-color: #6c757d !important; }
        .badge-payment { background-color: #ffc107; color: #212529; }
        .badge-paid { background-color: #28a745; color: white; }
        .badge-booked { background-color: #6610f2; color: white; }
        .badge-rate-us { background-color: #fd7e14; color: white; }s
        .badge-cancel { background-color: #dc3545; color: white; }
        .badge-completed { background-color: #20c997; color: white; }
      
       
      
        
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            font-weight: 600;
            border-radius: 0.25rem;
        }
        
        .records-per-page {
            width: 80px;
            display: inline-block;
        }
        
        .pagination-info {
            margin-top: 10px;
            color: var(--light-text);
        }
        
        .serial-number {
            font-weight: bold;
            color: var(--light-text);
        }
        
        /* Pagination styling */
        .page-item.active .page-link {
            background-color: var(--orange);
            border-color: var(--orange);
        }
        
        .page-link {
            color: var(--orange);
        }
        
        @media (max-width: 576px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>

    <div class="container">

    <? include '../../admin/user/profile_header.php'; ?>
        <div class="history-header">
            <h2>Order History</h2>
            <div class="underline"></div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab == 'orders' ? 'active' : ''; ?>" href="?tab=orders&records=<?php echo $recordsPerPage; ?>&page=1">Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab == 'reservations' ? 'active' : ''; ?>" href="?tab=reservations&records=<?php echo $recordsPerPage; ?>&page=1">Reservations</a>
            </li>
        </ul>

        <?php if ($activeTab == 'orders'): ?>
            <!-- Orders Tab Content -->
            <div class="table-container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="search-container">
                            <div class="input-group">
                                <input type="text" class="form-control" id="ordersSearch" placeholder="Search orders...">
                                <button class="btn btn-outline-secondary" type="button" id="ordersSearchBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="mb-3">
                            <span class="text-muted me-2">Show:</span>
                            <select class="form-select records-per-page d-inline" id="ordersRecordsPerPage">
                                <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="30" <?php echo $recordsPerPage == 30 ? 'selected' : ''; ?>>30</option>
                                <option value="50" <?php echo $recordsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <?php
                    // Get total count of orders for this user
                    $countQuery = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
                    $countStmt = $conn->prepare($countQuery);
                    $countStmt->bind_param("i", $clientId);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $totalOrders = $countResult->fetch_assoc()['total'];
                    $totalPages = ceil($totalOrders / $recordsPerPage);

                    $ordersQuery = "SELECT 
                        o.order_id,
                        o.user_id,
                        o.client_full_name,
                        o.total_price,
                        o.transaction_id,
                        o.created_at,
                        o.reservation_type,
                        o.party_size,
                        o.status,
                        c.firstname,
                        c.lastname
                    FROM orders o
                    LEFT JOIN client c ON o.user_id = c.id
                    WHERE o.user_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT ?, ?";
                    
                    $ordersStmt = $conn->prepare($ordersQuery);
                    $ordersStmt->bind_param("iii", $clientId, $offset, $recordsPerPage);
                    $ordersStmt->execute();
                    $ordersResult = $ordersStmt->get_result();
                    
                    if (!$ordersResult) {
                        echo '<div class="alert alert-danger">Error executing orders query: ' . mysqli_error($conn) . '</div>';
                    } else {
                        $ordersCount = $ordersResult->num_rows;
                    ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Client Name</th>
                                <th>Total Price</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Reservation Type</th>
                                <th>Party Size</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($ordersCount > 0) {
                                $serialNumber = $offset + 1;
                                while ($order = $ordersResult->fetch_assoc()) {
                                    $statusClass = strtolower(str_replace(' ', '', $order['status']));
                                    echo "<tr>
                                    <td class='serial-number'>{$serialNumber}</td>
                                    <td>{$order['order_id']}</td>
                                    <td>{$order['client_full_name']}</td>
                                    <td>{$order['total_price']}</td>
                                    <td>{$order['transaction_id']}</td>
                                    <td>" . date('M d, Y h:i A', strtotime($order['created_at'])) . "</td>
                                    <td>{$order['reservation_type']}</td>
                                    <td>{$order['party_size']}</td>
                                    <td>
                                        <span class='badge badge-{$statusClass}'>
                                            " . ucfirst($order['status']) . "
                                        </span>
                                    </td>
                                </tr>";
                                    $serialNumber++;
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No orders found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $recordsPerPage, $totalOrders); ?> of <?php echo $totalOrders; ?> entries
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Page navigation" class="float-end">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?tab=orders&records=<?php echo $recordsPerPage; ?>&page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="?tab=orders&records=<?php echo $recordsPerPage; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?tab=orders&records=<?php echo $recordsPerPage; ?>&page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <script>
                // Update records per page
                document.getElementById('ordersRecordsPerPage').addEventListener('change', function() {
                    const records = this.value;
                    window.location.href = `?tab=orders&records=${records}&page=1`;
                });

                // Search functionality for orders
                document.getElementById('ordersSearchBtn').addEventListener('click', function() {
                    const searchTerm = document.getElementById('ordersSearch').value.toLowerCase();
                    const rows = document.querySelectorAll('.table tbody tr');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            </script>

        <?php else: ?>
            <!-- Reservations Tab Content -->
            <div class="table-container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="search-container">
                            <div class="input-group">
                                <input type="text" class="form-control" id="reservationsSearch" placeholder="Search reservations...">
                                <button class="btn btn-outline-secondary" type="button" id="reservationsSearchBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="mb-3">
                            <span class="text-muted me-2">Show:</span>
                            <select class="form-select records-per-page d-inline" id="reservationsRecordsPerPage">
                                <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="30" <?php echo $recordsPerPage == 30 ? 'selected' : ''; ?>>30</option>
                                <option value="50" <?php echo $recordsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <?php
                    // Get total count of reservations for this user
                    $countQuery = "SELECT COUNT(*) as total FROM reservation WHERE client_id = ?";
                    $countStmt = $conn->prepare($countQuery);
                    $countStmt->bind_param("i", $clientId);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $totalReservations = $countResult->fetch_assoc()['total'];
                    $totalPages = ceil($totalReservations / $recordsPerPage);

                    $reservationsQuery = "SELECT 
                        r.id,
                        r.transaction_code,
                        r.client_id,
                        r.clientFullName,
                        r.reservation_date,
                        r.reservation_time,
                        r.reservation_time_id,
                        r.party_size,
                        r.amount,
                        r.res_status,
                        r.date_created,
                        c.firstname,
                        c.lastname
                    FROM reservation r
                    LEFT JOIN client c ON r.client_id = c.id
                    WHERE r.client_id = ?
                    ORDER BY r.date_created DESC
                    LIMIT ?, ?";
                    
                    $reservationsStmt = $conn->prepare($reservationsQuery);
                    $reservationsStmt->bind_param("iii", $clientId, $offset, $recordsPerPage);
                    $reservationsStmt->execute();
                    $reservationsResult = $reservationsStmt->get_result();
                    
                    if (!$reservationsResult) {
                        echo '<div class="alert alert-danger">Error executing reservations query: ' . mysqli_error($conn) . '</div>';
                    } else {
                        $reservationsCount = $reservationsResult->num_rows;
                    ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Reservation Date</th>
                                <th>Reservation Time</th>
                                <th>Party Size</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($reservationsCount > 0) {
                                $serialNumber = $offset + 1;
                                while ($reservation = $reservationsResult->fetch_assoc()) {
                                    $statusClass = strtolower(str_replace(' ', '-', $reservation['res_status']));
                                    echo "<tr>
                                        <td class='serial-number'>{$serialNumber}</td>
                                        <td>{$reservation['id']}</td>
                                        <td>{$reservation['clientFullName']}</td>
                                        <td>" . date('M d, Y', strtotime($reservation['reservation_date'])) . "</td>
                                        <td>{$reservation['reservation_time']}</td>
                                        <td>{$reservation['party_size']}</td>
                                        <td>{$reservation['amount']}</td>
                                        <td><span class='badge badge-{$statusClass}'>" . ucfirst($reservation['res_status']) . "</span></td>
                                        <td>" . date('M d, Y h:i A', strtotime($reservation['date_created'])) . "</td>
                                    </tr>";
                                    $serialNumber++;
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No reservations found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $recordsPerPage, $totalReservations); ?> of <?php echo $totalReservations; ?> entries
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Page navigation" class="float-end">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?tab=reservations&records=<?php echo $recordsPerPage; ?>&page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="?tab=reservations&records=<?php echo $recordsPerPage; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?tab=reservations&records=<?php echo $recordsPerPage; ?>&page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <script>
                // Update records per page
                document.getElementById('reservationsRecordsPerPage').addEventListener('change', function() {
                    const records = this.value;
                    window.location.href = `?tab=reservations&records=${records}&page=1`;
                });

                // Search functionality for reservations
                document.getElementById('reservationsSearchBtn').addEventListener('click', function() {
                    const searchTerm = document.getElementById('reservationsSearch').value.toLowerCase();
                    const rows = document.querySelectorAll('.table tbody tr');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
</body>
</html>