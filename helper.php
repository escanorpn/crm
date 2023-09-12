<?php
function handleCommonGreetings($Token, $messageText, $recipientWAID, $metad, $profileName) {
    $commonGreetings = ['hi', 'hello', 'sasa']; // Add more common greetings as needed

    if (in_array(strtolower($messageText), $commonGreetings)) {
        $responseMessage = "Hi $profileName, how can I help you?";
        sendBotResponse($Token, $responseMessage, $recipientWAID);
    } else {
        handleMenuOptions($Token, $messageText, $recipientWAID, $metad, $profileName);
    }
}
function handleMenuOptions($Token, $messageText, $recipientWAID, $metad, $profileName) {
    if ($messageText === '1') {
        // Query to retrieve messages from the database (adjust this query according to your database schema)
        $sql = "SELECT messages FROM webhook_data WHERE metad = '$metad' AND recipientWAID = '$recipientWAID'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $messages = $row['messages'];
            }
            
            // Process the messages as needed
            handleTicketCreation($Token, $messages, $recipientWAID, $metad, $profileName);
        } else {
            echo 'No messages found for the specified recipientWAID.';
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
    }
}
function handleTicketCreation($conn, $Token, $messageText, $recipientWAID, $metad, $profileName) {
    // Define your SQL query to insert ticket data (adjust table and column names)
    $sql = "INSERT INTO tickets (metad, recipientWAID, query, contact, contactName, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, NOW(), 'whatsapp', 'open')";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("sssss", $metad, $recipientWAID, $messageText, $recipientWAID, $profileName);

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

function sendBotResponse($conn, $token, $message, $recipientWAID, $code = 1, $userData = []) {
    $version = 'v17.0';
    $phoneNumberID = '118868224638325';

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $recipientWAID,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $message
        ]
    ];

    $postData = json_encode($payload);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $token\r\n" .
                "Content-Type: application/json\r\n",
            'content' => $postData
        ]
    ];

    $context = stream_context_create($options);
    $url = "https://graph.facebook.com/$version/$phoneNumberID/messages";

    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        $httpResponse = parse_http_response($http_response_header);

        if ($httpResponse['response_code'] === 200) {
            echo 'Bot response sent:', $httpResponse['response_code'];

            if ($code == 2) {
                // Update the status to verified in the MySQL database (adjust the SQL query)
                $statusQuery = "UPDATE whatsapp SET status = 'verified' WHERE metad = '{$userData['metad']}'";
                if ($conn->query($statusQuery) === true) {
                    $responseMessage = "The account $recipientWAID has been successfully verified. You can now use it to send and receive data";
                    sendBotResponse($conn, $token, $responseMessage, $recipientWAID);

                    // Log other updates to the MySQL database as needed
                } else {
                    echo "Error updating status: " . $conn->error;
                }
            }
        } else {
            echo "Error sending bot response. Status code: {$httpResponse['response_code']}";

            // Log the error and the received message to the MySQL database (adjust the SQL query)
            $errorQuery = "INSERT INTO webhook_errors (timestamp, error, message, recipientWAID) VALUES (NOW(), 'Error sending bot response. Status code: {$httpResponse['response_code']}', '$message', '$recipientWAID')";
            if ($conn->query($errorQuery) === false) {
                echo "Error logging error: " . $conn->error;
            }
        }
    } else {
        echo 'Error sending bot response: ' . error_get_last()['message'];

        // Log the error and the received message to the MySQL database (adjust the SQL query)
        $errorQuery = "INSERT INTO webhook_errors (timestamp, error, message, recipientWAID) VALUES (NOW(), '" . error_get_last()['message'] . "', '$message', '$recipientWAID')";
        if ($conn->query($errorQuery) === false) {
            echo "Error logging error: " . $conn->error;
        }
    }
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