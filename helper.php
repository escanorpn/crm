<?php
require_once 'cors.php';  
require_once 'db.php';  

function handleCommonGreetings($Token, $messageText, $recipientWAID, $selectedAppID , $profileName) {
    global $conn;
    $commonGreetings = ['hi', 'hello', 'sasa']; // Add more common greetings as needed
    // Ticket is open
    $checkTicketQuery = "SELECT * FROM ticket WHERE contact = '$recipientWAID' AND selectedAppID = '$selectedAppID' AND  status = 'open' LIMIT 1";
    $result = mysqli_query($conn, $checkTicketQuery);

  
    if ($result && mysqli_num_rows($result) > 0) {
        $ticketData = mysqli_fetch_assoc($result);
       
        if ($ticketData['tid']!=="") {
            // echo $ticketData['tid'] . "\n";
      
        // echo $checkTicketQuery. "\n";
          // Retrieve path to ticket is open appid-> user number
            $messages = $messageText;
            $sql1 = "SELECT path FROM whatsapp WHERE selectedAppID = '$selectedAppID' ORDER BY id DESC LIMIT 1";

            // echo "path retrieved". $sql1;
            $result1 = $conn->query($sql1);
            
            if ($result1->num_rows > 0) {
                echo "path retrieved";
            $row1 = $result1->fetch_assoc();

            $path = $row1['path'];
            $mData = [
                "assigned" => "",
                "contact" => $recipientWAID,
                // "createdBy" => [
                //     "displayName" => "Whatsapp",
                //     "uid" => ""
                // ],
                
                "createdBy" => 'whatsapp',
                "profileName" => $profileName,
                "query" => $messages,
                "text" => $messages,
                "status" => "open",
                
            ];
     

           echo 'sms sent';

            createChat($Token,$recipientWAID, $profileName,$messages,$mData,$path, $messages, $ticketData,$selectedAppID);
            $sql = "SELECT status,organizationName FROM bots_data WHERE rno = '$recipientWAID' AND selectedAppID = '$selectedAppID' LIMIT 1";
            $result = $conn->query($sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $bottData = mysqli_fetch_assoc($result);
                $status= $bottData['status'];
                $orgName= $bottData['organizationName'];
                if($status==3){
                $confirmationMessage = " *$orgName* Let me get a human to respond to your ticket. Respond with '4' to close the ticket.";
                sendBotResponse($Token, $confirmationMessage, $recipientWAID);
                }
            }
        }
    }else{
                if ($messageText === '4') {
                    // If the user responds with '4', set the ticket status to 'closed'
                    $closeTicketQuery = "UPDATE ticket SET status = 'closed' WHERE contact = '$recipientWAID'";
                    mysqli_query($conn, $closeTicketQuery);
                    $confirmationMessage = "Previous tickets closed.";
                    sendBotResponse($Token, $confirmationMessage, $recipientWAID);
                    return;
            
                }
                // $ticketData['id'] is not defined (undefined or null)
          $confirmationMessage = "Please wait for the ticket to be responded. Respond with '4' to close the ticket.";
          sendBotResponse($Token, $confirmationMessage, $recipientWAID);
        //   echo $path;
          return;
          }
                
    }else{
        if (in_array(strtolower($messageText), $commonGreetings)) {
            $responseMessage = "Hi $profileName, how can I help you?";
            sendBotResponse($Token, $responseMessage, $recipientWAID);
        } else {
            handleMenuOptions($Token, $messageText, $recipientWAID, $selectedAppID , $profileName);
       
        }
    }
  
}
function handleMenuOptions($Token, $messageText, $recipientWAID, $selectedAppID, $profileName) {
    global $conn;
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
            $sql1 = "SELECT path FROM whatsapp WHERE selectedAppID = '$selectedAppID'  ORDER BY id DESC LIMIT 1";
            $result1 = $conn->query($sql1);
            
            if ($result1->num_rows > 0) {
                
            $row1 = $result1->fetch_assoc();

            $path = $row1['path'];
            $mData = [
                "assigned" => "",
                "contact" => $recipientWAID,
                "createdBy" => [
                    "displayName" => "Whatsapp",
                    "uid" => ""
                ],
                
                "createdBy" => 'whatsapp',
                "profileName" => $profileName,
                "query" => $messages,
                "text" => $messages,
                "status" => "open",
                
            ];
     

            // Process the messages as needed
            // handleTicketCreation($Token, $messages, $recipientWAID, $selectedAppID, $profileName);
            createTicket($Token,$recipientWAID, $profileName,$messages,$mData,$path, $messages,$selectedAppID);
            // Update the last message in the database
            }
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
    } elseif ($messageText === '5') {
         // Prepare and execute the SQL query to insert the data
         $updateSql = "UPDATE bots_data
         SET selectedAppID = ''
         WHERE rno = '$recipientWAID'";                 
   echo $updateSql;

     if ($conn->query($updateSql) === TRUE) {
        $responseMessage = "Okay, please eneter business code!";
     } else {
        $responseMessage = "There was an issue please try again later!";
         echo "Error updating data into bots_data: " . $conn->error;
     }
        
        sendBotResponse($Token, $responseMessage, $recipientWAID);
    } elseif ($messageText === '4') {
        // If the user responds with '4', set the ticket status to 'closed'
        $closeTicketQuery = "UPDATE ticket SET status = 'closed' WHERE contact = '$recipientWAID'";
        mysqli_query($conn, $closeTicketQuery);
        $confirmationMessage = "Previous tickets closed.";
        sendBotResponse($Token, $confirmationMessage, $recipientWAID);
        return;

    }
    else {
        $responseMessage = "Would you like me to log the issue\n*$messageText*?\n1: Yes\n2: Enter new Query\n3: Never mind";
        $sql = "SELECT selectedAppID,organizationName FROM bots_data WHERE rno = '$recipientWAID' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $bottData = mysqli_fetch_assoc($result);
            $orgName= $bottData['organizationName'];
            $responseMessage = "Would you like me to log the issue\n*$messageText* \n to *$orgName*?\n1: Yes\n2: Enter new Query\n3: Never mind \n5: Choose different organization";
        }
       
        sendBotResponse($Token, $responseMessage, $recipientWAID);
     
        echo json_encode(array('message' => $responseMessage,'selectedAppID' => $selectedAppID));
    }
}

