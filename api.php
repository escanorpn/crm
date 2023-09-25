<?php
require_once 'helper.php';
require_once 'cors.php';  
require_once 'db.php';  
$response = array();  
set_error_handler("customError");

if (isset($_GET['apicall'])) {  
    switch ($_GET['apicall']) {
        
        case 'whatsapp':
           
            if ($data) {
                $response['data'] = $data;
                
                // Extract data fields from the JSON data
                $token = $data['Token'];
                $phoneNumberId = $data['Phone-Number-ID'];
                $version = $data['Version'];
                $status = $data['status'];
                $phoneNumber = $data['phoneNumber'];
                $verificationCode = $data['verificationCode'];
                $selectedAppID = $data['selectedAppID'];
                $path = $data['path'];
                
                $response['selectedAppID'] = $selectedAppID;
                
                // Prepare the SQL query with values
                $query = "INSERT INTO whatsapp (Token, phoneNumberId, Version, status, PhoneNumber, verificationCode, path, selectedAppID) 
                          VALUES ('$token', '$phoneNumberId', '$version', '$status', '$phoneNumber', '$verificationCode', '$path', '$selectedAppID') 
                          ON DUPLICATE KEY UPDATE 
                          Token=VALUES(Token), phoneNumberId=VALUES(phoneNumberId), 
                          Version=VALUES(Version), status=VALUES(status), 
                          PhoneNumber=VALUES(PhoneNumber), verificationCode=VALUES(verificationCode), path=VALUES(path), selectedAppID=VALUES(selectedAppID)";
                
                if ($conn->query($query)) {
                    http_response_code(200);
                    $response['message'] = "Validation data saved successfully";
                    $response['query'] = $query; // Store the executed SQL query in the response
                } else {
                    http_response_code(500);
                    $response['message'] = "Error saving validation data: " . $conn->error;
                    $response['query'] = $query; // Store the executed SQL query in the response even in case of an error
                }
            }
            break; 
        case 'whatsapp1':
           
            if ($data) {
                $response['data'] = $data;
                
                // Extract data fields from the JSON data
                $token = $data['Token'];
                $phoneNumberId = $data['Phone-Number-ID'];
                $version = $data['Version'];
                $status = $data['status'];
                $phoneNumber = $data['phoneNumber'];
                $selectedAppID = $data['selectedAppID'];
                $path = $data['path'];
                
                $response['selectedAppID'] = $selectedAppID;
                
                // Prepare the SQL query with values
                $query = "INSERT INTO whatsapp (Token, phoneNumberId, Version, status, PhoneNumber, path, selectedAppID) 
                          VALUES ('$token', '$phoneNumberId', '$version', '$status', '$phoneNumber', '$path', '$selectedAppID') 
                          ON DUPLICATE KEY UPDATE 
                          Token=VALUES(Token), phoneNumberId=VALUES(phoneNumberId), 
                          Version=VALUES(Version), status=VALUES(status), 
                          PhoneNumber=VALUES(PhoneNumber), path=VALUES(path), selectedAppID=VALUES(selectedAppID)";
                
                if ($conn->query($query)) {
                    http_response_code(200);
                    $response['message'] = "Validation data saved successfully";
                    // $response['query'] = $query; // Store the executed SQL query in the response
                } else {
                    http_response_code(500);
                    $response['message'] = "Error saving validation data: " . $conn->error;
                    $response['query'] = $query; // Store the executed SQL query in the response even in case of an error
                }
            }
            break;
            case 'tid':
                {
                    if ($data) {  
                        $tid = $data['tid'];
                        $recipientWAID = $data['recipientWAID'];
              
                if (updateTicketWithTID($tid, $recipientWAID, $conn)) {
                    echo "Ticket update successful.";
                } else {
                    echo "Ticket update failed.";
                }
                } else {  
                    $response['error'] = true;   
                    $response['message'] = 'bad data';  
                }
            }
            break;
            
            case 'status':
                {
                    if ($data) {  
                        $tid = $data['tid'];
                        $status = $data['status'];
              
                if (updateTicketStatus($tid, $status, $conn)) {
                    echo "Status update successful.";
                } else {
                    echo "Status update failed.";
                }
                } else {  
                    $response['error'] = true;   
                    $response['message'] = 'bad data';  
                }
            }
            break;
            case 'sms':
                {
                    if ($data) {
                        // Retrieve values from $data
                        $recipientWAID = $data['recipientWAID'];
            
                        // Retrieve selectedAppID from $data
                        $selectedAppID = $data['selectedAppID'];
                        $responseMessage = $data['responseMessage'];
            
                        // Fetch the Token from the database based on selectedAppID
                        $fetchTokenQuery = "SELECT Token FROM whatsapp WHERE selectedAppID = '$selectedAppID'";
                        $tokenResult = mysqli_query($conn, $fetchTokenQuery);
            
                        if ($tokenResult && mysqli_num_rows($tokenResult) > 0) {
                            $tokenData = mysqli_fetch_assoc($tokenResult);
                            $Token = $tokenData['Token'];
            
                            // Now you have $Token, $responseMessage, and $recipientWAID
                            sendBotResponse($Token, $responseMessage, $recipientWAID);
                                  // Update the 'status' field in the 'bots_data' table
                                    $updateStatusQuery = "UPDATE bots_data SET status = 2 WHERE selectedAppID = '$selectedAppID' AND rno='$recipientWAID";
                                    $updateStatusResult = mysqli_query($conn, $updateStatusQuery);

                                    if ($updateStatusResult) {
                                        echo "Status updated successfully for selectedAppID: $selectedAppID";
                                    } else {
                                        // Handle the case when the status update fails
                                        echo "Failed to update status for selectedAppID: $selectedAppID";
                                    }

                        } else {
                            // Handle the case when no Token is found
                            echo "No Token found for selectedAppID: $selectedAppID";
                        }
                    }
                }
                break;
            
                case 'sms':
                    {
                        if ($data) {
                            // Retrieve values from $data
                            $recipientWAID = $data['recipientWAID'];
                
                            // Retrieve selectedAppID from $data
                            $selectedAppID = $data['selectedAppID'];
                            $responseMessage = $data['responseMessage'];
                
                            // Fetch the Token from the database based on selectedAppID
                            $fetchTokenQuery = "SELECT Token FROM whatsapp WHERE selectedAppID = '$selectedAppID'";
                            $tokenResult = mysqli_query($conn, $fetchTokenQuery);
                
                            if ($tokenResult && mysqli_num_rows($tokenResult) > 0) {
                                $tokenData = mysqli_fetch_assoc($tokenResult);
                                $Token = $tokenData['Token'];
                
                                // Now you have $Token, $responseMessage, and $recipientWAID
                                sendBotResponse($Token, $responseMessage, $recipientWAID);
                            } else {
                                // Handle the case when no Token is found
                                echo "No Token found for selectedAppID: $selectedAppID";
                            }
                        }
                    }
                    break;

            case 'mail':
                {
                    if ($data) {  
                        $eventName = $data['eventName'];
                        $Location = $data['Location'];
                        $mDate = $data['mDate'];
                        $stSize = $data['stSize'];
                        $options = $data['options'];
                        $fullName = $data['fullName'];
                        $phone = $data['phone'];
                        $email = $data['email'];
                        $aReq = $data['aReq'];
    
                        $to = "vector.pn@gmail.com"; 
                        $from = $email; // this is the sender's Email address
                        $subject = "Form submission";
                        $message = " Quote request by:" . $fullName. "\n\n"." Exhibition name: ". $eventName."\n\n"." Exhibition location: ". $Location."\n\n"." Exhibition Date: ". $mDate."\n\n"." Stand size: ". $stSize."\n\n"." Phone number: ". $phone."\n\n"." Email: ". $email."\n\n"." Additional info: ". $aReq;
    
                        $headers = "From:" . $from;
                        $headers2 = "From:" . $to;
                        $response['fw'] = false;
    
                        if (mail($to, $subject, $message, $headers)) {
                            $response['message'] = "Mail Sent. Thank you " . $fullName . ", we will contact you shortly.";  
                            $response['fw'] = true;
                        } else {
                            $response['message'] = "Mail not sent Sent please try later"; 
                        }
                    } else {  
                        $response['error'] = true;   
                        $response['message'] = 'bad data';  
                    }
                }
                break;
    

        default:   
            $response['error'] = true;   
            $response['message'] = 'Invalid Operation Called';  
    }  
} else {  
    $response['error'] = true;   
    $response['message'] = 'Invalid API Call';  
} 

