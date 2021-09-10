<?php

namespace App;

function getServices($hex){
	$bit = base_convert($hex, 16, 2);
	$services = [];
	
	// 1 = Network, 2 = Getutxo, 3 = Bloom, 4 = Witness, 5 = Xthin, 6 = Cash, 7 = Segwit2X, 10 = Network Limited
	// No services
	if($bit === "0"){
		$services['None'] = "None";
		return $services;
	}
	
	// Fixed length, no if(lenght < xx) necessary
	$bit = sprintf('%010d', $bit);	

	if(substr($bit, -1) == 1){
		$services['Network'] = "N";
	}
	if(substr($bit, -2, 1) == 1){
		$services['Getutxo'] = "GT";
	}  
	if(substr($bit, -3, 1) == 1){
		$services['Bloom'] = "BL";
	}
	if(substr($bit, -4, 1) == 1){
		$services['Witness'] = "WI";
	}
	if(substr($bit, -5, 1) == 1){
		$services['Xthin'] = "XT";
	}
	if(substr($bit, -6, 1) == 1){
		$services['Cash'] = "CA";
	}
	if(substr($bit, -8, 1) == 1){
		$services['Segwit2X'] = "2X";
	}
	if(substr($bit, -11, 1) == 1){
		$services['Network Limited'] = "NL";
	}	 

	// Unknown services
	if(empty($services)){
		$services['Unknown'] = "Unknown";
	}

	return $services;
}

function getVoting($hex){
	if($hex[7] == 2) {
		$vote['Segwit'] = true;
	}
	if($hex[6] == 1) {
		$vote['BIP91'] = true;
	}
	return $vote;
}
function checkInt($int){
	if(!is_numeric($int)){
		$int = 0;
	}	
	return $int;
}

function getCleanIP($ip){
	$ip = checkIpPort($ip);
	$ip = preg_replace("/:[0-9]{1,5}$/", "", $ip);
	$ip = str_replace(array('[', ']'), '', $ip);
	return $ip;
}


function checkIpBanList($ip){
	if(preg_match("/^[0-9a-z:\.]{7,39}\/[0-9]{1,3}$/", $ip)) {
		return TRUE;
	}else{
		return FALSE;
	}
}

function checkIfIpv6($ip){
	if(preg_match("/]|:/",$ip)){
		return true;
	}else{
		return false;
	}
}

function checkIpPort($ip){
	if(preg_match("/^\[{0,1}[0-9a-z:\.]{7,39}\]{0,1}:[0-9]{1,5}$/", $ip)) {
		return $ip;
	}else{
		return "unknown";
	}
}

function checkBool($bool){
	if(is_bool($bool)){
		return $bool;
	}else{
		return false;
	}
}

function checkServiceString($services){
	if(preg_match("/^[0-9a-z]{16}$/",$services)){
		return $services;
	}else{
		return "unknown";
	}
}

function checkArray($array){
	foreach ($array as $key => $value){
		if(!preg_match("/^[a-z\*]{2,11}$/",$key) OR !is_int($value)){
			unset($array[$key]);
		}
	}
	return $array;
}

function checkCountryCode($countryCode){
	if(preg_match("/^[A-Z]{2}$/", $countryCode)){
		return $countryCode;
	}else{
		return "UN";
	}
}

function checkString($string){
	$string = substr($string,0,50);
	if(preg_match("/^[0-9a-zA-Z- \.,&]{2,50}$/",$string)){
		return $string;
	}else{
		return "Unknown";
	}
}

function checkSegWitTx($size, $vsize){
	$segwit = false;
	if($size != $vsize){
		$segwit = true;
	}
	
	return $segwit;
}

function getSegWitTx($txs){
	$i = 0;
	foreach($txs as $tx){
		if(checkSegWitTx($tx["size"], $tx["vsize"])){
			$i++;
		}
	}
	return $i;
}

function checkHosted($hoster){
	$hosterList = json_decode(file_get_contents('data/hoster.json'), true);
	if (in_array($hoster, $hosterList) OR preg_match("/server/i",$hoster)){
		return true;
	}else{
		return false;
	}
}

