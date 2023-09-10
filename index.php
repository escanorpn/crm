<?php
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


// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Implement your POST request handling logic here
    // Example: $data = json_decode(file_get_contents('php://input'), true);
    // Implement your response logic as needed
    http_response_code(200);
    echo "POST request received";
    exit();
}

// Handle unsupported request methods
http_response_code(405);
echo "Method Not Allowed";
?>
