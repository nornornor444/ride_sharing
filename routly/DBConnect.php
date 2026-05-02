<?php
$servername="localhost";
$username="root";  //This is the username
$password = "";
$dbname="ride_sharing";  // This are all of the variable naming

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error)
{
	die("Connection failed: " . $conn->connect_error);  // Used to print and then stop the code completely
}
else 
{
	
	mysqli_select_db($conn,$dbname);
}
?>