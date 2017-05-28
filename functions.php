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
	} else if ($_SESSION['roll'] == 'customer'){
		include_once('views/neworder.html');
	}
	
	if ($_SERVER['REQUEST_METHOD']=='GET'){
		include_once('views/neworder.html');
	}
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (empty($_POST['kauba nr.'])) {
			$errors[] = "Kauba nr. on määramata!";
		}
		if (empty($_POST['Kogus'])) {
			$errors[] = "Kogus on määramata!";
		}
		
		$KaubaNr = mysqli_real_escape_string($connection, $_POST['kauba nr.']);
		$kogus = mysqli_real_escape_string($connection, $_POST['kogus']);
		
		if (empty($errors)) {
			$query = "INSERT INTO atambek_proj_salesline (Item No., Quantity, , liik) VALUES ('$nimi','$vanus','$puur','$liik')";
			$result = mysqli_query($connection, $query);
			if (mysqli_insert_id($connection) > 0) {
				include_once('views/orders.html');
			} else {
				$errors[]= "Tellimuse loomine ebaõnnestus!";
			}
		}
	}
}

?>