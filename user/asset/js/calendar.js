window.onload = function() {
    const user_id = 25;  // Your client user ID
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    const today = new Date();
    const todayDay = today.getDate();
    const todayMonth = today.getMonth();
    const todayYear = today.getFullYear();

    // Define colors for different reservation states
    const COLORS = {
        userReserved: 'purple',
        fullyBooked: 'red',
        partiallyBooked: 'green',
        available: 'white'
    };

    // Initialize selectedDate to today's date
    let selectedDate = `${todayYear}-${todayMonth + 1}-${todayDay}`;
    console.log(`Default selectedDate: ${selectedDate}`);

    // Send the selected date to the parent window on page load
    window.parent.postMessage({ selectedDate: selectedDate }, '*');

    function loadCalendar(month, year) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
    
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const calendarTitle = document.getElementById('calendar-title');
        calendarTitle.textContent = `${monthNames[month]} ${year}`;
    
        const startDay = firstDay.getDay();
        const calendarBody = document.getElementById('calendar-body');
        calendarBody.innerHTML = '';
    
        let day = 1;
    
        // Generate the calendar rows
        for (let row = 0; row < 5; row++) {
            let tr = document.createElement('tr');
    
            // Create cells for each day of the week
            for (let col = 0; col < 7; col++) {
                let td = document.createElement('td');
    
                if (row === 0 && col < startDay) {
                    tr.appendChild(td); // Empty cells before the first day
                } else if (day > daysInMonth) {
                    tr.appendChild(td); // Empty cells after the last day
                } else {
                    const btn = document.createElement('button');
                    btn.classList.add('date-btn');
                    btn.textContent = day;
                    btn.setAttribute('data-date', `${year}-${month + 1}-${day}`);
    
                    const date = new Date(year, month, day);
                    const today = new Date();
    
                    // Disable dates before today
                    if (date <= today) {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                        // btn.style.backgroundColor = '#D3D3D3';  // Optional: Change color to indicate it's disabled
                        btn.style.cursor = 'not-allowed';  // Optional: Change the cursor to indicate it's unclickable
                    }
    
                    // Fetch reservation data and color buttons
                    fetch(`../user/get_reservation_count.php?date=${year}-${month + 1}-${day}`)
                        .then(response => response.json())
                        .then(data => {
                            const reservations = data.reservations; // Reservation data
                            const res_time_count = data.res_time_count; // Total rows in res_time table
    
                            let reservationCount = reservations.length;
                            let isUserReserved = reservations.some(reservation => reservation.client_id == user_id);
    
                            // Apply color based on reservation count and max available slots (res_time_count)
                            if (isUserReserved) {
                                btn.style.backgroundColor = COLORS.userReserved;
                                btn.style.color = 'white';
                            } else if (reservationCount >= res_time_count) {
                                btn.style.backgroundColor = COLORS.fullyBooked;
                                btn.style.color = 'white';
                            } else if (reservationCount > 0 && reservationCount < res_time_count) {
                                btn.style.backgroundColor = COLORS.partiallyBooked;
                                btn.style.color = 'white';
                            } else {
                                btn.style.backgroundColor = COLORS.available;
                                btn.style.color = 'black';
                            }
                        })
                        .catch(error => console.error("Error fetching reservation count:", error));
    
                    // Highlight today's date
                    if (year === todayYear && month === todayMonth && day === todayDay) {
                        btn.classList.add('today');
                    }
    
                    td.appendChild(btn);
                    tr.appendChild(td);
                    day++;
                }
            }
            calendarBody.appendChild(tr);
        }
    }
    

    function handleDateSelection(event) {
    if (event.target && event.target.matches('button.date-btn')) {
        selectedDate = event.target.getAttribute('data-date');
        console.log(`Selected Date: ${selectedDate}`);
        window.parent.postMessage({ selectedDate: selectedDate }, '*');
        // Use AJAX to send the selected date to PHP
        $.ajax({
            url: '../../views/Reservation.php',  // The PHP script that will handle the data
            type: 'POST',
            data: {
                selectedDate: selectedDate  // Send the selected date
            },
            success: function(response) {
                // Handle the response from PHP (if needed)
                console.log('Date sent to PHP:', response);
            },
            error: function() {
                console.log('Error sending the date to PHP');
            }
        });

       
        
    }
}

    // Event listeners for calendar navigation and date selection
    document.getElementById('calendar-body').addEventListener('click', handleDateSelection);

    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        loadCalendar(currentMonth, currentYear);
    });

    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        loadCalendar(currentMonth, currentYear);
    });

    // Initial calendar load with today's date
    loadCalendar(currentMonth, currentYear);
    console.log(`Today's date: ${todayYear}-${todayMonth + 1}-${todayDay}`);
};

