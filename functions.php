<?php

function connect_db(){
	//Tiia tänav - loomaaia kood >>
	global $connection;
	$host="localhost";
	$user="test";
	$pass="t3st3r123";
	//$user="root";
	//$pass="";
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
				header("get_default()");
				header("Location: ?");
				//include_once('views/head.html');
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
						$status = 'inprocess';
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
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (($status=='pending') && (isset($_SESSION['approved'])) && ($_SESSION['approved'] == false)) {
				approve_order();
				$_SESSION['approved'] = true;
			} else {
				$_SESSION['approved'] = false;
			}
		}
		include_once('views/head.html');
		switch($status) {
			case 'customerorders':				
				if (isset($_SESSION['klient'])) {
					$customer = $_SESSION['klient'];
					$orders_query ="SELECT OrderNo FROM atambek_proj_salesheader where CustomerNo='$customer' ORDER BY OrderNo";
				} else {
					$orders_query ="SELECT OrderNo FROM atambek_proj_salesheader ORDER BY OrderNo";
				}
			break;
			case 'pending':
				$orders_query ="SELECT OrderNo FROM atambek_proj_salesheader where Approved='0'";
			break;
			case 'inprocess':
				$orders_query ="SELECT OrderNo FROM atambek_proj_salesheader where Approved='1'";
			break;
			case 'processed':
				$orders_query ="SELECT DISTINCT(OrderNo) FROM atambek_proj_salesline where OutstandingQty = 0 ORDER BY OrderNo";
			break;
			default:
				$orders_query ="SELECT DISTINCT(OrderNo) FROM atambek_proj_salesline ORDER BY OrderNo";
		}
		$orders_result = mysqli_query($connection, $orders_query) or die("$orders_query - ".mysqli_error($connection));
		$orders = array();
		while ($order=mysqli_fetch_assoc($orders_result)) {
			switch($status) {
				case 'inprocess':
					$lines_query = "SELECT * FROM atambek_proj_salesline WHERE OrderNo = ".mysqli_real_escape_string($connection,$order['OrderNo']) AND 'OutstandingQty' > 0 ;
				break;
				default:
					$lines_query = "SELECT * FROM atambek_proj_salesline WHERE OrderNo = ".mysqli_real_escape_string($connection,$order['OrderNo']);
			}
			$lines_result = mysqli_query($connection, $lines_query) or die("$lines_query - ".mysqli_error($connection));
			while ($line=mysqli_fetch_assoc($lines_result)) {
				$orders[$order['OrderNo']][]=$line;
			}		
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
				header("Location: ?page=pending");
		}
		
	} else {
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
	
	if (($_SERVER['REQUEST_METHOD'] == 'POST')&&isset($_POST['Approved'])) {		
		$inserted = false;
		for($i = 0; $i < sizeof($_POST["Kogus"]); $i++) {  
			//var_dump($_POST['Kogus']);
			$itemno = mysqli_real_escape_string($connection, $_POST['HiddenItemNo'][$i]);
			$qty = mysqli_real_escape_string($connection, $_POST['Kogus'][$i]);
			$price = mysqli_real_escape_string($connection, $_POST['HiddenPrice'][$i]);
			$customer = $_SESSION['klient'];
			$user = $_SESSION['user'];
			if (empty($errors)) {
				if (($qty != 0)&&(!$inserted)) {
					$query_cust = "SELECT CustomerName FROM atambek_proj_customer WHERE CustomerNo = '$customer'";
					$cust_result = mysqli_query($connection, $query_cust);
					$cust_record = mysqli_fetch_assoc($cust_result);
					$query_hdr = "INSERT INTO atambek_proj_salesheader (OrderNo, CustomerNo, CustomerName, CreatedBy, Approved) VALUES ('0','$customer','$cust_record[CustomerName]','$user','0')";
					$result_hdr = mysqli_query($connection, $query_hdr);
					$insertid = mysqli_insert_id($connection);
					$inserted = ($insertid > 0);
				}
				if (($qty !=0)&&$inserted) {
					$query_hdr = "SELECT OrderNo FROM atambek_proj_salesheader WHERE OrderNo = '$insertid'";
					$result_hdr = mysqli_query($connection, $query_hdr);
					$salesheader = mysqli_fetch_assoc($result_hdr); 
					$orderno = $salesheader['OrderNo']; 
					$query_lines = "SELECT MAX(LineNo) as LineNo FROM atambek_proj_salesLine";
					$result_line = mysqli_query($connection, $query_lines);
					if (!$result_line) {
						$lineno = 0;
					} else {
						$row = mysqli_fetch_assoc($result_line);
						$lineno = $row['LineNo'];
					}
					//$lineno++;
					$query_lines = "INSERT INTO atambek_proj_salesline (OrderNo, LineNo, ItemNo, Quantity, Price, PickedQty, OutstandingQty) VALUES ('$orderno', '$lineno', '$itemno','$qty','$price', 0, '$qty')";
					$result_line = mysqli_query($connection, $query_lines);
					//include_once('views/customerorders.html');
				}
			}
			
		}
		if (!$inserted) {
			$errors[]= "Tellimuse loomine ebaõnnestus!";
		}		
		//include_once('views/customerorders.html');
		show_orders('customerorders');
		
	}
}

function approve_order () {		
global $connection;
	if (empty($_SESSION['user'])) {
		include_once('views/login.html');
	}
		
	if ($_SERVER['REQUEST_METHOD']=='GET'){
		include_once('views/orderspending.html');
	}
			
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//var_dump($_POST);
		for($i = 0; $i < sizeof($_POST['Approved']); $i++) {
			//if (mysqli_real_escape_string($connection, $_POST['Approved'][$i]) == 'on') {
				//$orderno = mysqli_real_escape_string($connection, $_POST['HiddenOrderNo'][$i]);
				//echo $_POST['HiddenOrderNo'][$i];
				$orderno = $_POST['Approved'][$i];
				$query = "UPDATE atambek_proj_salesheader SET Approved=1 WHERE OrderNo='$orderno'";
				//echo $query;
				$result = mysqli_query($connection, $query);
				$_SESSION['approved'] = true;
				if (mysqli_affected_rows($connection) == 0) {
					$errors[] = "Rida ei õnnestunud uuendada!";
				}
		}
			
		//include_once('views/orderspending.html');
		show_orders('pending');
		//header("Location: ?");
	}
}		
function get_default() { 
	if (empty($_SESSION['user'])) {
		$page = "?page=pending";
	} else {
		$roll = $_SESSION['roll'];
		switch($roll) {
			case 'admin':
				$page = "?page=pending";
				break;
			case 'warehouse worker':
				$page = "?page=inprocess";
				break;
			case 'order processor':
				$page = "?page=pending";
				break;
			case 'customer':
				$page = "?page=custorders";
				break;
			default:
				$page = "?page=pending";
				break;
		}
	}	
	return $page;
}
?>