<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reservation-page
        {
            height: 100%;
            background-color: transparent;
        }
        .calendar-container {
            padding: 15px;
            border-radius: 3rem;
            border: 2px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: white;
            width: 100%;
            height: 100%;
            margin: auto;
            display: flex;
            flex-direction: column;
        }

        .reservation-page .calendar-container {
            max-width: 70%;
            height: 25rem;
        }

        .calendar-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            display: table;
        }

        .calendar-table td, .calendar-table th {
            border: none;
            padding: .2rem;
            text-align: center;
            vertical-align: middle;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .5rem;
        }

        .calendar-title {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            color: white;
            background-color: orange;
            padding: 1rem;
            border-radius: 1rem;
            flex-grow: .5;
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

        .calendar-table tbody {
            overflow-y: auto;
            height: calc(100% - 3.5rem);
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

<div class="container mt-5">
    <div id="calendar" class="calendar-container">
        <div class="calendar-header">
            <span id="prev-month" class="calendar-nav"><</span>
            <span id="calendar-title" class="calendar-title"></span>
            <span id="next-month" class="calendar-nav">></span>
        </div>
        <table class="calendar-table">
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
                <!-- automatic dates here -->
            </tbody>
        </table>
    </div>
</div>

<script src="../asset/js/calendar.js"></script>

</body>
</html>