function updateHosted($hoster, $hosted){
	$peers = file_get_contents('data/geodatapeers.inc');
	$peers = unserialize($peers);
	foreach($peers as &$peer){
		if ($peer[5] == $hoster){
			$peer[6] = $hosted;
		}
	}
	file_put_contents('data/geodatapeers.inc',serialize($peers)); 
}	

function bytesToMb($size, int $round = 1){
	$size = round(checkInt($size) / 1000000,$round);
	return $size;
}

function getDateTime($timestamp){
	$date = date("Y-m-d H:i:sT",$timestamp);	
	return $date;
}

function checkMemPoolLimited($memPoolFee, $relayTxFee){
	$result = false;
	if($memPoolFee > $relayTxFee){
		$result = true;
	}
	return $result;
}

function checkSoftFork($softForks){
	foreach($softForks as $name => &$sf){  
		if($sf['status'] === "started"){
			if(!preg_match("/[A-Za-z0-9 ]{2,25}/", $name)){
				unset($softForks[$name]);
				continue;
			}
			$sf['status'] = ucfirst(preg_replace("/[^A-Za-z]/", '', $sf['status']));
			$sf['startTime'] = date("Y-m-d",$sf['startTime']);
			$sf['timeout'] = date("Y-m-d",$sf['timeout']); 
			$sf['since'] = checkInt($sf['since']); 
			if(isset($sf['statistics'])){
				$sf['process'] = round(($sf['statistics']['count']/$sf['statistics']['period'])*100,1);
			}
		}else{
			unset($softForks[$name]);
		}
	}	
	return $softForks;
}

function getTrafficLimitSet($target){
	$result = false;
	if($target != 0) {
		$result = true;
	}
	return $result;
}

function calcMpUsage($usage, $max){
	$value = ceil(($usage/$max)*100);
	if($value == 0){
		$icon = "fa-battery-empty";
		$color = "green";
	}elseif($value <= 50){
		$icon = "fa-battery-half";
		$color = "green";
	}elseif($value > 50 AND $value < 80){
		$icon = "fa-battery-three-quarters";
		$color = "orange";
	}else{
		$icon = "fa-battery-full";
		$color = "red";		
	}
	$usageP = array('value' => $value, 'color' => $color, 'icon' => $icon);
	return $usageP;
	
}

function getBanReason($banreason){
	switch ($banreason) {
		case "manually added":
			$banreason = 'User';
			break;
		case "node misbehaving":
			$banreason = 'Auto';
			break;
		default:
			$banreason = 'Unknown';
			break;
	}
	return $banreason;
}

function getCleanClient($client){
	$client = ltrim($client,"/");
	$client = rtrim($client,"/");
	return $client;
	if(preg_match("/^Blocknet Core:([0]\.[0-9]{1,2}\.[0-9]{1,2})/",$client, $matches)) {
		$client = "Core ".$matches[1];
	}else{
		$replace = array(":", "-SNAPSHOT", "\"", "'", "<", ">", "=");
		$client = str_replace($replace, " ", $client);
	}
	return $client;
}

function checkSPV($client){	
	if (preg_match('/MultiBit|bitcoinj|bread/i',$client)){
		return true;
	}else{
		return false;
	}
}

function checkSnooping($client){	
	if (preg_match('/Snoopy|Coinscope|bitnodes/i',$client)){
		return true;
	}else{
		return false;
	}
}

function checkAltClient($client){
	if (preg_match('/Unlimited|Classic|XT|ABC|BUCash|bcoin/i',$client)){
		return true;
	}else{
		return false;
	}	
}


