<?php
require_once 'cors.php';  
require_once 'db.php';  
require_once 'helper.php';  
$response = array();  

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON data from the request body

    // Extract data fields from the JSON data
    $selectedAppID = isset($_GET['metad']) ? $_GET['metad'] : null;
 
    $entry = $data['entry'][0];
    $firstChange = $entry['changes'][0];
    $value = $firstChange['value'];

    
    // Log the request details and data to a file
    $logMessage = "Received POST request with selectedAppID : $selectedAppID \n";
    $logMessage .= "Data: " . json_encode($data) . "\n"; // Include the $data variable
    $logMessage .= "Variable selectedAppID : $selectedAppID \n"; // Include the $selectedAppID  variable
    
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

    
    if ($data['object'] === 'whatsapp_business_account' && isset($data['entry']) && count($data['entry']) > 0) {
        $entry = $data['entry'][0];

        if (isset($entry['changes']) && count($entry['changes']) > 0) {
            $firstChange = $entry['changes'][0];

            if (isset($firstChange['value'])) {
                $messagingProduct = $firstChange['value']['messaging_product'];
                $metadata = $firstChange['value']['metadata'];
                $contacts = $firstChange['value']['contacts'];

                if (isset($firstChange['value']['messages']) && count($firstChange['value']['messages']) > 0) {
                    $firstMessage = $firstChange['value']['messages'][0];

                    if (isset($firstMessage['text']) && isset($firstMessage['text']['body'])) {
                        $messages = $firstMessage['text']['body'];

                        // You now have the $messages variable available for further processing

                        // Extract information from the first contact
                        $profileName = '';
                        $recipientWAID = '';
                        if (isset($contacts) && count($contacts) > 0) {
                            $profileName = $contacts[0]['profile']['name'];
                            $recipientWAID = $contacts[0]['wa_id'];
                        }

                        // Query to retrieve data from the database
                        $sql = "SELECT verificationCode, status, Token FROM whatsapp WHERE selectedAppID = '-$selectedAppID '";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $storedCode = $row['verificationCode'];
                                $status = $row['status'];
                                $Token = $row['Token'];
                            }

                            // Handle further processing based on your logic

                            // Handle verificationCodeRef logic
                            $verificationCodeRefData = array();
                            $verificationCodeRefData['verificationCode'] = $storedCode;
                            $verificationCodeRefData['status'] = $status;
                            $verificationCodeRefData['Token'] = $Token;
                            // echo $status;
                            // Assuming you have retrieved $messages from the request data
                            if (strpos($messages, 'passCode=') !== false) {
                                $receivedCode = substr($messages, strpos($messages, 'passCode=') + 9);

                                if ($status !== 'verified') {
                                    if ($receivedCode === $storedCode) {
                                        $userData = array(
                                            'selectedAppID' => '-'.$selectedAppID ,
                                            'profileName' => $profileName
                                        );
                                        $responseMessage = "Hi {$userData['profileName']}, Processing...";
                                        sendBotResponse($Token, $responseMessage, $recipientWAID,2, $userData);
                                        // Send response to the client
                                        echo json_encode(array('message' => $responseMessage, 'status' => 2, 'userData' => $userData));
                    
                                    } else {
                                        $responseMessage = "Invalid passcode";
                    
                                        sendBotResponse($Token, $responseMessage, $recipientWAID);
                                        // Send response to the client
                                        echo json_encode(array('message' => $responseMessage));
                    
                                    }
                                } else {
                                    $responseMessage = "This account is already verified";
                    
                                    sendBotResponse($Token, $responseMessage, $recipientWAID);
                                    // Send response to the client
                                    echo json_encode(array('message' => $responseMessage));
                    
                                }

                            }else {
                                // Handle common greetings logic here
                                handleCommonGreetings($Token, $messages, $recipientWAID, $selectedAppID , $profileName);
                    
                                // Assuming you have already sent the response in handleCommonGreetings
                            }
                                               
                          // SQL query to update or insert a record
                            $query = "INSERT INTO webhook_data (selectedAppID, recipientWAID, messages)
                            VALUES ('$selectedAppID', '$recipientWAID', '$messages')
                            ON DUPLICATE KEY UPDATE messages = IF(messages IS NULL, '$messages', CONCAT(messages, ', ', '$messages'))";

                            // Execute the SQL query
                            if ($conn->query($query) === TRUE) {
                            echo "Record updated/inserted successfully.";
                            } else {
                            echo "Error updating/inserting record: " . $conn->error;
                            }


                       
                          
                    
                        } else {
                            // Handle case where no data is found in the database
                    
                            // Send response to the client
                            echo json_encode(array('message' => 'No data found in the database.'.$sql));
                        }
                        }
                    }
                }
            }
            
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
