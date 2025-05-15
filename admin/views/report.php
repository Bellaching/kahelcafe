<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Backend: Fetch data from both tables
include './../inc/topNav.php'; 
include './../../connection/connection.php'; // Include your database connection

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize date range variables
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default: Start of the current month
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Default: End of the current month

// Fetch Reservation data from the `reservation` table (status = 'rate it')
$reservationQuery = "SELECT COUNT(*) as count FROM reservation 
                     WHERE res_status = 'rate us' 
                     AND date_created BETWEEN ? AND ?";
$stmtReservation = $conn->prepare($reservationQuery);
if (!$stmtReservation) {
    die("SQL Error (Reservation): " . $conn->error);
}
$stmtReservation->bind_param("ss", $dateFrom, $dateTo);
$stmtReservation->execute();
$reservationResult = $stmtReservation->get_result();
$reservationCount = $reservationResult->fetch_assoc()['count'];
$stmtReservation->close();

// Fetch Pickup and Over the Counter data from the `orders` table (status = 'rate it')
$ordersQuery = "SELECT reservation_type, COUNT(*) as count FROM orders 
                WHERE status = 'rate us' 
                AND created_at BETWEEN ? AND ? 
                AND reservation_type IN ('Pickup', 'Over the counter')
                GROUP BY reservation_type";
$stmtOrders = $conn->prepare($ordersQuery);
if (!$stmtOrders) {
    die("SQL Error (Orders): " . $conn->error);
}
$stmtOrders->bind_param("ss", $dateFrom, $dateTo);
$stmtOrders->execute();
$ordersResult = $stmtOrders->get_result();

$pickupCount = 0;
$overTheCounterCount = 0;

while ($row = $ordersResult->fetch_assoc()) {
    if ($row['reservation_type'] === 'Pickup') {
        $pickupCount = $row['count'];
    } elseif ($row['reservation_type'] === 'Over the counter') {
        $overTheCounterCount = $row['count'];
    }
}
$stmtOrders->close();

// Fetch data for morning, afternoon, and evening from the `orders` table (status = 'rate us')
$timeQuery = "SELECT 
                CASE 
                    WHEN HOUR(created_at) BETWEEN 6 AND 11 THEN 'Morning'
                    WHEN HOUR(created_at) BETWEEN 12 AND 17 THEN 'Afternoon'
                    WHEN HOUR(created_at) BETWEEN 18 AND 23 THEN 'Evening'
                    ELSE 'Night'
                END AS time_period,
                COUNT(*) as count 
              FROM orders 
              WHERE status = 'rate us' 
              AND created_at BETWEEN ? AND ? 
              GROUP BY time_period";
$stmtTime = $conn->prepare($timeQuery);
if (!$stmtTime) {
    die("SQL Error (Time): " . $conn->error);
}
$stmtTime->bind_param("ss", $dateFrom, $dateTo);
$stmtTime->execute();
$timeResult = $stmtTime->get_result();

$morningCount = 0;
$afternoonCount = 0;
$eveningCount = 0;
$totalOrders = 0;

while ($row = $timeResult->fetch_assoc()) {
    if ($row['time_period'] === 'Morning') {
        $morningCount = $row['count'];
    } elseif ($row['time_period'] === 'Afternoon') {
        $afternoonCount = $row['count'];
    } elseif ($row['time_period'] === 'Evening') {
        $eveningCount = $row['count'];
    }
    $totalOrders += $row['count'];
}
$stmtTime->close();

// Calculate percentages
$morningPercentage = $totalOrders > 0 ? ($morningCount / $totalOrders) * 100 : 0;
$afternoonPercentage = $totalOrders > 0 ? ($afternoonCount / $totalOrders) * 100 : 0;
$eveningPercentage = $totalOrders > 0 ? ($eveningCount / $totalOrders) * 100 : 0;

// Fetch total revenue for the current month (status = 'rate us')
$currentMonthFrom = date('Y-m-01'); // First day of the current month
$currentMonthTo = date('Y-m-t'); // Last day of the current month