// Creates chart and legend (list)
function getTopClients($peers){
	$clients = [];
	$chartLabels = "";
	$chartValue = ""; 
	
	foreach($peers as $peer){
		if(isset($clients[$peer->client])){
			$clients[substr($peer->client,0,27)]['count']++;
		}else{
			$clients[substr($peer->client,0,27)]['count'] = 1;
		}
	}
	
	$peerCount = count($peers);
	$clientCount = count($clients);
	arsort($clients);
	$clients = array_slice($clients,0,9);	
	if($clientCount > 9){
		$clients['Other']['count'] = $clientCount-9;
	}
	
	
	foreach($clients as $cName => &$client){
		$chartLabels .= '"'.$cName.'",';
		$chartValue .= $client['count'].',';
		$client['share'] = round($client['count']/$peerCount,2)*100;
	}
	
	$chartData['labels'] = rtrim($chartLabels, ",");
	$chartData['values'] = rtrim($chartValue, ",");
	$chartData['legend'] = $clients;

	return $chartData;
}


function getMostPop($peers){
	$segWitCount = 0;
	$clCountAr = [];
	$ctCountAr = [];
	$htCountAr = [];
	$result = [];
	
	foreach($peers as $peer){
		// Count Witness
		if(isset($peer->services['Witness']) AND $peer->services['Witness']){
			$segWitCount++;
		}
		
		// Count Client 1
		if(array_key_exists($peer->client,$clCountAr)){
			$clCountAr[$peer->client]++;
		}else{
			$clCountAr[$peer->client] = 1;
		}
		
		if(CONFIG::PEERS_GEO){
			// Count Country 1
			if(array_key_exists($peer->country,$ctCountAr)){
				$ctCountAr[$peer->country]++;
			}else{
				$ctCountAr[$peer->country] = 1;
			}
			
			// Count ISP 1
			if(array_key_exists($peer->isp,$htCountAr)){
				$htCountAr[$peer->isp]++;
			}else{
				$htCountAr[$peer->isp] = 1;
			}			
		}
	}
	
	// Count Client 2
	arsort($clCountAr);
	$result['mpCli'] = substr(key($clCountAr),9,10);
	$result['mpCliC'] = reset($clCountAr);
	
	if(CONFIG::PEERS_GEO){
		// Count Country 2
		arsort($ctCountAr);
		$result['mpCou'] = key($ctCountAr);
		$result['mpCouC'] = reset($ctCountAr);
		
		// Count ISP 2
		arsort($htCountAr);
		$result['mpIsp'] = substr(key($htCountAr),0,14);
		$result['mpIspC'] = reset($htCountAr);
	}

	$result['segWitC'] = $segWitCount;
	return $result;
}


// Peer functions

function getPeerData(bool $geo = NULL){
	global $blocknetd;
	
	// If not set, use config setting
	if(is_null($geo)){
		$geo = CONFIG::PEERS_GEO;
	}
	
	$peerInfo = $blocknetd->getpeerinfo(); 
	if($geo){
		$peers = createPeersGeo($peerInfo);
	}else{
		$peers = createPeers($peerInfo);
	}
	
	return $peers;
}

function createPeers($peerinfo){
	global $trafficC, $trafficCIn, $trafficCOut;
	
	foreach($peerinfo as $peer){
		$peerObj = new Peer($peer);
		$peers[] = $peerObj;
		$trafficC += $peerObj->traffic;
		$trafficCIn += $peerObj->trafficIn;
		$trafficCOut += $peerObj->trafficOut;
	}
	return $peers;
}

