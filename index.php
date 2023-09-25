<?php
require_once 'cors.php';  
require_once 'db.php';  
require_once 'helper.php'; 
require_once 'config.php'; 
$response = array();  

// Handle POST request to create or update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON data from the request body
    // Extract data fields from the JSON data
   
 
    $entry = $data['entry'][0];
    $firstChange = $entry['changes'][0];
    $value = $firstChange['value'];

    

    
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

                        $selectedAppID = retrieveAppId($recipientWAID, $conn);

                        // Check if the retrieved selectedAppID is not null
                        echo json_encode(array('selectedAppID' => $selectedAppID));    
                    // Check if the retrieved selectedAppID is not null
                    if ($selectedAppID == null) {
                        // Call the retrieveAppdata function to get the appId
                        $appData = retrieveAppdata($messages);
                        
                        echo json_encode(array('appId' => $messages,'selectedAppID' => $selectedAppID,'appData' => $appData));

                        if (!$appData['success']) {
                            // If appData is not available, send a response to request the business code
                            $responseMessage = "Hi {$profileName}, Please provide a valid organization Code";
                            sendBotResponse($Token, $responseMessage, $recipientWAID);
                        } else {
                              // If appData is not available, send a response to request the business code
                            
                            // Insert appId as selectedAppID, $recipientWAID as rno, status=1, organizationName=appData['name'] into bots_data
                            $appId = $selectedAppID; // Replace with the actual key in $appData
                            $organizationName = $appData['appName']; // Replace with the actual key in $appData

                            $responseMessage = "I'm,an AI assistant from {$organizationName}, how may i be of assistance?";
                            sendBotResponse($Token, $responseMessage, $recipientWAID);
                            // if (preg_match('/^-/', $string)) {
                            //     echo "String starts with a special character.";
                            // } else {
                            //     echo "String starts with a normal word.";
                            // }
                            // Prepare and execute the SQL query to insert the data
                            $updateSql = "UPDATE bots_data
                                SET status = 1,selectedAppID = '$messages', organizationName = '$organizationName'
                                WHERE rno = '$recipientWAID'";                 
                          echo $updateSql;

                            if ($conn->query($updateSql) === TRUE) {
                                // Data inserted successfully
                                // You can send a confirmation response if needed
                            } else {
                                // Handle the case where data insertion failed
                                // You can send an error response or log the error
                                echo "Error updating data into bots_data: " . $conn->error;
                            }
                        }
                        return;
                    }
                        
                        // Query to retrieve data from the database
                        $sql = "SELECT verificationCode, status, Token FROM whatsapp WHERE selectedAppID = '$selectedAppID'";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $storedCode = $row['verificationCode'];
                                $status = $row['status'];
                                $Token = $row['Token'];
                            }
                            
                            // echo json_encode(array('sendBotResponsemessage' => $Token.$sql));

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
                                // echo json_encode(array('handleCommonGreetings' => $selectedAppID));
                            }
                             if($messages!='1'&&$messages!='2'&&$messages!='3') {
                          // SQL query to update or insert a record
                          $query = "INSERT INTO webhook_data (selectedAppID, recipientWAID, messages)
                          VALUES ('$selectedAppID', '$recipientWAID', '$messages')
                          ON DUPLICATE KEY UPDATE messages = IF(messages IS NULL, '$messages', CONCAT(messages, ', ', '$messages'))";

                          // Execute the SQL query
                          if ($conn->query($query) === TRUE) {
                        //   echo "Record updated/inserted successfully.";
                          } else {
                          echo "Error updating/inserting record: " . $conn->error;
                          }
                             }                 


                        } else {
                            // Handle case where no data is found in the database
                    
                            // Send response to the client
                            $updateSql = "UPDATE bots_data SET selectedAppID = ''WHERE rno = '$recipientWAID'";                 
                          echo $updateSql;

                            if ($conn->query($updateSql) === TRUE) {
                            } else {
                                echo "Error updating data into bots_data: " . $conn->error;
                            }
                            $responseMessage = "That's weird, the organization was not found in my database \n *Enter* 5: to Choose different organization";
                    
                            sendBotResponse($Token, $responseMessage, $recipientWAID);
                            echo json_encode(array('message' => 'No data found in the database.'.$sql));
                        }
                        }
                    }
                }
            }
            
        }
    }
function retrieveAppId($recipientWAID, $conn) {
    // Implement your logic to retrieve the selectedAppID
    // For example, you can query the database based on $recipientWAID
    $sql = "SELECT selectedAppID FROM bots_data WHERE rno = '$recipientWAID'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            return $row['selectedAppID'];
        }
    } else {
        // If no selectedAppID is found, you can add a new record and return its value
        $insertSql = "INSERT INTO bots_data (rno) VALUES ('$recipientWAID' )";
        
        if ($conn->query($insertSql) === TRUE) {
            return null;
        } else {
            // Handle the case where insertion fails
            return null;
        }
    }
}

    
   

    function retrieveAppdata($appId) {
        global $conn;
        
        $APPID = APPID;
        $secret = My_SECRETE; // Replace My_SECRETE with your actual secret
        $curl = curl_init();
        $APPDURL = $APPID ; // Replace with your AppDURL
    
        // Define the payload data
        $payload = [
            "appId" => $appId
        ];
    
        // Encode the payload as JSON
        $jsonData = json_encode($payload);
    
        // Define cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $APPDURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Bearer $secret"
            ),
        ));
    
        // Execute the cURL request
        $response = curl_exec($curl);
    
        // Check for cURL errors and handle the response as needed
        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
            // Handle the error as required
        } else {
            // Process the response data (e.g., JSON parsing)
            $responseData = json_decode($response, true);
            
            // You can now work with $responseData to extract the necessary information
            // For example, $responseData['some_key'] to access a specific value
            
            // Return the response data or perform further processing
            return $responseData;
        }
    
        // Close the cURL session
        curl_close($curl);
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
