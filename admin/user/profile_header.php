<?php
include './../../connection/connection.php';
$clientId = $_SESSION['user_id'] ?? 0;




$stmt = $conn->prepare("SELECT firstname, lastname, email, profile_picture FROM client WHERE id = ?");
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    $client = [
        'firstname' => 'Guest',
        'lastname' => 'User',
        'email' => 'no-email@example.com',
        'profile_picture' => ''
    ];
}


$clientFullName = htmlspecialchars($client['firstname'] . ' ' . $client['lastname']);
$email = htmlspecialchars($client['email']);
$clientProfilePicture = htmlspecialchars($client['profile_picture']);

$profileImagePath = '';
if (!empty($clientProfilePicture)) {
    $potentialPath = 'uploads/profile_pictures/' . $clientProfilePicture;
    if (file_exists($potentialPath)) {
        $profileImagePath = $potentialPath;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Header</title>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Poppins Font -->
   
    <style>
    :root {
        --orange: #FF902B;
    }
    
    * {
        font-family: 'Poppins', sans-serif;
        box-sizing: border-box;
    }
    
    .profile-container {
        position: relative;
        margin-bottom: clamp(80px, 8vw, 100px);
    }
    
    .profile-banner {
        background-image: url('./../asset/img/sched-reservation/sched-banner.png');
        background-size: cover;
        background-position: center;
        height: clamp(220px, 30vw, 300px);
        border-radius: 8px;
        position: relative;
        overflow: visible;
        padding-bottom: clamp(60px, 8vw, 80px);
    }
    
    .profile-img-container {
        position: absolute;
        bottom: calc(-1 * clamp(45px, 4.5vw, 90px));
        left: clamp(15px, 3vw, 40px);
        z-index: 10;
    }
    
    .profile-img {
        width: clamp(100px, 12vw, 180px);
        height: clamp(100px, 12vw, 180px);
        object-fit: cover;
        border-radius: 50%;
        border: clamp(3px, 0.4vw, 5px) solid white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        display: block;
    }
    
    .profile-fallback {
        width: clamp(100px, 12vw, 180px);
        height: clamp(100px, 12vw, 180px);
        border-radius: 50%;
        border: clamp(3px, 0.4vw, 5px) solid white;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #6c757d;
    }
    
    .profile-fallback i {
        font-size: clamp(2rem, 3vw, 3rem);
    }
    
    .text-content {
        position: absolute;
        bottom: clamp(20px, 3vw, 30px);
        left: clamp(140px, 20vw, 240px);
        width: calc(100% - clamp(160px, 23vw, 280px));
    }
    
    .client-name {
        color: #FF902B;
        font-size: clamp(1.4rem, 2.2vw, 2rem);
        font-weight: bold;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        margin-bottom: 0;
        line-height: 1.2;
        word-break: break-word;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    .email-container {
        position: absolute;
        bottom: clamp(-40px, -3vw, -50px);
        left: clamp(140px, 20vw, 240px);
        width: calc(100% - clamp(160px, 23vw, 280px));
    }
    
    .client-email {
        color: #000;
        font-size: clamp(0.9rem, 1.3vw, 1.2rem);
        margin-bottom: 0;
        word-break: break-all;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    /* Mobile layout adjustment */
    @media (max-width: 768px) {
        .client-name {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }
        
        .client-email {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }
    }
    
    @media (max-width: 576px) {
        .profile-banner {
            padding-bottom: clamp(80px, 12vw, 100px);
        }
        
        .profile-img-container {
            left: clamp(15px, 5vw, 30px);
            bottom: calc(-1 * clamp(40px, 6vw, 50px));
        }
        
        .text-content {
            left: clamp(140px, 20vw, 240px);
            width: calc(100% - clamp(160px, 23vw, 280px));
            bottom: clamp(20px, 3vw, 30px);
        }
        
        .email-container {
            left: clamp(140px, 20vw, 240px);
            width: calc(100% - clamp(160px, 23vw, 280px));
            bottom: clamp(-40px, -3vw, -50px);
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <!-- Banner with profile picture half-overlapping -->
            <div class="profile-banner">
                <!-- Profile Picture -->
                <div class="profile-img-container">
                    <?php if (!empty($profileImagePath)): ?>
                        <img src="<?php echo $profileImagePath; ?>" alt="<?php echo $clientFullName; ?>" class="profile-img">
                    <?php else: ?>
                        <div class="profile-fallback">
                            <i class="fas fa-user text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Name (inside banner) -->
                <div class="text-content">
                    <h1 class="client-name"><?php echo $clientFullName; ?></h1>
                </div>
                
                <!-- Email (outside banner) -->
                <div class="email-container">
                    <p class="client-email"><?php echo $email; ?></p>
                </div>
            </div>
        </div>
    </div>
    

</body>
</html>