
<?php
//session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Define the secret
define('My_SECRETE', 'aX12MmhK2RlFLQcOvfhSaSj8Plv2hJBJ3TTEljWihgtA000YGeduu2rt016mwtLBbty8wbUgBjeyL7FtdPPNpkycTGbW022qZNmS');
define('TURL', 'https://us-central1-keja-a108d.cloudfunctions.net/addPackage');
define('CHATURL', 'https://us-central1-keja-a108d.cloudfunctions.net/addChat');
		
		$servername="localhost";
		$username="lmglobal_lmglobal";
		$password="lmglobal_lmglobal";
		$dbname="lmglobal_crm";
		
		global $conn; // Declare $conn as a global variable
	$conn=mysqli_connect($servername,$username,$password,$dbname);

	if(!$conn){
		die("connection failed:".mysqli_connect_error());
	}
	$data = json_decode(file_get_contents('php://input'), true);
	// $data=$data1['fieldsValue'];

?>