function createPeersGeo($peerinfo){
	global $countryList;
	global $trafficC, $trafficCIn, $trafficCOut;
	global $hosterCount;
	global $privateCount;
	global $newPeersCount;
	
	$noGeoData = false;
	
	// Check if peer file exists and enabled
	if (file_exists('data/geodatapeers.inc')){
		// Loads serialized stored peers from disk
		$serializedPeers = file_get_contents('data/geodatapeers.inc');
		$arrayPeers = unserialize($serializedPeers);
		// Check if client was restarted and IDs reassigned
		$oldestPeerId = reset($peerinfo)["id"];
		$oldestPeerIp = getCleanIP(reset($peerinfo)["addr"]);
		$delete = false;
		// Checks if we know about the oldest peer, if not we assume that we don't known any peer
		foreach($arrayPeers as $key => $peer){
			if($oldestPeerIp == $peer[0]){
				$delete = true;
				// Either blocknetd was restarted or peer reconnected. Since peer is the oldest, all other peers we known disconnected
				if($oldestPeerId != $key){
					$delete = false;
				}
				break;
			}
			// For removing old peers that disconnected. Value of all peers that are still conected will be changed to 1 later. All peers with 0 at the end of the function will be deleted.
			$arrayPeers[$key][7] = 0;
		}
		// Oldest peer hasn't shown up -> Node isn't connected to any of the previously stored peers
		if(!$delete){
			unset($arrayPeers);
			$noGeoData = true;
		}
	}else{
		$noGeoData = true;
	}
	
	// Find Ips that we don't have geo data for and that are "older" than 2 minutes
	// First interation through all peers is used to collect ips for geo api call. This way the batch functionality can be used
	$ips = [];
	foreach($peerinfo as &$peer){
		$tempIP = getCleanIP($peer['addr']);
		$age = round((time()-$peer["conntime"])/60);
		if ($age >  2 AND ($noGeoData OR !in_array($tempIP,array_column($arrayPeers,0)))){
			$ips[] = $tempIP;
		}
	}
	unset($peer);
	
	if(!empty($ips)){
		$ipData = getIpData($ips);
	}
	// 2nd interation through peers to create final peer list for output
	foreach($peerinfo as $peer){
		// Creates new peer object
		$peerObj = new Peer($peer);

		// Checks if peer is new or if we can read data from disk (geodatapeers.inc)
		if($noGeoData OR !in_array($peerObj->ip,array_column($arrayPeers,0))){	   
			if(isset($ipData[0]) AND $peerObj->age > 2){
				// Only counted for peers older than 2 minutes
				$newPeersCount++;
				
				$countryInfo = $ipData[array_search($peerObj->ip, array_column($ipData, 'query'))];
				$countryCode = checkCountryCode($countryInfo['countryCode']);
				$country = checkString($countryInfo['country']);
				$region = checkString($countryInfo['regionName']);
				$city = checkString($countryInfo['country']);
				$isp = checkString($countryInfo['isp']);		 
				$hosted = checkHosted($isp);
				// Adds the new peer to the save list
				$arrayPeers[$peerObj->id] = array($peerObj->ip, $countryCode, $country, $region, $city, $isp, $hosted, 1);
			}elseif($peerObj->age > 2){
				// If IP-Api.com call failed we set all data to Unknown and don't store the data
				$countryCode = "UN";
				$country = "Unknown";
				$region = "Unknown";
				$city = "Unknown";
				$isp = "Unknown";		 
				$hosted = false;
				// Only counted for peers older than 2 minutes
				$newPeersCount++;				
			}else{
				// If peer is younger than 2 minutes
				$countryCode = "NE";
				$country = "New";
				$region = "New";
				$city = "New";
				$isp = "New";		 
				$hosted = false;				
				
			}

		}else{
			$id = $peerObj->id;
			// Nodes that we know about but reconnected
			if(!isset($arrayPeers[$id])){
				$id = array_search($peerObj->ip, array_column($arrayPeers,0));
				$id = array_keys($arrayPeers)[$id];
			}
			$countryCode = $arrayPeers[$id][1];
			$country = $arrayPeers[$id][2];
			$region = $arrayPeers[$id][3];
			$city = $arrayPeers[$id][4];
			$isp = $arrayPeers[$id][5];
			$hosted = $arrayPeers[$id][6];
			$arrayPeers[$id][7] = 1;
		}

		// Counts the countries
		if(isset($countryList[$country])){	   
			$countryList[$country]['count']++;
		}else{
			$countryList[$country]['code'] = $countryCode;
			$countryList[$country]['count'] = 1;
		}

		// Adds country data to peer object
		$peerObj->countryCode = $countryCode;
		$peerObj->country = $country;
		$peerObj->region = $region;
		$peerObj->city = $city;
		$peerObj->isp = $isp;
		$peerObj->hosted = $hosted;
		if($hosted){
			$hosterCount++;
		}else{
			$privateCount++;
		}
		// Adds traffic of each peer to total traffic (in MB)
		$trafficC += $peerObj->traffic;
		$trafficCIn += $peerObj->trafficIn;
		$trafficCOut += $peerObj->trafficOut;
	
		// Adds peer to peer array
		$peers[] = $peerObj;
	}

	// Removes all peers that the node is not connected to anymore.
	foreach($arrayPeers as $key => $peer){
		if($peer[7] == 0){
			unset($arrayPeers[$key]);
		}
	}

	$newSerializePeers = serialize($arrayPeers);
	file_put_contents('data/geodatapeers.inc', $newSerializePeers);
		
	return $peers;
}

