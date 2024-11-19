$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        let username = $('#username').val();
        let password = $('#password').val();

        $.ajax({
            url: './../user/login.php',
            type: 'POST',
            data: {
                action: 'login',
                username: username,
                password: password
            },
            success: function(response) {
                let result = JSON.parse(response);
                
                if (result.success) {
                    window.location.href = "./../views/index.php"; 
                } else {
                    alert('Login failed: ' + result.message);
                }
            }
        });
    });
});