$currentRevenueQuery = "SELECT SUM(total_price) as total_revenue FROM orders 
                        WHERE status = 'rate us' 
                        AND created_at BETWEEN ? AND ?";
$stmtCurrentRevenue = $conn->prepare($currentRevenueQuery);
if (!$stmtCurrentRevenue) {
    die("SQL Error (Current Revenue): " . $conn->error);
}
$stmtCurrentRevenue->bind_param("ss", $currentMonthFrom, $currentMonthTo);
$stmtCurrentRevenue->execute();
$currentRevenueResult = $stmtCurrentRevenue->get_result();
$currentRevenue = $currentRevenueResult->fetch_assoc()['total_revenue'] ?? 0;
$stmtCurrentRevenue->close();

// Fetch total revenue for the previous month (status = 'rate us')
$previousMonthFrom = date('Y-m-01', strtotime('-1 month')); // First day of the previous month
$previousMonthTo = date('Y-m-t', strtotime('-1 month')); // Last day of the previous month

$previousRevenueQuery = "SELECT SUM(total_price) as total_revenue FROM orders 
                         WHERE status = 'rate us' 
                         AND created_at BETWEEN ? AND ?";
$stmtPreviousRevenue = $conn->prepare($previousRevenueQuery);
if (!$stmtPreviousRevenue) {
    die("SQL Error (Previous Revenue): " . $conn->error);
}
$stmtPreviousRevenue->bind_param("ss", $previousMonthFrom, $previousMonthTo);
$stmtPreviousRevenue->execute();
$previousRevenueResult = $stmtPreviousRevenue->get_result();
$previousRevenue = $previousRevenueResult->fetch_assoc()['total_revenue'] ?? 0;
$stmtPreviousRevenue->close();

// Calculate percentage change
$percentageChange = 0;
if ($previousRevenue > 0) {
    $percentageChange = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
}

$totalCount = $reservationCount + $pickupCount + $overTheCounterCount;
if ($totalCount > 0) {
    $reservationPercentage = ($reservationCount / $totalCount) * 100;
    $pickupPercentage = ($pickupCount / $totalCount) * 100;
    $overTheCounterPercentage = ($overTheCounterCount / $totalCount) * 100;
} else {
    $reservationPercentage = 0;
    $pickupPercentage = 0;
    $overTheCounterPercentage = 0;
}
// Prepare data for the reservation type chart
$labels = [];
$data = [];
$colors = ['#FF902B', '#FFC38B', '#FCE7D3']; // Custom colors

if ($reservationCount > 0) {
    $labels[] = 'Reservation';
    $data[] = $reservationPercentage;
}
if ($pickupCount > 0) {
    $labels[] = 'Pickup';
    $data[] = $pickupPercentage;
}
if ($overTheCounterCount > 0) {
    $labels[] = 'Over the Counter';
    $data[] = $overTheCounterPercentage;
}

// Prepare data for the time period chart
$timeLabels = [];
$timeData = [];
$timeColors = ['#FCE7D3', '#FF902B', '#FFC38B']; // Morning, Afternoon, Evening

if ($morningCount > 0) {
    $timeLabels[] = 'Morning';
    $timeData[] = $morningPercentage;
}
if ($afternoonCount > 0) {
    $timeLabels[] = 'Afternoon';
    $timeData[] = $afternoonPercentage;
}
if ($eveningCount > 0) {
    $timeLabels[] = 'Evening';
    $timeData[] = $eveningPercentage;
}
// Fetch counts of food and drink orders by month
$query = "
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') AS month,
        m.type, 
        COUNT(*) AS count
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    JOIN 
        menu1 m ON oi.item_id = m.id
    WHERE 
        o.status = 'rate us'
        AND o.created_at BETWEEN ? AND ?
        AND m.type IN ('food', 'drink')
    GROUP BY 
        month, m.type
    ORDER BY 
        month;
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$result = $stmt->get_result();

$foodData = [];
$drinkData = [];
$labelsFoodDrink = [];

