<?php
// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Your existing form processing code from change_profile.php
    // Make sure to redirect after processing:
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}
?>