// Nodes functions

function getNodesData(bool $geo = NULL){
	global $blocknetd;
	
	// If not set, use config setting
	if(is_null($geo)){
		$geo = CONFIG::NODES_GEO;
	}
	
	$snodeInfo = $blocknetd->servicenodelist(); 
	if($geo){
		$nodes = createNodesGeo($snodeInfo);
	}else{
		$nodes = createNodes($snodeInfo);
	}
	
	return $nodes;
}

function createNodes($snodeinfo){
	
	foreach($snodeinfo as $snode){
		$snodeObj = new SNode($snode);
		$nodes[] = $snodeObj;
	}
	return $nodes;
}

//

function createNodesGeo($nodes){
	global $countryList;
	global $hosterCount;
	global $privateCount;
	global $newNodesCount;
	
	$noGeoData = false;
	
	// Check if peer file exists and enabled
	if (file_exists('data/geodatanodes.inc')){
		// Loads serialized stored nodes from disk
		$serializedNodes = file_get_contents('data/geodatanodes.inc');
		$arrayNodes = unserialize($serializedNodes);
	}else{
		$noGeoData = true;
	}
	// Find IPs that we don't have geo data for
	// First interation through all nodes is used to collect IPs for geo api call. This way the batch functionality can be used
	$ips = [];
	foreach($nodes as &$node){
		if ($noGeoData OR !in_array($node["IP"],array_column($arrayNodes,0))){
			$ips[] = $node["IP"];
		}
	}
	unset($node);
	
	if(!empty($ips)){
		$ipData = getIpData($ips);
	}
	// 2nd interation through nodes to create final node list for output
	foreach($nodes as $node){
		// Creates new node object
		$nodeObj = new MSNode($node);
		// Checks if node is new or if we can read data from disk (geodatanodes.inc)
		if($noGeoData OR !in_array($nodeObj->IP,array_column($arrayNodes,0))){	   
			if(isset($ipData[0])){
				$newNodesCount++;
				
				$countryInfo = $ipData[array_search($nodeObj->IP, array_column($ipData, 'query'))];
				$countryCode = checkCountryCode($countryInfo['countryCode']);
				$country = checkString($countryInfo['country']);
				$region = checkString($countryInfo['regionName']);
				$city = checkString($countryInfo['country']);
				$isp = checkString($countryInfo['isp']);		 
				$hosted = checkHosted($isp);
				// Adds the new node to the save list
				$arrayNodes[$nodeObj->id] = array($nodeObj->IP, $countryCode, $country, $region, $city, $isp, $hosted, 1);
			}else{
				// If IP-Api.com call failed we set all data to Unknown and don't store the data
				$countryCode = "UN";
				$country = "Unknown";
				$region = "Unknown";
				$city = "Unknown";
				$isp = "Unknown";		 
				$hosted = false;
				// Only counted for peers older than 2 minutes
				$newNodesCount++;				
			}
		}else{
			$id = $nodeObj->id;
			// Nodes that we know about but reconnected
			if(!isset($arrayNodes[$id])){
				$id = array_search($nodeObj->IP, array_column($arrayNodes,0));
				$id = array_keys($arrayNodes)[$id];
			}
			$countryCode = $arrayNodes[$id][1];
			$country = $arrayNodes[$id][2];
			$region = $arrayNodes[$id][3];
			$city = $arrayNodes[$id][4];
			$isp = $arrayNodes[$id][5];
			$hosted = $arrayNodes[$id][6];
			$arrayNodes[$id][7] = 1;
		}

		// Counts the countries
		if(isset($countryList[$country])){	   
			$countryList[$country]['count']++;
		}else{
			$countryList[$country]['code'] = $countryCode;
			$countryList[$country]['count'] = 1;
		}

		// Adds country data to node object
		$nodeObj->countryCode = $countryCode;
		$nodeObj->country = $country;
		$nodeObj->region = $region;
		$nodeObj->city = $city;
		$nodeObj->isp = $isp;
		$nodeObj->hosted = $hosted;
		if($hosted){
			$hosterCount++;
		}else{
			$privateCount++;
		}
		// Adds node to nodes array
		$newnodes[] = $nodeObj;
	}

	$newSerializeNodes = serialize($arrayNodes);
	file_put_contents('data/geodatanodes.inc', $newSerializeNodes);
	return $newnodes;
}

