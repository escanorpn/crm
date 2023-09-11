<?php
// Include your database configuration here
$servername = "your_servername";
$username = "your_username";
$password = "your_password";
$database = "your_database";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $token = $data['Token'];
    $phoneNumberId = $data['Phone-Number-ID'];
    $version = $data['Version'];
    $status = $data['status'];
    $phoneNumber = $data['phoneNumber'];
    $verificationCode = $data['verificationCode'];
    $phoneNumber = $data['PhoneNumber'];

    // You can use prepared statements to prevent SQL injection here
    $stmt = $conn->prepare("INSERT INTO whatsapp (Token, Phone_Number_ID, Version, status, phone_number, verification_code, PhoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE Token=?, Version=?, status=?, phone_number=?, verification_code=?, PhoneNumber=?");

    $stmt->bind_param("ssssssssssss", $token, $phoneNumberId, $version, $status, $phoneNumber, $verificationCode, $phoneNumber, $token, $version, $status, $phoneNumber, $verificationCode, $phoneNumber);

    if ($stmt->execute()) {
        http_response_code(200);
        echo "Validation data saved successfully";
    } else {
        http_response_code(500);
        echo "Error saving validation data";
    }

    $stmt->close();
}

// Handle GET request to retrieve data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Assuming you want to retrieve data based on some criteria (e.g., selectedAppID)
    $selectedAppID = $_GET['selectedAppID'];

    // You can use prepared statements to prevent SQL injection here
    $stmt = $conn->prepare("SELECT * FROM whatsapp WHERE selectedAppID = ?");
    $stmt->bind_param("s", $selectedAppID);

    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($data);

    $stmt->close();
}

// Handle other CRUD operations as needed (e.g., DELETE)

$conn->close();
?>
