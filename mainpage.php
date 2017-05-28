<?php
require_once('functions.php');
session_start();
connect_db();

$page="pealeht";

if (isset($_GET['page']) && $_GET['page']!=""){
	$page=htmlspecialchars($_GET['page']);
}

include('views/head.html');

switch($page){
	case "login":
		logi();
	break;
	case "custorders":
		show_orders('customerorders');
	break;
	case "pending":
		show_orders('pending');
	break;
	case "inprocess":
		show_orders('inprocess');
	break;
	case "processed":
		show_orders('processed');
	break;
	case "add":
		add_order();
	break;
	case "logout":
		logout();
	break;
	default:
		include_once('views/main.html');
}

include_once('views/foot.html');

?>