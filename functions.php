<?php

function connect_db(){
	//Tiia tänav - loomaaia kood >>
	global $connection;
	$host="localhost";
	//$user="test";
	//$pass="t3st3r123";
	$user="root";
	$pass="";
	$db="test";
	$connection = mysqli_connect($host, $user, $pass, $db) or die("ei saa ühendust mootoriga- ".mysqli_error());
	mysqli_query($connection, "SET CHARACTER SET UTF8") or die("Ei saanud baasi utf-8-sse - ".mysqli_error($connection));
	//Tiia tänav - loomaaia kood <<
}

function logi() {	
	if (isset($_SESSION['user'])) {
		//show_orders($_SESSION['roll']);
		//header("Location: ?");
	}
	else if ($_SERVER['REQUEST_METHOD']=='GET'){
		include_once('views/login.html');
	}
	
	if ($_SERVER['REQUEST_METHOD'] == "POST") {
		if (empty($_POST['user'])) {
			$errors[] = "Kasutajanimi on puudu!";
			include_once('views/login.html');
			break;
		}
		else if (empty($_POST['pass'])) {
			$errors[] = "Parool on puudu!";
			include_once('views/login.html');
			break;
		}
		else {
			global $connection;
			$trim_user =  mysqli_real_escape_string($connection,htmlspecialchars($_POST['user']));
			$trim_pass = mysqli_real_escape_string($connection,htmlspecialchars($_POST['pass']));
			$users_query ="SELECT * FROM atambek_proj_user WHERE UserName = '$trim_user' AND Password = SHA1('$trim_pass')";	
			$users_result=mysqli_query($connection, $users_query) or die("$users_query - ".mysqli_error($connection));
			if (mysqli_num_rows($users_result) != 0){
				$row = mysqli_fetch_assoc($users_result);
				$_SESSION['user'] = $row["UserName"];
				$_SESSION['roll'] = $row["Role"];
				$_SESSION['klient'] = $row["CustomerNo"];
				include_once('views/head.html');
				switch($row["Role"]) {
					case 'admin':
						$status = 'pending';
						show_orders($status);
					break;
					case 'customer':
						$status = 'customerorders';
						show_orders($status);
					break;	
					case 'order processor':
						$status = 'pending';
						show_orders($status);
					break;
					case 'warehouse worker':
						$status = 'in process';
						show_orders($status);
					break;
				}
				
			}else{
				$errors[] = "Kasutajanimi või parool on vale";
				include_once('views/login.html');
			}
		}
	}
}

function logout(){
	$_SESSION=array();
	session_destroy();
	header("Location: ?");
}

function show_orders($status) {
	global $connection;
	if(isset($_SESSION['user'])) {
		include_once('views/head.html');
		$orders_query ="SELECT * FROM atambek_proj_salesheader";
		$orders_result = mysqli_query($connection, $orders_query) or die("$orders_query - ".mysqli_error($connection));
		$orders = array();
		while ($order=mysqli_fetch_assoc($orders_result)) {
			$orders[] = $order;	
		}
		switch($status) {
			case 'customerorders':
				include_once('views/customerorders.html');
				break;
			case 'pending':
				include_once('views/orderspending.html');
				break;
			case 'inprocess':
				include_once('views/ordersinprocess.html');
				break;
			case 'processed':
				include_once('views/ordersprocessed.html');
			break;
			default:
				include_once('views/orderspending.html');			
		}	
	}
	else {
		header("Location: ?page=login");
	}
}

function add_order() {
	global $connection;
	if (empty($_SESSION['user'])) {
		include_once('views/login.html');
	}
	
	if ($_SERVER['REQUEST_METHOD']=='GET'){
		include_once('views/neworder.html');
	}
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {		
		$itemno = mysqli_real_escape_string($connection, $_POST['HiddenItemNo']);
		$qty = mysqli_real_escape_string($connection, $_POST['Kogus']);
		$price = mysqli_real_escape_string($connection, $_POST['HiddenPrice']);
		$customer = $_SESSION['klient'];
		$user = $_SESSION['user'];
		if (empty($errors)) {
			$query_cust = "SELECT CustomerName FROM atambek_proj_customer WHERE CustomerNo = '$customer'";
			$cust_result = mysqli_query($connection, $query_cust);
			$cust_record = mysqli_fetch_assoc($cust_result);
			$query_hdr = "INSERT INTO atambek_proj_salesheader (OrderNo, CustomerNo, CustomerName, CreatedBy, Approved) VALUES ('0','$customer','$cust_record[CustomerName]','$user','0')";
			$result_hdr = mysqli_query($connection, $query_hdr);
			
			if (mysqli_insert_id($connection) > 0) {
				$insertid = mysqli_insert_id($connection);
				$query_hdr = "SELECT OrderNo FROM atambek_proj_salesheader WHERE OrderNo = '$insertid'";
				$result_hdr = mysqli_query($connection, $query_hdr);
				$salesheader = mysqli_fetch_assoc($result_hdr); 
				$orderno = $salesheader['OrderNo']; 
				$query_lines = "SELECT MAX(LineNo) as LineNo FROM atambek_proj_salesLine";
				$result_line = mysqli_query($connection, $query_lines);
				if (!$query_lines) {
					$lineno = 0;
				} else {
					$row = mysqli_fetch_assoc($result_line);
					$lineno = $row['LineNo'];
				}
				$lineno++;
				echo $lineno;
				echo $orderno;
				$query_lines = "INSERT INTO atambek_proj_salesline (OrderNo, LineNo, ItemNo, Quantity, Price, PickedQty, OutstandingQty) VALUES ('$orderno', '$lineno', '$itemno','$qty','$price', 0, '$qty')";
				$result_line = mysqli_query($connection, $query_lines);
				echo $orderno; 
				include_once('views/customerorders.html');
			} else {
				$errors[]= "Tellimuse loomine ebaõnnestus!";
			}
		}
		include_once('views/customerorders.html');
	}
}

?>