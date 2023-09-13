<?php
require_once 'cors.php';  
require_once 'db.php';  

function handleCommonGreetings($Token, $messageText, $recipientWAID, $selectedAppID , $profileName) {
    $commonGreetings = ['hi', 'hello', 'sasa']; // Add more common greetings as needed

    if (in_array(strtolower($messageText), $commonGreetings)) {
        $responseMessage = "Hi $profileName, how can I help you?";
        sendBotResponse($Token, $responseMessage, $recipientWAID);
    } else {
        handleMenuOptions($Token, $messageText, $recipientWAID, $selectedAppID , $profileName);
   
    }
}
function handleMenuOptions($Token, $messageText, $recipientWAID, $selectedAppID, $profileName) {
    // Define responses for common greetings and questions
    $commonResponses = [
        'hi' => "Hi $profileName, how can I help you?",
        'hello' => "Hello $profileName! How can I assist you today?",
        'how are you' => "I'm just a bot, but I'm here to help. What can I assist you with?",
        // Add more common greetings and responses as needed
    ];

    // Check if the message is a common greeting or question
    $messageTextLower = strtolower($messageText);
    if (isset($commonResponses[$messageTextLower])) {
        $responseMessage = $commonResponses[$messageTextLower];
        sendBotResponse($Token, $responseMessage, $recipientWAID);
        return; // Exit the function after sending the response
    }

    // Handle other menu options
    if ($messageText === '1') {
        // Query to retrieve messages from the database (adjust this query according to your database schema)
        $sql = "SELECT messages FROM webhook_data WHERE selectedAppID = '$selectedAppID' AND recipientWAID = '$recipientWAID' ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $messages = $row['messages'];
     

            // Process the messages as needed
            handleTicketCreation($Token, $messages, $recipientWAID, $selectedAppID, $profileName);
            createTicket($recipientWAID, $profileName,$messages);
            // Update the last message in the database
            $lastMessage = $messageText; // Store the current message as the last message
            $updateSql = "UPDATE webhook_data SET messages = '$lastMessage' WHERE selectedAppID = '$selectedAppID' AND recipientWAID = '$recipientWAID'";
            $conn->query($updateSql);
        } else {
            echo 'No messages found for the specified recipientWAID.';
            $responseMessage="I din't understand, what can i help you with?";
            sendBotResponse($Token, $responseMessage, $recipientWAID);
        }
    } elseif ($messageText === '2') {
        $responseMessage = "Okay, please state your query:";
        sendBotResponse($Token, $responseMessage, $recipientWAID);
    } elseif ($messageText === '3') {
        $responseMessage = "Okay, thank you and have a nice day!";
        sendBotResponse($Token, $responseMessage, $recipientWAID);
    } else {
        $responseMessage = "Would you like me to log the issue\n*$messageText*?\n1: Yes\n2: Enter new Query\n3: Never mind";
        sendBotResponse($Token, $responseMessage, $recipientWAID);
        echo $responseMessage;
    }
}

