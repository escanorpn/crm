
<?php
//session_start();
	error_reporting(E_ALL);
ini_set('display_errors', 1);


		
		$servername="localhost";
		$username="lmglobal_lmglobal";
		$password="lmglobal_lmglobal";
		$dbname="lmglobal_crm";
		

	$conn=mysqli_connect($servername,$username,$password,$dbname);

	if(!$conn){
		die("connection failed:".mysqli_connect_error());
	}
	$data = json_decode(file_get_contents('php://input'), true);
	// $data=$data1['fieldsValue'];

?>