try {
    echo json_encode($response);
} catch (ErrorException $e) {
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}

function customError($errno, $errstr) {
    echo "<b>Error:</b> [$errno] $errstr";
}
function updateTicketStatus($ticketID, $newStatus, $conn) {
    // Sanitize input to prevent SQL injection
    $ticketID = mysqli_real_escape_string($conn, $ticketID);
    $newStatus = mysqli_real_escape_string($conn, $newStatus);

    // Update the ticket status with the provided status for the given ticket ID
    $updateQuery = "UPDATE ticket SET status = '$newStatus' WHERE tid = '$ticketID'";
    echo $updateQuery;
    $result = mysqli_query($conn, $updateQuery);

    if ($result) {
        // The update was successful
        // Log the operation, for example, by writing to a log file
        $logMessage = "Ticket with ID '$ticketID' updated with status '$newStatus'.";
        file_put_contents("update_ticket_status_log.txt", $logMessage . PHP_EOL, FILE_APPEND);

        return true; // Return true to indicate success
    } else {
        // The update failed
        return false; // Return false to indicate failure
    }
}

function updateTicketWithTID($tid, $recipientWAID, $conn) {
    // Sanitize input to prevent SQL injection
    $tid = mysqli_real_escape_string($conn, $tid);
    $recipientWAID = mysqli_real_escape_string($conn, $recipientWAID);

    // Update the ticket with the provided TID for the given contact
    $closeTicketQuery = "UPDATE ticket SET tid = '$tid' WHERE contact = '$recipientWAID'";
    $result = mysqli_query($conn, $closeTicketQuery);

    if ($result) {
        // The update was successful
        // Log the operation, for example, by writing to a log file
        $logMessage = "Ticket with TID '$tid' updated for contact '$recipientWAID'.";
        file_put_contents("update_ticket_log.txt", $logMessage . PHP_EOL, FILE_APPEND);
        
        return true; // Return true to indicate success
    } else {
        // The update failed
        return false; // Return false to indicate failure
    }
}



?>