function getIpData($ips){
	global $error;
	
	$numOfIps = count($ips);
	// Check up to 1500 IPs. MN/SN count are each around 1000 so we have some headroom here.
	// Rate limit by ip-api.com is 4500 per min (45 x 100) if batch requests are used.
	if($numOfIps > 1500){
		$numOfIps = 1500;
	}	
	$j = 0;
	// A mamxium of 100 IPs can be checked per API call (limit by ip-api.com)
	$m = 100;
	// Creates Postvar data with a maximum of 100 IPs per request
	while($j < $numOfIps){
		if($numOfIps-$j < 100){
			$m=$numOfIps-$j;
		}
		for($i = 0; $i < $m; $i++){
			$postvars[$j][] =  array("query" => $ips[$i+$j]);
		}
		$j += $i;
	}
	// Curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_URL,'http://ip-api.com/batch?fields=query,country,countryCode,regionName,city,isp,hosting');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , CONFIG::GEO_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, CONFIG::GEO_TIMEOUT+1);
	
	// One call for each 100 IPs
	$geojsonraw = [];
	foreach($postvars as $postvar){
		$postvarJson = json_encode($postvar);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $postvarJson);
		$result = json_decode(curl_exec($ch),true);
		if(empty($result)){
			$error = "Geo API (ip-api.com) Timeout";
			$result = [];
		}
		$geojsonraw = array_merge($geojsonraw, $result);
	}
	return $geojsonraw;
}

function createMapJs(int $peerCount){
	global $countryList;
	
	// Sorting country list
	function compare($a, $b)
	{
		return $b['count'] - $a['count'];
	}
	uasort($countryList, "App\compare");

	$i = 0;
	$jqvData = 'var peerData = {';
	$mapDesc = [];

	// Creates map Legend. Top 9 countries + Others
	foreach($countryList as $countryName => $country){
		$jqvData .= "\"".strtolower($country['code'])."\":".$country['count'].",";
		
		if($i<9){
			$mapDesc[$countryName] = $country;		   
			$i++;
		}else{
			if(isset($mapDesc['Other']['count'])){
				$mapDesc['Other']['count']++;
			}else{
				$mapDesc['Other']['count'] = 1;
			}
		}
	}
	
	foreach($mapDesc as &$country){
		$country['share'] = round($country['count']/$peerCount,2)*100;
	}
	
	$jqvData = rtrim($jqvData, ",");
	$jqvData .= '};';
	
	// Writes data file for JVQMap
	//file_put_contents('data/countries.js',$jqvData);
	$map['data'] = $jqvData;
	$map['desc'] = $mapDesc;
	
	return $map;
}