while ($row = $result->fetch_assoc()) {
    $month = $row['month'];
    if (!in_array($month, $labelsFoodDrink)) {
        $labelsFoodDrink[] = $month;
    }
    if ($row['type'] === 'food') {
        $foodData[$month] = $row['count'];
    } elseif ($row['type'] === 'drink') {
        $drinkData[$month] = $row['count'];
    }
}

$stmt->close();

// Ensure the chart gets 0 for months with no orders for that type
$foodCounts = [];
$drinkCounts = [];

foreach ($labelsFoodDrink as $month) {
    $foodCounts[] = isset($foodData[$month]) ? $foodData[$month] : 0;
    $drinkCounts[] = isset($drinkData[$month]) ? $drinkData[$month] : 0;
}


// Initialize date range variables
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default: Start of the current month
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Default: End of the current month
// Fetch top 4 most ordered food items
$topFoodQuery = "
    SELECT 
        m.name, 
        m.price,
        COUNT(oi.item_id) as order_count
    FROM 
        order_items oi
    JOIN 
        menu1 m ON oi.item_id = m.id
    JOIN 
        orders o ON oi.order_id = o.order_id
   WHERE 
    o.status = 'rate us'
    AND o.created_at BETWEEN ? AND ?
    AND m.type IN ('food', 'drink')

    GROUP BY 
        m.id, m.name, m.price
    ORDER BY 
        order_count DESC
    LIMIT 4;
";
$stmtTopFood = $conn->prepare($topFoodQuery);
if (!$stmtTopFood) {
    die("SQL Error (Top Food): " . $conn->error);
}
$stmtTopFood->bind_param("ss", $dateFrom, $dateTo);
$stmtTopFood->execute();
$topFoodResult = $stmtTopFood->get_result();

$topFoodItems = [];
while ($row = $topFoodResult->fetch_assoc()) {
    $topFoodItems[] = $row;
}
$stmtTopFood->close();

// Fetch order count for the selected date range
$orderCountQuery = "SELECT COUNT(*) as count FROM orders 
                   WHERE status = 'rate us' 
                   AND created_at BETWEEN ? AND ?";
$stmtOrderCount = $conn->prepare($orderCountQuery);
if (!$stmtOrderCount) {
    die("SQL Error (Order Count): " . $conn->error);
}
$stmtOrderCount->bind_param("ss", $dateFrom, $dateTo);
$stmtOrderCount->execute();
$orderCountResult = $stmtOrderCount->get_result();
$orderCount = $orderCountResult->fetch_assoc()['count'];
$stmtOrderCount->close();

// Fetch order count for the previous comparable period (same duration before dateFrom)
$prevDateFrom = date('Y-m-d', strtotime($dateFrom . ' -' . (strtotime($dateTo) - strtotime($dateFrom) . ' seconds')));
$prevDateTo = date('Y-m-d', strtotime($dateFrom . ' -1 day'));

$prevOrderCountQuery = "SELECT COUNT(*) as count FROM orders 
                       WHERE status = 'rate us' 
                       AND created_at BETWEEN ? AND ?";
$stmtPrevOrderCount = $conn->prepare($prevOrderCountQuery);
if (!$stmtPrevOrderCount) {
    die("SQL Error (Previous Order Count): " . $conn->error);
}
$stmtPrevOrderCount->bind_param("ss", $prevDateFrom, $prevDateTo);
$stmtPrevOrderCount->execute();
$prevOrderCountResult = $stmtPrevOrderCount->get_result();
$prevOrderCount = $prevOrderCountResult->fetch_assoc()['count'] ?? 0;
$stmtPrevOrderCount->close();

// Calculate percentage change compared to previous period
$percentageChange = 0;
if ($prevOrderCount > 0) {
    $percentageChange = (($orderCount - $prevOrderCount) / $prevOrderCount) * 100;
}
//cards
// Fetch total sales for today
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Total Sales for Today
$totalSalesQuery = "SELECT SUM(total_price) as total_sales FROM orders 
                    WHERE status = 'rate us' 
                    AND DATE(created_at) = ?";
$stmtTotalSales = $conn->prepare($totalSalesQuery);
$stmtTotalSales->bind_param("s", $today);
$stmtTotalSales->execute();
$totalSalesResult = $stmtTotalSales->get_result();
$totalSalesToday = $totalSalesResult->fetch_assoc()['total_sales'] ?? 0;
$stmtTotalSales->close();

