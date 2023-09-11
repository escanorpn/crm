<?php   
require_once 'cors.php';  
  require_once 'db.php';  
  $response = array();  
  set_error_handler("customError");

  if(isset($_GET['apicall'])){  
  switch($_GET['apicall']){ 
    case 'whatsapp';
    {
    if($data){

        $token = $data['Token'];
        $phoneNumberId = $data['Phone-Number-ID'];
        $version = $data['Version'];
        $status = $data['status'];
        $phoneNumber = $data['phoneNumber'];
        $verificationCode = $data['verificationCode'];
        $phoneNumber = $data['PhoneNumber'];
    
        // You can use prepared statements to prevent SQL injection here
        $stmt = $conn->prepare("INSERT INTO whatsapp (Token, Phone_Number_ID, Version, status, phone_number, verification_code, PhoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE Token=?, Version=?, status=?, phone_number=?, verification_code=?, PhoneNumber=?");
    
        $stmt->bind_param("ssssssssssss", $token, $phoneNumberId, $version, $status, $phoneNumber, $verificationCode, $phoneNumber, $token, $version, $status, $phoneNumber, $verificationCode, $phoneNumber);
    
        if ($stmt->execute()) {
            http_response_code(200);
            echo "Validation data saved successfully";
        } else {
            http_response_code(500);
            echo "Error saving validation data";
        }
    
        $stmt->close();
     }}
      
  case 'mail';
  {
    
  if($data){  
    
    $eventName=$data['eventName'];
    $Location=$data['Location'];
    $mDate=$data['mDate'];
    $stSize=$data['stSize'];
    $options=$data['options'];
    $fullName=$data['fullName'];
    $phone=$data['phone'];
    $email=$data['email'];
    $aReq=$data['aReq'];


    $to = "vector.pn@gmail.com"; 
    $from = $email; // this is the sender's Email address
    $subject = "Form submission";
    $message =" Quote request by:" . $fullName. "\n\n"." Exhibition name: ". $eventName."\n\n"." Exhibition location: ". $Location."\n\n"." Exhibition Date: ". $mDate."\n\n"." Stand size: ". $stSize."\n\n"." Phone number: ". $phone."\n\n"." Email: ". $email."\n\n"." Additional info: ". $aReq;
    // $message2 = "Here is a copy of your message " . $name . "\n\n" . $msg;

    $headers = "From:" . $from;
    $headers2 = "From:" . $to;
    $response['fw']=false ;
    if(mail($to,$subject,$message,$headers)){
            
        $response['message'] = "Mail Sent. Thank you " . $fullName . ", we will contact you shortly.";  
        $response['fw']=true ;
    }else{
      $response['message'] = "Mail not sent Sent please try later"; 
     
    }
    // $response['message'] = $data;  
    
  
} else{  
    $response['error'] = true;   
    $response['message'] = 'bad data';  
	// $response['user'] = $user; 
 }  
  }
default:   
 $response['error'] = true;   
 $response['message'] = 'Invalid Operation Called';  
}  
}  
else{  
 $response['error'] = true;   
 $response['message'] = 'Invalid API Call';  
  } 
  
try
{
    echo json_encode($response);
    // echo "s".$response;
}
catch (ErrorException $e)
{
    // do some thing with $e->getMessage()
	$response['message']=$e->getMessage();
  echo json_encode($response);
 
}
  
function customError($errno, $errstr) {
  echo "<b>Error:</b> [$errno] $errstr";
}

function isTheseParametersAvailable($params){  
foreach($params as $param){  
 if(!isset($_POST[$param])){  
     return false;   
  }  
}  
return true;   
}  

?>  