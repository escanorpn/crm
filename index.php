<?php
require_once 'cors.php';  
require_once 'db.php';  
$response = array();  

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Your POST request handling code here
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $VERIFY_TOKEN = 'abcd';
    $mode = isset($_GET['hub_mode']) ? $_GET['hub_mode'] : null;
    $challenge = isset($_GET['hub_challenge']) ? $_GET['hub_challenge'] : null;
    $verifyToken = isset($_GET['hub_verify_token']) ? $_GET['hub_verify_token'] : null;

    if ($mode === 'subscribe' && $verifyToken === $VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
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
