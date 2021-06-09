<?php

namespace App;

ini_set('display_startup_errors',1); 
ini_set('display_errors','on');  // 1
error_reporting(E_ALL); // 11

require_once 'src/Autoloader.php';
Autoloader::register();

// Set timezone
date_default_timezone_set('UTC');

// Check IP, deny access if not allowed
if(!(empty(Config::ACCESS_IP) OR $_SERVER['REMOTE_ADDR'] == "127.0.0.1" OR $_SERVER['REMOTE_ADDR'] == "::1" OR $_SERVER['REMOTE_ADDR'] == Config::ACCESS_IP OR $_SERVER['REMOTE_ADDR'] == Config::ACCESS_IP2 OR $_SERVER['REMOTE_ADDR'] == Config::ACCESS_IP3)){
	header('Location: login.html');
	exit; 
}

// Cronjob Rule Run
if(isset($_GET['job']) AND $_GET['job'] === substr(hash('sha256', Config::PASSWORD."ebe8d532"),0,24)){
	require_once 'src/Utility.php';
	$blocknetd = new jsonRPCClient('http://'.Config::RPC_USER.':'.Config::RPC_PASSWORD.'@'.Config::RPC_IP.'/');
	Rule::run();
	exit;
}


// Start check user session
session_start();
$passToken = hash('sha256', Config::PASSWORD."ibe81rn6");

// Active Session
if(isset($_SESSION['login']) AND $_SESSION['login'] === TRUE){
	// Nothing needs to be done
	
// Login Cookie available	
}elseif(isset($_COOKIE["Login"]) AND $_COOKIE["Login"] == $passToken){
		$_SESSION['login'] = TRUE;
		$_SESSION["csfrToken"] = hash('sha256', random_bytes(20));

// Login		
}elseif(!isset($_SESSION['login']) AND isset($_POST['password']) AND $_POST['password'] == Config::PASSWORD){
	ini_set('session.cookie_httponly', '1');
	$passHashed = hash('sha256', Config::PASSWORD);
	
		$_SESSION['login'] = TRUE;
		$_SESSION["csfrToken"] = hash('sha256', random_bytes(20));
		if(isset($_POST['stayloggedin'])){		
			setcookie("Login", $passToken, time()+2592000, "","",FALSE, TRUE);
		}

// Not logged in or invalid data
//}else{
//	header('Location: login.html');
//	exit; 	
}

// Load utility and content creator functions
require_once 'src/Utility.php';
require_once 'src/Content.php';
require_once 'ccxt/ccxt.php';

// Globals
$error = "";
$message = "";
$trafficC = 0;
$trafficCIn = 0;
$trafficCOut = 0;
$newPeersCount = 0;
$blocknetd = new jsonRPCClient('http://'.Config::RPC_USER.':'.Config::RPC_PASSWORD.'@'.Config::RPC_IP.'/', Config::DEBUG);

use SQLite3;
$db = new SQLite3('data/blocknet.db');

// Do some database setup
$db->enableExceptions(true);

$db->exec('CREATE TABLE IF NOT EXISTS "pastorders"(
	"id" VARCHAR PRIMARY KEY NOT NULL,
	"timestamp" INTEGER NOT NULL,
	"fee_txid" VARCHAR NOT NULL,
	"nodepubkey" VARCHAR NOT NULL,
	"taker" VARCHAR NOT NULL,
	"taker_size" INTEGER NOT NULL,
	"maker" VARCHAR NOT NULL,
	"maker_size" INTEGER NOT NULL
)');

$db->exec('CREATE TABLE IF NOT EXISTS "events"(
	"lastorderheight" INTEGER,
	"timestamp" INTEGER
)');