// Total Sales for Yesterday
$stmtTotalSalesYesterday = $conn->prepare($totalSalesQuery);
$stmtTotalSalesYesterday->bind_param("s", $yesterday);
$stmtTotalSalesYesterday->execute();
$totalSalesYesterdayResult = $stmtTotalSalesYesterday->get_result();
$totalSalesYesterday = $totalSalesYesterdayResult->fetch_assoc()['total_sales'] ?? 0;
$stmtTotalSalesYesterday->close();

// Calculate percentage difference for total sales
$salesPercentageDifference = 0;
if ($totalSalesYesterday > 0) {
    $salesPercentageDifference = (($totalSalesToday - $totalSalesYesterday) / $totalSalesYesterday) * 100;
}

// Total Orders for Today
$totalOrdersQuery = "SELECT COUNT(*) as total_orders FROM orders 
                     WHERE status = 'rate us' 
                     AND DATE(created_at) = ?";
$stmtTotalOrders = $conn->prepare($totalOrdersQuery);
$stmtTotalOrders->bind_param("s", $today);
$stmtTotalOrders->execute();
$totalOrdersResult = $stmtTotalOrders->get_result();
$totalOrdersToday = $totalOrdersResult->fetch_assoc()['total_orders'] ?? 0;
$stmtTotalOrders->close();

// Total Orders for Yesterday
$stmtTotalOrdersYesterday = $conn->prepare($totalOrdersQuery);
$stmtTotalOrdersYesterday->bind_param("s", $yesterday);
$stmtTotalOrdersYesterday->execute();
$totalOrdersYesterdayResult = $stmtTotalOrdersYesterday->get_result();
$totalOrdersYesterday = $totalOrdersYesterdayResult->fetch_assoc()['total_orders'] ?? 0;
$stmtTotalOrdersYesterday->close();

// Calculate percentage difference for total orders
$ordersPercentageDifference = 0;
if ($totalOrdersYesterday > 0) {
    $ordersPercentageDifference = (($totalOrdersToday - $totalOrdersYesterday) / $totalOrdersYesterday) * 100;
}

// Total Products Sold for Today
$totalProductsSoldQuery = "SELECT COUNT(*) as total_products_sold FROM order_items oi
                           JOIN orders o ON oi.order_id = o.order_id
                           WHERE o.status = 'rate us' 
                           AND DATE(o.created_at) = ?";
$stmtTotalProductsSold = $conn->prepare($totalProductsSoldQuery);
$stmtTotalProductsSold->bind_param("s", $today);
$stmtTotalProductsSold->execute();
$totalProductsSoldResult = $stmtTotalProductsSold->get_result();
$totalProductsSoldToday = $totalProductsSoldResult->fetch_assoc()['total_products_sold'] ?? 0;
$stmtTotalProductsSold->close();

// Total Products Sold for Yesterday
$stmtTotalProductsSoldYesterday = $conn->prepare($totalProductsSoldQuery);
$stmtTotalProductsSoldYesterday->bind_param("s", $yesterday);
$stmtTotalProductsSoldYesterday->execute();
$totalProductsSoldYesterdayResult = $stmtTotalProductsSoldYesterday->get_result();
$totalProductsSoldYesterday = $totalProductsSoldYesterdayResult->fetch_assoc()['total_products_sold'] ?? 0;
$stmtTotalProductsSoldYesterday->close();

// Calculate percentage difference for products sold
$productsSoldPercentageDifference = 0;
if ($totalProductsSoldYesterday > 0) {
    $productsSoldPercentageDifference = (($totalProductsSoldToday - $totalProductsSoldYesterday) / $totalProductsSoldYesterday) * 100;
}

// New Customers for Today
$newCustomersQuery = "SELECT COUNT(*) as new_customers FROM client
                      WHERE DATE(created_at) = ?";