function handleTicketCreation( $Token, $messageText, $recipientWAID, $selectedAppID , $profileName) {
    global $conn;
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
function createChat($Token, $contact, $displayName, $mQuery, $mData, $path1,$message,  $ticketData,$selectedAppID)
{
   global $conn;
    $secret = My_SECRETE;
    $curl = curl_init();
    $CHATURL = CHATURL;
    
    $path = $path1 . '/tickets/'.$ticketData['tid'].'/chats';
    $path2 = $path1 . '/tickets/'.$ticketData['tid'];
    echo $path;
     // No open ticket with the same contact, proceed with creating a new ticket
     $payload = [
        "ticketPath" => $path2,
        "chatPath" => $path,
        "data" => $mData
    ];

    // Use the $mData array in json_encode
    $jsonData = json_encode($payload);

    // Define the cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => $CHATURL,
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

    // Check for errors and handle the response as needed
    if (curl_errno($curl)) {
        echo 'Curl error: ' . curl_error($curl);
        // Ticket creation failed
        $errorMessage = 'An error occurred while procesing your input. Please try again later.';
        sendBotResponse($Token, $errorMessage, $contact);
    }else{
        $updateStatusQuery = "UPDATE bots_data SET status = 3 WHERE selectedAppID = '$selectedAppID' AND rno='$contact' ";
        $updateStatusResult = mysqli_query($conn, $updateStatusQuery);

        if ($updateStatusResult) {
            echo "Status updated successfully for selectedAppID: $selectedAppID";
        } else {
            // Handle the case when the status update fails
            echo "Failed to update status for selectedAppID: $selectedAppID";
        }
    } 
    echo "chat sent";
}

function createTicket($Token, $contact, $displayName, $mQuery, $mData, $path1,$message,$selectedAppID)
{
   
    global $conn;
    $secret = My_SECRETE;
    $curl = curl_init();
    $tUrl = TURL;
    $path = $path1 . '/tickets';

    $ck=0;
    // Check if a ticket with the given contact already exists
    $checkTicketQuery = "SELECT * FROM ticket WHERE contact = '$contact' AND selectedAppID='$selectedAppID' LIMIT 1";
    $result = mysqli_query($conn, $checkTicketQuery);

    if ($result && mysqli_num_rows($result) > 0) {
        
        $ticketData = mysqli_fetch_assoc($result);
        echo $path;
        if ($ticketData['tid']!=="") {
            echo $ticketData['tid'];
        if ($ticketData['status'] === 'closed') {
            
            $path = $path1 . '/tickets';
            // Ticket with the same contact exists but is closed, update its status to 'open'
            $updateTicketQuery = "UPDATE ticket SET status = 'open', message = '$message' WHERE contact = " . $contact;
           
            mysqli_query($conn, $updateTicketQuery);
        $ck=0;
        } else {
            
            $path = $path1 . '/tickets/'.$ticketData['tid'].'/chats';
            echo $path;
            $ck=1;
            // Ticket with the same contact already exists and is open
            $confirmationMessage = 'A ticket with your contact already exists and is open. Thank you for your query.';
            // sendBotResponse($Token, $confirmationMessage, $contact);
        }
    } else {
        // $ticketData['id'] is not defined (undefined or null)
        $confirmationMessage = "Please wait for the ticket to be responded. Respond with '4' to close the ticket.";
         sendBotResponse($Token, $confirmationMessage, $contact);
         echo $path;
         return;
        
     
    }
  
    } 
     // No open ticket with the same contact, proceed with creating a new ticket
     $payload = [
        "path" => $path,
        "data" => $mData
    ];

    // Use the $mData array in json_encode
    $jsonData = json_encode($payload);

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
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            "Authorization: Bearer $secret"
        ),
    ));

    // Execute the cURL request
    $response = curl_exec($curl);

    // Check for errors and handle the response as needed
    if (curl_errno($curl)) {
        echo 'Curl error: ' . curl_error($curl);
        // Ticket creation failed
        $errorMessage = 'An error occurred while procesing your input. Please try again later.';
        sendBotResponse($Token, $errorMessage, $contact);
    } else {
       
        if(  $ck==0){
        // Ticket creation succeeded
        $confirmationMessage = 'Your ticket has been created. Thank you for your query.';
        sendBotResponse($Token, $confirmationMessage, $contact);
        
         // Create a new ticket in the database with status 'open'
         $insertTicketQuery = "INSERT INTO ticket (contact,message, status,selectedAppID, createdAt) VALUES ( '$contact','$message', 'open','$selectedAppID', NOW())";
         echo $insertTicketQuery;
         mysqli_query($conn, $insertTicketQuery);
    }
      
    }
}

function sendBotResponse( $token, $message, $recipientWAID, $code = 1, $userData = []) {
    echo 'send';
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