function handleTicketCreation($conn, $Token, $messageText, $recipientWAID, $selectedAppID , $profileName) {
    // Define your SQL query to insert ticket data (adjust table and column names)
    $sql = "INSERT INTO tickets (selectedAppID , recipientWAID, query, contact, contactName, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, NOW(), 'whatsapp', 'open')";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("sssss", $selectedAppID , $recipientWAID, $messageText, $recipientWAID, $profileName);

    // Execute the SQL statement
    if ($stmt->execute()) {
        // Ticket creation succeeded
        $confirmationMessage = 'Your ticket has been created. Thank you for your query.';
        sendBotResponse($Token, $confirmationMessage, $recipientWAID);
    } else {
        // Ticket creation failed
        $errorMessage = 'An error occurred while creating your ticket. Please try again later.';
        sendBotResponse($Token, $errorMessage, $recipientWAID);
    }

    // Close the prepared statement
    $stmt->close();
}
function createTicket($contact, $displayName,$mQuery){
    global $conn;
    $secret = My_SECRETE;
    $curl = curl_init();
    $tUrl= TURL;
    // Define the cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => $tUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
          "assigned" => "",
          "contact" => $contact,
          "createdBy" => array(
            "displayName" => $displayName,
            "uid" => null
          ),
          "query" => $mQuery,
          "status" => "open",
        )),
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json',
          "Authorization: Bearer $secret"
        ),
      ));
      echo $secret;
      // Execute the cURL request
      $response = curl_exec($curl);
      
      // Check for errors and handle the response as needed
      if (curl_errno($curl)) {
        // echo 'Curl error: ' . curl_error($curl);
        // echo "Error sending bot response. Status code:".  curl_error($curl);
        // $cError= curl_error($curl);
        // // Log the error and the received message to the MySQL database (adjust the SQL query)
        // $errorQuery = "INSERT INTO webhook_errors (timestamp, error, message, recipientWAID) VALUES (NOW(), 'Error sending bot response. Status code:', '$cError','$message', '$recipientWAID')";
        // if ($conn->query($errorQuery) === false) {
        //     echo "Error logging error: " . $conn->error;
        // }
      }
}
function sendBotResponse( $token, $message, $recipientWAID, $code = 1, $userData = []) {
    global $conn;
    $version = 'v17.0';
    $phoneNumberID = '118868224638325';

    $url = "https://graph.facebook.com/$version/$phoneNumberID/messages";

    $curl = curl_init();
// Define the cURL options
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(array(
      "messaging_product" => "whatsapp",
      "recipient_type" => "individual",
      "to" => $recipientWAID,
      "type" => "text",
      "text" => array(
        "preview_url" => false,
        "body" => $message
      )
    )),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      "Authorization: Bearer $token"
    ),
  ));
  
  // Execute the cURL request
  $response = curl_exec($curl);
  
  // Check for errors and handle the response as needed
  if (curl_errno($curl)) {
    echo 'Curl error: ' . curl_error($curl);
    echo "Error sending bot response. Status code:".  curl_error($curl);
$cError= curl_error($curl);
    // Log the error and the received message to the MySQL database (adjust the SQL query)
    $errorQuery = "INSERT INTO webhook_errors (timestamp, error, message, recipientWAID) VALUES (NOW(), 'Error sending bot response. Status code:', '$cError','$message', '$recipientWAID')";
    if ($conn->query($errorQuery) === false) {
        echo "Error logging error: " . $conn->error;
    }
  } else {
    // Handle the response here
    if ($code == 2) {
        
    $selectedAppID = $userData['selectedAppID'];
    echo "selectedAppID: " . $selectedAppID;
    //    echo json_encode($userData);
        // Update the status to verified in the MySQL database (adjust the SQL query)
        $statusQuery = "UPDATE whatsapp SET status = 'verified' WHERE selectedAppID  = '{$selectedAppID}'";
        if ($conn->query($statusQuery) === true) {
            $responseMessage = "The account $recipientWAID has been successfully verified. You can now use it to send and receive data";
            sendBotResponse( $token, $responseMessage, $recipientWAID);
echo $statusQuery;
            // Log other updates to the MySQL database as needed
        } else {
            echo "Error updating status: " . $conn->error;
        }
    }
  }
  
  // Close the cURL session
  curl_close($curl);

   
}

// Helper function to parse HTTP response headers
function parse_http_response($headers) {
    $response = [];

    foreach ($headers as $header) {
        $parts = explode(':', $header, 2);
        if (count($parts) == 2) {
            $response[strtolower(trim($parts[0]))] = trim($parts[1]);
        }
    }

    if (isset($response['response_code'])) {
        list($protocol, $code, $text) = explode(' ', $response['response_code'], 3);
        $response['protocol'] = $protocol;
        $response['response_code'] = intval($code);
        $response['response_text'] = $text;
    }

    return $response;
}

?>