$stmtNewCustomers = $conn->prepare($newCustomersQuery);
$stmtNewCustomers->bind_param("s", $today);
$stmtNewCustomers->execute();
$newCustomersResult = $stmtNewCustomers->get_result();
$newCustomersToday = $newCustomersResult->fetch_assoc()['new_customers'] ?? 0;
$stmtNewCustomers->close();

// New Customers for Yesterday
$stmtNewCustomersYesterday = $conn->prepare($newCustomersQuery);
$stmtNewCustomersYesterday->bind_param("s", $yesterday);
$stmtNewCustomersYesterday->execute();
$newCustomersYesterdayResult = $stmtNewCustomersYesterday->get_result();
$newCustomersYesterday = $newCustomersYesterdayResult->fetch_assoc()['new_customers'] ?? 0;
$stmtNewCustomersYesterday->close();

// Calculate percentage difference for new customers
$newCustomersPercentageDifference = 0;
if ($newCustomersYesterday > 0) {
    $newCustomersPercentageDifference = (($newCustomersToday - $newCustomersYesterday) / $newCustomersYesterday) * 100;
}
// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .chart-container {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card-stat {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .stat-change {
            font-size: 0.85rem;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .page-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .page-title span {
            position: relative;
        }
        .page-title span:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #FF902B;
        }
        @media (max-width: 768px) {
            .stat-cards {
                flex-direction: column;
            }
            .stat-card {
                width: 100%;
                margin-bottom: 15px;
            }
            .chart-row {
                flex-direction: column;
            }
            .chart-col {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
<div class="px-5 mx-3">


<div class="container-fluid py-4 ">
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-left">
            <h2 class="page-title m-0 mb-2 mb-md-0">Performance <span>Report</span></h2>
            <form method="GET" action="" class="d-flex flex-column flex-sm-row align-items-center gap-2">
                <div class="input-group input-group-sm mb-2 mb-sm-0 mr-sm-2">
                    <span class="input-group-text">From</span>
                    <input type="date" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>" class="form-control">
                </div>
                <div class="input-group input-group-sm mb-2 mb-sm-0 mr-sm-2">
                    <span class="input-group-text">To</span>
                    <input type="date" id="date_to" name="date_to" value="<?php echo $dateTo; ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>
        </div>
    </div>
</div>

    <!-- Stat Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="row stat-cards">
                <!-- Total Sales Card -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card-stat" style="background-color: #FFE2E5;">
                        <div class="stat-icon" style="background-color: #FA5A7D;">
                            <i class="fas fa-dollar-sign text-white"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalSalesToday, 2); ?></div>
                        <div class="stat-label">Total Sales</div>
                        <div class="stat-change" style="color: <?php echo ($salesPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                            <?php echo ($salesPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($salesPercentageDifference), 2); ?>% from yesterday
                        </div>
                    </div>
                </div>

                <!-- Total Orders Card -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card-stat" style="background-color: #FFF4DE;">
                        <div class="stat-icon" style="background-color: #FF947A;">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalOrdersToday; ?></div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-change" style="color: <?php echo ($ordersPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                            <?php echo ($ordersPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($ordersPercentageDifference), 2); ?>% from yesterday
                        </div>
                    </div>
                </div>

                <!-- Products Sold Card -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card-stat" style="background-color: #DCFCE7;">
                        <div class="stat-icon" style="background-color: #3CD856;">
                            <i class="fas fa-box text-white"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalProductsSoldToday; ?></div>
                        <div class="stat-label">Products Sold</div>
                        <div class="stat-change" style="color: <?php echo ($productsSoldPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                            <?php echo ($productsSoldPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($productsSoldPercentageDifference), 2); ?>% from yesterday
                        </div>
                    </div>
                </div>

                <!-- New Customers Card -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card-stat" style="background-color: #F3E8FF;">
                        <div class="stat-icon" style="background-color: #BF83FF;">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div class="stat-value"><?php echo $newCustomersToday; ?></div>
                        <div class="stat-label">New Customers</div>
                        <div class="stat-change" style="color: <?php echo ($newCustomersPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                            <?php echo ($newCustomersPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($newCustomersPercentageDifference), 2); ?>% from yesterday
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Charts Row 1 -->
    <div class="row mb-4 chart-row">
        <!-- Revenue Chart -->
        <div class="col-lg-4 col-md-6 mb-3 chart-col">
            <div class="chart-container h-100">
                <h5 class="text-dark">Revenue</h5>
                <p class="text-dark fs-3">Total <?php echo number_format($currentRevenue, 2); ?></p>
                <div style="color: <?php echo ($percentageChange >= 0) ? 'green' : 'red'; ?>;">
                    <?php echo ($percentageChange >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($percentageChange), 2); ?>% <span class="text-muted">vs last month</span>
                </div>
                <div class="chart-wrapper" style="height: 200px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Food and Drinks Line Chart -->
        <div class="col-lg-4 col-md-6 mb-3 chart-col">
            <div class="chart-container h-100">
                <h5 class="mb-2">Food and Drinks</h5>
                <?php if (count($labelsFoodDrink) > 0): ?>
                    <div class="chart-wrapper" style="height: 200px;">
                        <canvas id="foodDrinksChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <div class="d-flex align-items-center">
                            <span style="display: inline-block; width: 10px; height: 10px; background-color: #EF4444; margin-right: 5px;"></span>
                            <small>Food</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span style="display: inline-block; width: 10px; height: 10px; background-color: #A700FF; margin-right: 5px;"></span>
                            <small>Drinks</small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted my-5">No data available</div>
                <?php endif; ?>
            </div>
        </div>

       <!-- Orders Chart -->
<div class="col-lg-4 col-md-6 mb-3 chart-col">
    <div class="chart-container h-100">
        <h5 class="mb-2">Orders</h5>
        <p class="fs-3 fw-bold"><?php echo $orderCount; ?></p>
        <div style="color: <?php echo ($percentageChange >= 0) ? 'green' : 'red'; ?>;">
            <?php echo ($percentageChange >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($percentageChange), 2); ?>% <span class="text-muted">vs previous period</span>
        </div>
        <div class="chart-wrapper" style="height: 200px;">
            <canvas id="orderComparisonChart"></canvas>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-2">
            <div class="d-flex align-items-center">
                <span style="display: inline-block; width: 12px; height: 12px; background-color: #FF902B; border-radius: 50%; margin-right: 5px;"></span>
                <small>Selected Period</small>
            </div>
            <div class="d-flex align-items-center">
                <span style="display: inline-block; width: 12px; height: 12px; background-color: #D8D9DB; border-radius: 50%; margin-right: 5px;"></span>
                <small>Previous Period</small>
            </div>
        </div>
    </div>
</div>

    <!-- Main Charts Row 2 -->
    <div class="row mb-4 chart-row">
        <!-- Most Ordered Food -->
        <div class="col-lg-4 col-md-6 mb-3 chart-col">
            <div class="chart-container h-100">
                <h5 class="mb-3">Most Ordered Food</h5>
                <p class="text-muted small mb-3">Best seller and crowd favorite!</p>
                <?php if (count($topFoodItems) > 0): ?>
                    <?php foreach ($topFoodItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="text-muted">₱<?php echo htmlspecialchars($item['price']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted my-5">No data available</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Time Distribution -->
        <div class="col-lg-4 col-md-6 mb-3 chart-col">
            <div class="chart-container h-100">
                <h5 class="mb-3">Order Time Distribution</h5>
                <?php if (count($timeLabels) > 0): ?>
                    <div class="chart-wrapper" style="height: 200px;">
                        <canvas id="timeChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-3 mt-3" id="timeChartLegend"></div>
                <?php else: ?>
                    <div class="text-center text-muted my-5">No data available</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reservation/Order Type -->
        <div class="col-lg-4 col-md-6 mb-3 chart-col">
            <div class="chart-container h-100">
                <h5 class="mb-3">Reservation/Order Type</h5>
                <?php if ($totalCount == 0): ?>
                    <div class="text-center text-muted my-5">No data available</div>
                <?php else: ?>
                    <div class="chart-wrapper" style="height: 200px;">
                        <canvas id="reservationChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-3 mt-3" id="chartLegend"></div>
                <?php endif; ?>
            </div>
        </div>
                </div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
  // Frontend: Chart.js Line Chart for Order Comparison
const orderComparisonCtx = document.getElementById('orderComparisonChart').getContext('2d');
const orderComparisonChart = new Chart(orderComparisonCtx, {
    type: 'bar',
    data: {
        labels: ['Previous Period', 'Selected Period'],
        datasets: [{
            label: 'Number of Orders',
            data: [<?php echo $prevOrderCount; ?>, <?php echo $orderCount; ?>],
            backgroundColor: ['#D8D9DB', '#FF902B'],
            borderColor: ['#D8D9DB', '#FF902B'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function(context) {
                        return context.raw + ' orders';
                    }
                }
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

    // Frontend: Chart.js Donut Chart for Reservation Types
    const ctx = document.getElementById('reservationChart').getContext('2d');
    const reservationChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Reservation Types',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: <?php echo json_encode($colors); ?>,
                borderColor: <?php echo json_encode($colors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw.toFixed(2) + '%';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Custom Legend for Reservation Types
    const chartLegend = document.getElementById('chartLegend');
    const legendItems = <?php echo json_encode($labels); ?>;
    const legendColors = <?php echo json_encode($colors); ?>;

    legendItems.forEach((item, index) => {
        const legendItem = document.createElement('div');
        legendItem.className = 'd-flex align-items-center';
        legendItem.innerHTML = `
            <span style="display: inline-block; width: 12px; height: 12px; background-color: ${legendColors[index]}; border-radius: 50%; margin-right: 5px;"></span>
            <small>${item} (${reservationChart.data.datasets[0].data[index].toFixed(2)}%)</small>
        `;
        chartLegend.appendChild(legendItem);
    });

    // Frontend: Chart.js Donut Chart for Time Periods
    const timeCtx = document.getElementById('timeChart').getContext('2d');
    const timeChart = new Chart(timeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($timeLabels); ?>,
            datasets: [{
                label: 'Order Time Periods',
                data: <?php echo json_encode($timeData); ?>,
                backgroundColor: <?php echo json_encode($timeColors); ?>,
                borderColor: <?php echo json_encode($timeColors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw.toFixed(2) + '%';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Custom Legend for Time Periods
    const timeChartLegend = document.getElementById('timeChartLegend');
    const timeLegendItems = <?php echo json_encode($timeLabels); ?>;
    const timeLegendColors = <?php echo json_encode($timeColors); ?>;

    timeLegendItems.forEach((item, index) => {
        const legendItem = document.createElement('div');
        legendItem.className = 'd-flex align-items-center';
        legendItem.innerHTML = `
            <span style="display: inline-block; width: 12px; height: 12px; background-color: ${timeLegendColors[index]}; border-radius: 50%; margin-right: 5px;"></span>
            <small>${item} (${timeChart.data.datasets[0].data[index].toFixed(2)}%)</small>
        `;
        timeChartLegend.appendChild(legendItem);
    });

    // Frontend: Chart.js Bar Chart for Total Revenue
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Last Month', 'Current Month'],
            datasets: [{
                label: 'Total Revenue',
                data: [<?php echo $previousRevenue; ?>, <?php echo $currentRevenue; ?>],
                backgroundColor: ['#D8D9DB', '#FF902B'],
                borderColor: ['#D8D9DB', '#FF902B'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.raw.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Frontend: Chart.js Line Chart for Food and Drinks
    const foodDrinksCtx = document.getElementById('foodDrinksChart').getContext('2d');
    const foodDrinksChart = new Chart(foodDrinksCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labelsFoodDrink); ?>,
            datasets: [{
                label: 'Food',
                data: <?php echo json_encode($foodCounts); ?>,
                borderColor: '#EF4444',
                backgroundColor: '#EF4444',
                fill: false,
                pointRadius: 3,
                pointHoverRadius: 7
            },
            {
                label: 'Drinks',
                data: <?php echo json_encode($drinkCounts); ?>,
                borderColor: '#A700FF',
                backgroundColor: '#A700FF',
                fill: false,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>