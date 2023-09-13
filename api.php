<?php
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
?>
