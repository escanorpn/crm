<?php
require_once 'cors.php';  
require_once 'db.php';  
require_once 'helper.php';  
$response = array();  

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON data from the request body

    // Extract data fields from the JSON data
    $metad = isset($_GET['metad']) ? $_GET['metad'] : null;
 
    $entry = $data['entry'][0];
    $firstChange = $entry['changes'][0];
    $value = $firstChange['value'];

    
    // Log the request details and data to a file
    $logMessage = "Received POST request with metad: $metad\n";
    $logMessage .= "Data: " . json_encode($data) . "\n"; // Include the $data variable
    $logMessage .= "Variable metad: $metad\n"; // Include the $metad variable
    
    // Specify the log file path
    $logFilePath = 'log.txt';
    
    // Open the log file for appending
    $logFile = fopen($logFilePath, 'a');
    
    if ($logFile) {
        // Write the log message to the file
        fwrite($logFile, $logMessage);
        
        // Close the log file
        fclose($logFile);
    } else {
        // Handle any errors that occur when opening the log file
        error_log("Error opening log file $logFilePath");
    }

    // Extract messaging product, metadata, and contacts
    $messagingProduct = $value['messaging_product'];
    $metadata = $value['metadata'];
    $contacts = $value['contacts'];

    // Extract information from the first contact
    $profileName = '';
    $recipientWAID = '';
    if ($contacts && count($contacts) > 0) {
        $profileName = $contacts[0]['profile']['name'];
        $recipientWAID = $contacts[0]['wa_id'];
    }

    // Query to retrieve data from the database (adjust this query according to your database schema)
    $sql = "SELECT verificationCode, status, Token FROM whatsapp WHERE metad = '$metad'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $storedCode = $row['verificationCode'];
            $status = $row['status'];
            $Token = $row['Token'];
        }

        // Handle further processing based on your logic

        // Handle verificationCodeRef logic
        $verificationCodeRefData = array(); // Replace with your logic to retrieve data
        $verificationCodeRefData['verificationCode'] = $storedCode;
        $verificationCodeRefData['status'] = $status;
        $verificationCodeRefData['Token'] = $Token;

        // Assuming you have retrieved $messages from the request data
        if (strpos($messages, 'passCode=') !== false) {
            $receivedCode = substr($messages, strpos($messages, 'passCode=') + 9);

            if ($status !== 'verified') {
                if ($receivedCode === $storedCode) {
                    $userData = array(
                        'metad' => $metad,
                        'profileName' => $profileName
                    );
                    $responseMessage = "Hi {$userData['profileName']}, Processing...";

                    // Send response to the client
                    echo json_encode(array('message' => $responseMessage, 'status' => 2, 'userData' => $userData));

                } else {
                    $responseMessage = "Invalid passcode";

                    // Send response to the client
                    echo json_encode(array('message' => $responseMessage));

                }
            } else {
                $responseMessage = "This account is already verified";

                // Send response to the client
                echo json_encode(array('message' => $responseMessage));

            }
        } else {
            // Handle common greetings logic here
            handleCommonGreetings($Token, $messages, $recipientWAID, $metad, $profileName);

            // Assuming you have already sent the response in handleCommonGreetings
        }

        // Log successful message to the database (you can replace this part with your MySQL logic)
        $webhookSuccessRefData = array(
            'timestamp' => date('c'),
            'message' => $messages,
            'recipientWAID' => $recipientWAID
        );

        // Send response to the client
        echo json_encode(array('message' => 'Successful message logged to the database.', 'data' => $webhookSuccessRefData));

    } else {
        // Handle case where no data is found in the database

        // Send response to the client
        echo json_encode(array('message' => 'No data found in the database.'));
    }
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