// Content
// Main Page
if(empty($_GET) OR $_GET['p'] == "main") {   
	try{
	$content = createMainContent();
	}catch(\Exception $e) {
	   $error = "Node offline or incorrect RPC data";
	}
	$data = array('section' => 'main', 'title' => 'Home', 'content' => $content);   
   
// New Main Page
}elseif($_GET['p'] == "newmain") {   
	try{
	$content = createMainContent();
	}catch(\Exception $e) {
	   $error = "Node offline or incorrect RPC data";
	}
	$data = array('section' => 'newmain', 'title' => 'Home', 'content' => $content);   
   
// Peers Page   
}elseif($_GET['p'] == "peers") {
	
	// Information for header
	$content = createPeerContent();
	
	// Create page specfic variables
	$data = array('section' => 'peers', 'title' => 'Peers', 'content' => $content);

// Memory Pool Page	
}elseif($_GET['p'] == "mempool") {
	
	if(isset($_GET['e']) AND ctype_digit($_GET['id'])){
		$end = $_GET['e'];
	}else{
		$end = Config::DISPLAY_TXS;
	}
	
	$content = createMempoolContent($end);
	$data = array('section' => 'mempool', 'title' => 'Memory Pool', 'content' => $content);  
 
 
// Servicenodes Page
}elseif($_GET['p'] == "servicenodes") {
	$content = createNodesContent();
	$data = array('section' => 'servicenodes', 'title' => 'Servicenodes', 'content' => $content);  
 
// Proposals Page
}elseif($_GET['p'] == "proposals") {
	$content = createGovernanceContent();
	$data = array('section' => 'proposals', 'title' => 'Proposals', 'content' => $content);

// Past proposals Page
}elseif($_GET['p'] == "pastproposals") {
	$content = createOldGovernanceContent();
	$data = array('section' => 'pastproposals', 'title' => 'Past Proposals', 'content' => $content);

// Blocks Page 
}elseif($_GET['p'] == "blocks") {
	$content= createBlocksContent();
	$data = array('section' => 'blocks', 'title' => 'Blocks', 'content' => $content);
  
// Forks Page 
}elseif($_GET['p'] == "forks") {
	$content= createForksContent();
	$data = array('section' => 'forks', 'title' => 'Forks', 'content' => $content);
  
// Open Orders Page 
}elseif($_GET['p'] == "openorders") {
	$content= createOpenOrdersContent();
	$data = array('section' => 'openorders', 'title' => 'Open Orders', 'content' => $content);
  
// Past Orders Page 
}elseif($_GET['p'] == "past1") {
	$content= createPastOrdersContent(1);
	$data = array('section' => 'pastorders', 'title' => 'Past Orders', 'content' => $content);
  
// Past Orders Page 
}elseif($_GET['p'] == "past7") {
	$content= createPastOrdersContent(7);
	$data = array('section' => 'pastorders', 'title' => 'Past Orders', 'content' => $content);
  
// Past Orders Page 
}elseif($_GET['p'] == "past14") {
	$content= createPastOrdersContent(14);
	$data = array('section' => 'pastorders', 'title' => 'Past Orders', 'content' => $content);
  
// Past Orders Page 
}elseif($_GET['p'] == "past30") {
	$content= createPastOrdersContent(30);
	$data = array('section' => 'pastorders', 'title' => 'Past Orders', 'content' => $content);
  
// Settings Page	
}elseif($_GET['p'] == "settings") {
	if(isset($_GET['c'])  AND $_GET['t'] == $_SESSION["csfrToken"]){
		if(isset($_GET['c']) AND $_GET['c'] == "geosave"){
			// Check if Geo Peer Tracing was changed
			if(isset($_POST['geopeers']) AND $_POST['geopeers'] == "on"){
				 $geoPeers = "true";
			}else{
				$geoPeers = "false";
			}

			// Write new settings in config.php
			if (file_exists('config.php')){
				$conf = file_get_contents('config.php');
				$conf = preg_replace("/geoPeers = (true|false);/i", 'geoPeers = '.$geoPeers.';', $conf);
				file_put_contents('config.php', $conf);
				$message = "Setings succesfully saved";
			}else{
				$error = "Config file does not exists";
			}				
			$message = "Settings succesfully saved";
		}
	}
   $data = array('section' => 'settings', 'title' => 'Settings', 'geoPeers' => Config::PEERS_GEO);

// About Page	
}elseif($_GET['p'] == "about") {
	$data = array('section' => 'about', 'title' => 'About'); 
	
}else{
	header('Location: index.php');
	exit; 	
}


// Create HTML output
if(isset($error)){
	$data['error'] = $error;
}
if(isset($message)){
	$data['message'] = $message;
}

$tmpl = new Template($data);
echo $tmpl->render();

?>