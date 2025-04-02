<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .reservation-page {
            height: 100%;
            background-color: transparent;
        }

        .calendar-container {
    padding: 15px;
    border-radius: 3rem;
    border: 2px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow-x: auto; /* Allows scrolling if content overflows */
    background-color: white;
    width: 100%;
    min-width: 300px; /* Prevents it from being cut off */
    max-width: 100%;
    height: auto;
    display: flex;
    flex-direction: column;
    flex-wrap: wrap; /* Ensures items wrap properly */
}

        .calendar-title {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            color: white;
            background-color: orange;
            padding: 1rem;
            border-radius: 1rem;
            flex-grow: 1;
        }

        .calendar-nav {
            cursor: pointer;
            font-size: 1.5em;
            font-weight: bold;
            color: white;
            background-color: orange;
            user-select: none;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .today {
            background-color: #ffeb3b;
            border-radius: 50%;
            font-weight: bold;
        }

        .date-btn {
            border: none;
            background-color: transparent;
            color: black;
            width: 100%;
            height: 100%;
            cursor: pointer;
            outline: none;
            border-radius: 1rem;
        }
    </style>
</head>
<body class="reservation-page">

<div class="container d-flex justify-content-center align-items-center mt-5">
    <div id="calendar" class="calendar-container col-lg-8 col-md-10 col-sm-12">
        <div class="calendar-header d-flex justify-content-between align-items-center">
            <span id="prev-month" class="calendar-nav"><i class="fas fa-chevron-left"></i></span>
            <span id="calendar-title" class="fw-bold"></span>
            <span id="next-month" class="calendar-nav"><i class="fas fa-chevron-right"></i></span>
        </div>
        <table class="calendar-table table text-center">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody id="calendar-body">
                <!-- Automatic dates here -->
            </tbody>
        </table>
    </div>
</div>

<script src="../asset/js/calendar.js"></script>

</body>
</html>
