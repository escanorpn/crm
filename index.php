<?php
require_once 'cors.php';  
require_once 'db.php';  
$response = array();  
// set_error_handler("customError");

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
}
// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $VERIFY_TOKEN = 'abcd';
    $mode = $_GET['hub_mode'];
    $challenge = $_GET['hub_challenge'];
    $verifyToken = $_GET['hub_verify_token'];

    if ($mode === 'subscribe' && $verifyToken === $VERIFY_TOKEN) {
        echo $challenge;
        http_response_code(200);
        exit();
    } else {
        http_response_code(403);
        echo "Verification failed";
        exit();
    }
}

// Handle other CRUD operations as needed (e.g., DELETE)

$conn->close();
?>