function secondsToHuman($number_of_seconds, $out_seconds = True, $out_minutes = True, $out_hours = True){
    $human_string = "";

    $weeks = 0;
    $days = 0;
    $hours = 0;
    if($number_of_seconds > 604800) {
        # weeks
        $weeks = intdiv($number_of_seconds, 604800);
        $number_of_seconds = $number_of_seconds - ($weeks * 604800);
        $elem_str = $weeks . ' week';
        if($weeks > 1) {
			$elem_str .= 's';
		}
		$human_string .= $elem_str . ' ';
	}

    if($number_of_seconds > 86400) {
        # days
        $days = intdiv($number_of_seconds, 86400);
        $number_of_seconds = $number_of_seconds - ($days * 86400);
        $elem_str = $days . ' day';
        if($days > 1) {
			$elem_str .= 's';
		}
		$human_string .= $elem_str . ' ';
	}

    if($out_hours and $number_of_seconds > 3600) {
        $hours = intdiv($number_of_seconds, 3600);
        $number_of_seconds = $number_of_seconds - ($hours * 3600);
        $elem_str = $hours . ' hour';
        if($hours > 1) {
			$elem_str .= 's';
		}
		$human_string .= $elem_str . ' ';
	}
	
	if($out_minutes and $number_of_seconds > 60) {
		$minutes = intdiv($number_of_seconds, 60);
		$number_of_seconds = $number_of_seconds - ($minutes * 60);
		$elem_str = $minutes . ' minute';
		if($minutes > 1) {
			$elem_str .= 's';
		}
		$human_string .= $elem_str . ' ';
	}
	
	if($out_seconds and $number_of_seconds > 0) {
		$elem_str = $number_of_seconds + ' second';
		if($number_of_seconds > 1) {
			$elem_str .= 's';
		}
		$human_string .= $elem_str;
	}
	
	return $human_string;
}

function chainzAPI($query){
	// Curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, "https://chainz.cryptoid.info/block/api.dws?q=".$query);
	$result = json_decode(curl_exec($ch),true);
	if(empty($result)){
			print("Chainz API timeout ");
			$result = [];
	}
	return $result;
}

