<?php 
include './../../connection/connection.php';

$firstName = null;
$lastName = null;
$email = null;
$contactNumber = null;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT firstname, lastname, email, contact_number FROM client WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $email, $contactNumber);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateProfile') {
    $response = ["success" => false, "message" => "", "errors" => []];

    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $contactNumber = $_POST['contactNumber'];

    // Validate inputs
    if (!preg_match('/^[a-zA-Z]+$/', $firstName)) {
        $response["errors"][] = "First name should only contain alphabetic characters.";
    }

    if (!preg_match('/^[a-zA-Z]+$/', $lastName)) {
        $response["errors"][] = "Last name should only contain alphabetic characters.";
    }

    if (!preg_match('/^\d+$/', $contactNumber)) {
        $response["errors"][] = "Contact number should contain only digits.";
    }

    if (empty($response["errors"])) {
        $sql = "UPDATE client SET firstname = ?, lastname = ?, email = ?, contact_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $firstName, $lastName, $email, $contactNumber, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response["success"] = true;
            $response["message"] = "Profile updated successfully!";
        } else {
            $response["message"] = "No changes were made.";
        }

        $stmt->close();
    }

    echo json_encode($response);
    exit;
}

$conn->close();
?>

<style>
body {
    overflow-x: hidden;
}
.modal-header {
    background-color: #FF902B;
    color: white;
}
.modal-content {
    border-radius: 10px;
}
.error-message {
    color: red;
    font-size: 0.875em;
}
</style>

<!-- Modal for changing profile -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="responseMessage" class="alert" style="display: none;"></div>
                <form id="profileForm">
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstName" id="firstName" value="<?= htmlspecialchars($firstName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="lastName" id="lastName" value="<?= htmlspecialchars($lastName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactNumber" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contactNumber" id="contactNumber" value="<?= htmlspecialchars($contactNumber); ?>" required>
                    </div>
                    <button type="button" id="updateProfileBtn" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('updateProfileBtn').addEventListener('click', function() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        formData.append('action', 'updateProfile');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById('responseMessage');
            responseMessage.style.display = 'block';
            responseMessage.textContent = data.message;

            if (data.success) {
                responseMessage.className = 'alert alert-success';
            } else {
                responseMessage.className = 'alert alert-danger';
                if (data.errors) {
                    data.errors.forEach(error => {
                        const errorElement = document.createElement('div');
                        errorElement.className = 'error-message';
                        errorElement.textContent = error;
                        responseMessage.appendChild(errorElement);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
</script>
