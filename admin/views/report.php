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

// Fetch data from `menu1`, `order_items`, and `orders` tables
$query = "
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') AS month,
        m.type, 
        COUNT(oi.item_id) AS count
    FROM 
        menu1 m
    JOIN 
        order_items oi ON m.id = oi.item_id
    JOIN 
        orders o ON oi.order_id = o.order_id
    WHERE 
        o.status = 'rate us'
        AND o.created_at BETWEEN ? AND ?
    GROUP BY 
        month, m.type
    ORDER BY 
        month;
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
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

// Prepare data for the chart
$foodCounts = [];
$drinkCounts = [];
foreach ($labelsFoodDrink as $month) {
    $foodCounts[] = $foodData[$month] ?? 0;
    $drinkCounts[] = $drinkData[$month] ?? 0;
}

// Initialize date range variables
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default: Start of the current month
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Default: End of the current month

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
        AND m.type = 'food'
    GROUP BY 
        oi.item_id
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

//orders
// 
// Fetch data for the last 6 days
$last6DaysCount = 0;
for ($i = 1; $i <= 6; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = "SELECT COUNT(*) as count FROM orders 
              WHERE status = 'rate us' 
              AND DATE(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $last6DaysCount += $result->fetch_assoc()['count'];
    $stmt->close();
}

// Fetch data for last week (7 days ago)
$lastWeekDate = date('Y-m-d', strtotime('-1 week'));
$lastWeekQuery = "SELECT COUNT(*) as count FROM orders 
                  WHERE status = 'rate us' 
                  AND DATE(created_at) = ?";
$stmtLastWeek = $conn->prepare($lastWeekQuery);
$stmtLastWeek->bind_param("s", $lastWeekDate);
$stmtLastWeek->execute();
$lastWeekResult = $stmtLastWeek->get_result();
$lastWeekCount = $lastWeekResult->fetch_assoc()['count'];
$stmtLastWeek->close();

// Calculate percentage change compared to last week
$percentageChange = 0;
if ($lastWeekCount > 0) {
    $percentageChange = (($last6DaysCount - $lastWeekCount) / $lastWeekCount) * 100;
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
    <title>Reservation, Time, Revenue, and Food/Drinks Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <style>
        body {
         
            display: flex;
            flex-direction: column;
        
            font-family: 'Poppins', sans-serif;
        }
        .chart-container {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

      
        .chart-container {
            width: 100%;
            max-width: 500px;
            margin: auto;
        }
      
        .card {
            height: 100%;
        }
        .row-equal-height {
            display: flex;
            flex-wrap: wrap;
        }
        .row-equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }

        .chart-container {
    width: 100%; /* Ensure full width */
    height: 400px; /* Set a fixed height or use min-height */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
   
    </style>
</head>


<body>
<div class="container-fluid d-flex flex-column p-5">
<h2 class="m-4" style="color: black;">
    Performance <span style="border-bottom: 3px solid #FF902B;" >Report</span>
</h2>
    <!-- Date Filter at Top Right -->
    <div class="d-flex justify-content-end mb-4">
    <form method="GET" action="" class="d-flex align-items-center gap-3 p-3 rounded ">
        <div class="d-flex align-items-center gap-2">
            <label for="date_from" class="form-label mb-0">From:</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>" required class="form-control form-control-sm">
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="date_to" class="form-label mb-0">To:</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo $dateTo; ?>" required class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
    </form>
</div>
    <!-- Display Selected Date Range -->
    <!-- <div class="date-range mb-4 text-center">
        Showing data from <strong><?php echo $dateFrom; ?></strong> to <strong><?php echo $dateTo; ?></strong> -->
    <!-- </div> -->

 <!-- Section 1: Cards -->
<div class="row mb-4 ">
    <div class="col-12">
        <div class="d-flex flex-row gap-3 px-5 " >
            <!-- Total Sales Card -->
            <div class="card p-3" style="width: 200px; background-color: #FFE2E5; outline: none; border: none; box-shadow: none;">
                <div class="symbol mb-2" style="width: 40px; height: 40px; background-color: #FA5A7D; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-dollar-sign text-white"></i>
                </div>
                <div class="total fs-4 fw-bold"><?php echo number_format($totalSalesToday, 2); ?></div>
                <div class="label text-muted">Total Sales</div>
                <div class="percentage fs-6" style="color: <?php echo ($salesPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                    <?php echo ($salesPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($salesPercentageDifference), 2); ?>% from yesterday
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="card p-3" style="width: 200px; background-color: #FFF4DE; outline: none; border: none; box-shadow: none;">
                <div class="symbol mb-2" style="width: 40px; height: 40px; background-color: #FF947A; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shopping-cart text-white"></i>
                </div>
                <div class="total fs-4 fw-bold"><?php echo $totalOrdersToday; ?></div>
                <div class="label text-muted">Total Orders</div>
                <div class="percentage fs-6" style="color: <?php echo ($ordersPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                    <?php echo ($ordersPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($ordersPercentageDifference), 2); ?>% from yesterday
                </div>
            </div>

            <!-- Products Sold Card -->
            <div class="card p-3"style="width: 200px; background-color: #DCFCE7; outline: none; border: none; box-shadow: none;">
                <div class="symbol mb-2" style="width: 40px; height: 40px; background-color: #3CD856; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-box text-white"></i>
                </div>
                <div class="total fs-4 fw-bold"><?php echo $totalProductsSoldToday; ?></div>
                <div class="label text-muted">Products Sold</div>
                <div class="percentage fs-6" style="color: <?php echo ($productsSoldPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                    <?php echo ($productsSoldPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($productsSoldPercentageDifference), 2); ?>% from yesterday
                </div>
            </div>

            <!-- New Customers Card -->
            <div class="card p-3" style="width: 200px; background-color: #F3E8FF; outline: none; border: none; box-shadow: none;">
                <div class="symbol mb-2" style="width: 40px; height: 40px; background-color: #BF83FF; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="total fs-4 fw-bold"><?php echo $newCustomersToday; ?></div>
                <div class="label text-muted">New Customers</div>
                <div class="percentage fs-6" style="color: <?php echo ($newCustomersPercentageDifference >= 0) ? '#4079ED' : 'red'; ?>;">
                    <?php echo ($newCustomersPercentageDifference >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($newCustomersPercentageDifference), 2); ?>% from yesterday
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Section 2: Revenue, Food and Drink, Orders -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-row gap-3">
    <!-- Revenue Chart -->
<div class="chart-container border border-2 flex-fill p-3" style="min-width: 0;">
    <h5 class="text-dark">Revenue</h5>
    <p class="text-dark fs-3">Total <?php echo number_format($currentRevenue, 2); ?></p>
    <div style="color: <?php echo ($percentageChange >= 0) ? 'green' : 'red'; ?>;">
        <?php echo ($percentageChange >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($percentageChange), 2); ?>% <span class="text-muted">vs last month</span>
    </div>
    <canvas id="revenueChart"></canvas>
</div>

                <!-- Food and Drinks Line Chart -->
                <div class="chart-container border border-2 flex-fill p-3" style="min-width: 0;">
    <h2 class="p-1 fs-6">Food and Drinks Line Chart</h2>
    <?php if (count($labelsFoodDrink) > 0): ?>
        <canvas id="foodDrinksChart"></canvas>
        <!-- Custom Legend Below the Chart -->
        <div class="d-flex justify-content-center gap-3 mt-3 p-2">
            <div>
                <span style="display: inline-block; width: 10px; height: 10px; background-color: #EF4444; "></span>
                Food
            </div>
            <div>
                <span style="display: inline-block; width: 10px; height: 10px; background-color: #A700FF; "></span>
                Drinks
            </div>
        </div>
    <?php else: ?>
        <div class="text-center text-muted">No data available</div>
    <?php endif; ?>
</div>
                <!-- Orders Chart -->
                <div class="chart-container border border-2 flex-fill p-3" style="min-width: 0;">
                    <h2 class="fs-6">Order</h2>
                    <p class="fs-4 bold"><?php echo $last6DaysCount + $lastWeekCount; ?></p>
                    <div class="" style="color: <?php echo ($percentageChange >= 0) ? 'green' : 'red'; ?>;">
                        <?php echo ($percentageChange >= 0) ? '▲' : '▼'; ?> <?php echo number_format(abs($percentageChange), 2); ?>% vs  <span class="text-muted">last week</span>
                    </div>
                    <canvas id="orderComparisonChart"></canvas>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <div>
                            <span style="display: inline-block; width: 12px; height: 12px; background-color: #FF902B; border-radius: 50%;"></span>
                            Last 6 Days
                        </div>
                        <div>
                            <span style="display: inline-block; width: 12px; height: 12px; background-color: #D8D9DB; border-radius: 50%;"></span>
                            Last Week
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Most Ordered Food and Donut Charts -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-row gap-3">
                <!-- Most Ordered Food -->
           
<div class="chart-container border border-2 flex-fill p-3" style="min-width: 0;">
    <h2 class="fs-6 mb-2 p-1">Most Ordered Food<br>
        <span class="fs-7 text-muted">Best seller and crowd favorite!</span>
    </h2>
    <div>
        <?php if (count($topFoodItems) > 0): ?>
            <?php foreach ($topFoodItems as $item): ?>
                <div class="d-flex justify-content-between align-items-center text-center">
                    <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                    <span class="text-muted"><?php echo htmlspecialchars($item['price']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted">No data available</div>
        <?php endif; ?>
    </div>
</div>
             <!-- Order Time Distribution -->
<div class="chart-container border border-2 flex-fill p-3" style="min-width: 0;">
    <h2 class=" p-1 fs-6 ">Order Time Distribution</h2>
    <?php if (count($timeLabels) > 0): ?>
        <div class="d-flex justify-content-center" style="height: 15rem; width: 100%;"> 
            <canvas id="timeChart"  class="w-100 h-100"></canvas>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-3 p-2" id="timeChartLegend"></div>
    <?php else: ?>
        <div class="text-center text-muted">No data available</div>
    <?php endif; ?>
</div>

 




<div class="chart-container bordborder er-2 flex-fill p-3" style="min-width: 0;">
    
        <h2 class=" p-1 fs-6">Reservation/Order Type</h2>
        <?php if ($totalCount == 0): ?>
            <div class="text-center text-muted bg-light p-2">No data available</div>
        <?php else: ?>
            <div class="d-flex justify-content-center" style="height: 15rem; width: 100%;"> 
                <canvas id="reservationChart" class="w-100 h-100"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-3 p-2" id="chartLegend"></div>
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
            type: 'line',
            data: {
                labels: ['Last 6 Days', 'Last Week'],
                datasets: [{
                    label: 'Number of Orders',
                    data: [<?php echo $last6DaysCount; ?>, <?php echo $lastWeekCount; ?>],
                    borderColor: ['#FF902B', '#D8D9DB'],
                    backgroundColor: ['#FF902B', '#D8D9DB'],
                    fill: false,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        enabled: false
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
            legendItem.innerHTML = `
                <span style="display: inline-block; width: 12px; height: 12px; background-color: ${legendColors[index]}; border-radius: 50%;"></span>
                ${item} (${reservationChart.data.datasets[0].data[index].toFixed(2)}%)
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
            legendItem.innerHTML = `
                <span style="display: inline-block; width: 12px; height: 12px; background-color: ${timeLegendColors[index]}; border-radius: 50%;"></span>
                ${item} (${timeChart.data.datasets[0].data[index].toFixed(2)}%)
            `;
            timeChartLegend.appendChild(legendItem);
        });

       // Frontend: Chart.js Bar Chart for Total Revenue
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Last Month', 'Current Month'], // Labels for the bars
            datasets: [{
                label: 'Total Revenue',
                data: [<?php echo $previousRevenue; ?>, <?php echo $currentRevenue; ?>], // Data for last month and current month
                backgroundColor: ['#D8D9DB', '#FF902B'], // Colors for the bars
                borderColor: ['#D8D9DB', '#FF902B'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false, // Hide the legend
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'P' + context.raw.toFixed(2); // Display revenue in tooltip
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true // Start the y-axis from zero
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
    plugins: {
        legend: {
            display: false // Disable the legend (labels)
        },
        tooltip: {
            enabled: false // Disable tooltips
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