function dbupdate($doit = 0){
	global $db;
	try{
		$dbversion = $db->querySingle('SELECT "dbversion" FROM "events"') or $dbversion = 0;
	} catch (\Exception $e) {
		print($e);
		$dbversion = 0;
	}
	if($doit <> 0){
	    if($dbversion == 0){
			$db->exec('BEGIN TRANSACTION');
    		$db->exec('ALTER TABLE "events" ADD COLUMN "dbversion" INTEGER');
            $db->exec('ALTER TABLE "events" ADD COLUMN "lastproposal" INTEGER');
	    	$db->exec('UPDATE "events" SET "dbversion" = 1');
			$db->exec('COMMIT');
            $content['newVersion'] = 1;
	    }
		if($dbversion == 1){
			$db->exec('BEGIN TRANSACTION');
			$db->exec('CREATE TABLE IF NOT EXISTS "servicenodes"(
				"nodepubkey" VARCHAR PRIMARY KEY NOT NULL,
				"status" VARCHAR NOT NULL,
				"score" INTEGER NOT NULL,
				"banned" BOOLEAN NOT NULL DEFAULT FALSE,
				"address" VARCHAR NOT NULL,
				"payment_address" VARCHAR NOT NULL,
				"tier" VARCHAR NOT NULL DEFAULT "SPV",
				"xr" BOOLEAN NOT NULL DEFAULT FALSE,
				"exr" BOOLEAN NOT NULL DEFAULT FALSE,
				"timelastseen" INTEGER NOT NULL DEFAULT 0,
				"updated" INTEGER NOT NULL DEFAULT 0,
				"fee_default" INTEGER NOT NULL DEFAULT 0,
				"ip_addr" VARCHAR,
				"config" VARCHAR
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "coins"(
				"coin" VARCHAR PRIMARY KEY NOT NULL,
				"name" VARCHAR,
				"latest_version" VARCHAR
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "xrServices"(
				"nodepubkey" VARCHAR,
				"xrservice" VARCHAR NOT NULL,
				"coin" VARCHAR NOT NULL,
				"fee" NUMBER,
				"payment_address" VARCHAR NOT NULL,
				"request_limit" INTEGER,
				"fetch_limit" INTEGER,
				"timeout" INTEGER,
				"disabled" BOOLEAN,
				"updated" INTEGER NOT NULL,
				FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey"),
				FOREIGN KEY ("coin") REFERENCES "spvWallets" ( "coin" )
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "xcServices"(
				"nodepubkey" VARCHAR,
				"xcservice" VARCHAR NOT NULL,
				"parameters" VARCHAR,
				"fee" NUMBER,
				"payment_address" VARCHAR NOT NULL,
				"request_limit" INTEGER,
				"fetch_limit" INTEGER,
				"timeout" INTEGER,
				"disabled" BOOLEAN,
				"description" VARCHAR,
				"updated" INTEGER NOT NULL,
				FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey")
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "spvWallets"(
				"coin" VARCHAR,
				"nodepubkey" VARCHAR,
		        FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey")
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "spvConfigs"(
				"coin" VARCHAR ,
				"nodepubkey" VARCHAR,
				"xrservice" VARCHAR,
				"fee" INTEGER NOT NULL,
				"paymentAddress" VARCHAR NOT NULL,
				"requestLimit" INTEGER NOT NULL,
				"fetchLimit" INTEGER NOT NULL,
				"timeout" INTEGER NOT NULL,
				"disabled" BOOLEAN NOT NULL,
				"lastUpdated" INTEGER,
				FOREIGN KEY ("coin") REFERENCES "coins" ("coin"),
				FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey"),
				FOREIGN KEY ("xrservice") REFERENCES "xrServices" ("xrservice")
			)');
		
			$db->exec('CREATE TABLE IF NOT EXISTS "fees"(
				"nodepubkey" VARCHAR,
				"xrservice" VARCHAR,
				"fee" INTEGER NOT NULL,
				FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey"),
				FOREIGN KEY ("xrservice") REFERENCES "xrServices" ("xrservice")
			)');
	    	$db->exec('UPDATE "events" SET "dbversion" = 2');
			$db->exec('COMMIT');
            $content['newVersion'] = 2;
        }
		if($dbversion == 2){
			$db->exec('BEGIN TRANSACTION');
			$db->exec('ALTER TABLE "spvWallets" RENAME TO "dxWallets"');
            $db->exec('ALTER TABLE "xrServices" RENAME TO "old_xrServices"');
			$db->exec('CREATE TABLE "xrServices"(
				"nodepubkey" VARCHAR,
				"xrservice" VARCHAR NOT NULL,
				"coin" VARCHAR NOT NULL,
				"fee" NUMBER,
				"payment_address" VARCHAR NOT NULL,
				"request_limit" INTEGER,
				"fetch_limit" INTEGER,
				"timeout" INTEGER,
				"disabled" BOOLEAN,
				"updated" INTEGER NOT NULL,
				FOREIGN KEY ("nodepubkey") REFERENCES "servicenodes" ("nodepubkey")
			)');
	        $db->exec('INSERT INTO "xrServices" SELECT * FROM "old_xrServices"');
		    $db->exec('DROP TABLE "old_xrServices"'); 
	    	$db->exec('UPDATE "events" SET "dbversion" = 3');
			$db->exec('COMMIT');
            $content['newVersion'] = 3;
        }
		if($dbversion == 3){
			$db->exec('BEGIN TRANSACTION');
			$db->exec('CREATE VIEW "spvwallets" AS SELECT "coin", "nodepubkey" FROM "xrservices" GROUP BY "nodepubkey","coin" ORDER BY "coin"');
	    	$db->exec('UPDATE "events" SET "dbversion" = 4');
			$db->exec('COMMIT');
            $content['newVersion'] = 4;
        }
		if($dbversion == 4){
            $content['newVersion'] = 4;
        }
	}
    $content['oldVersion'] = $dbversion;
    return $content;
}

?>