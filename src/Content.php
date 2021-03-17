<?php

namespace App;

function createMainContent(){
	global $blocknetd, $trafficCIn, $trafficCOut, $newPeersCount;

	$blockReward = 1;
	$blocksPerDay = 1440;
	$poolPerDay = $blockReward*$blocksPerDay;
	$mnCollateral = 5000;
	
	$peers = getPeerData();
	$peerCount = count($peers);
	$snodes = getNodesData();
	$snodeCount = count($snodes);
	$banListInfo = createBanListContent();

	$content = [];
	$content['bannedPeers'] = $banListInfo['totalBans'];
	$content['last24h'] = $banListInfo['lastCount'];
	$nodecounts = $blocknetd->servicenodecount();
	$content['totalNodes'] = $nodecounts["total"];
	$content['onlineNodes'] = $nodecounts["online"];

	
	$content['nextSuperblock'] = $blocknetd->nextsuperblock();
	$content['node'] = new Node();
	if(Config::PEERS_GEO){
		$content['map'] = createMapJs($peerCount);
	}
	$content['geo'] = Config::PEERS_GEO;
	$content['nPeers'] = $newPeersCount;
	$content['chartData'] = getTopClients($peers);

	// Current peers traffic
	$content['trafcin'] = round($trafficCIn/1000, 2);
	$content['trafcout'] = round($trafficCOut/1000, 2);

	// Current price info
	$content['priceInfo'] = getPriceInfo();
	$txoutset = $blocknetd->gettxoutsetinfo();
	$content['issued'] = floor($txoutset['total_amount']);
	$content['marketCap'] = round($txoutset['total_amount'] * $content['priceInfo']['BLOCK/USD'], 0);

	// Open orders count
	$openorders = $blocknetd->dxGetOrders();
	$content['openOrders'] = count($openorders);

	// Completed orders
	$completedorders = $blocknetd->dxGetTradingData(1440);
	$content['recentOrders'] = count($completedorders);

	return $content;
	
}

function createPeerContent(){
	global $trafficC, $trafficCIn, $trafficCOut, $blocknetd, $newPeersCount;

	$peers = getPeerData();
	$netinfo = $blocknetd->getnettotals();

	$content = getMostPop($peers);
	$content['peers'] = $peers;
	$content['tPeers'] = count($peers);
	$content['nPeers'] = $newPeersCount;
	$content['segWitP'] = round($content['segWitC']/$content['tPeers'],2)*100;
	$content['cTraf'] = round($trafficC/1000,2);
	$content['trafcin'] = round($trafficCIn/1000,2);
	$content['trafcout'] = round($trafficCOut/1000,2);
	$content['tTraf'] = ($netinfo["totalbytesrecv"] + $netinfo["totalbytessent"])/1000000;
	$content['cTrafP'] = round($content['cTraf']/$content['tTraf'],2)*100;
	$content['geo'] = Config::PEERS_GEO;

	return $content;
}

function getPriceInfo(){
	date_default_timezone_set ('UTC');

	$bittrex = new \ccxt\bittrex (array (
    	'verbose' => false,
    	'timeout' => 30000,
    	'enablerateLimit' => true,
	));

	$result = array('BLOCK/BTC' => 'N/A', 'BTC/USD' => 'N/A', 'BLOCK/USD' => 'N/A');
	try {
    	$p1 = $bittrex->fetch_ticker("BLOCK/BTC")['last'];
    	$p2 = $bittrex->fetch_ticker("BTC/USD")['last'];
    } catch (\ccxt\NetworkError $e) {
		echo '[Network Error] ' . $e->getMessage () . "\n";
		return $result;
	} catch (\ccxt\ExchangeError $e) {
    	echo '[Exchange Error] ' . $e->getMessage () . "\n";
		return $result;
	} catch (Exception $e) {
    	echo '[Error] ' . $e->getMessage () . "\n";
		return $result;
	}
	$result['BLOCK/BTC'] = $p1 * 1E8;
	$result['BTC/USD'] = round($p2, 2);
	$result['BLOCK/USD'] = round($p1 * $p2, 2);
	return $result;
}


function createBanListContent(){
	global $blocknetd, $error;

    // Crown doesn't (yet) support listbanned RPC, fake empty result
	//$banlist = $blocknetd->listbanned();
    $banlist = [];

	$content = [];
	$lastCount = 0;
	$autoCount = 0;
	$autoPerc = 0;
	$userCount = 0;
	$userPerc  = 0;
	$avgTime = 0;
	$settCore = 0;

	// Total Bans
	$totalBans = count($banlist);

	foreach($banlist as &$ban){
		// In last 24h
		if($ban['ban_created'] >= time()-86400){
			$lastCount++;
		}
		 // Auto/User Ban Count
		$ban['ban_reason'] = getBanReason($ban['ban_reason']);
		if($ban['ban_reason'] == "Auto"){
			$autoCount++;
		}else{
			$userCount++;
		}

		// Sum up all ban time
		$avgTime += $ban['banned_until']-$ban['ban_created'];

		// Calculate Core ban time settings (only done once)
		if($settCore == 0){
			if($ban['ban_reason'] == "Auto"){
			   $settCore = (int)$ban['banned_until'] - (int)$ban['ban_created'];
			}
		}

		$ban['ban_duration'] = round(($ban['banned_until'] - $ban['ban_created'])/86400,1);
		$ban['ban_created'] = getDateTime($ban['ban_created']);
		$ban['banned_until'] = getDateTime($ban['banned_until']);
		if(!checkIpBanList($ban['address'])){
			$error = "Invalid ban list IP";
			return false;
		}
		$ban['ipv6'] = checkIfIpv6($ban['address']);
	}

	// Calculate and format average ban time
	$content['avgTime'] = 0; // after codebase update round($avgTime/(86400*$totalBans),0);

	// Calculate percentage auto/user bans
	$content['autoCount'] = $autoCount;
	$content['userCount'] = $userCount;
	$content['autoPer'] = 0; // after codebase update  round($autoCount/$totalBans,2)*100;
	$content['userPer'] = 0; // after codebase update  round($userCount/$totalBans,2)*100;

	$content['totalBans'] = $totalBans;
	$content['lastCount'] = $lastCount;

	// Setting Core Setting and check if default
	$content['settCore'] = $settCore/86400;
	if($content['settCore'] != 1){
		$content['settCoreMode'] = "Custom";
	}else{
	   $content['settCoreMode'] = "Default";
	}

	// List of all banned peers
	$content['banList'] = $banlist;
	

	return $content;
}

function createBlocksContent(){
	global $blocknetd;

	$content = [];
	$content["totalTx"] = 0;
	$content["totalFees"] = 0;
	$content["totalSize"] = 0;
	$content["segwitCount"] = 0;
	$blocktime = 60;

	$blockHash = $blocknetd->getbestblockhash();

	for($i = 0; $i < Config::DISPLAY_BLOCKS; $i++){
		$block = $blocknetd->getblock($blockHash);
		if($i==0){ 
			$content["latest"] = $block["height"];
		}
		$content["blocks"][$block["height"]]["hash"] = $block["hash"];
		$content["blocks"][$block["height"]]["size"] = round($block["size"]/1000,2);
		$content["totalSize"] += $block["size"];
		$content["blocks"][$block["height"]]["versionhex"] = "N/A";
		$content["blocks"][$block["height"]]["voting"] = "N/A";
		$content["blocks"][$block["height"]]["time"] = getDateTime($block["time"]);
		$content["blocks"][$block["height"]]["timeago"] = round((time() - $block["time"])/60);
		$content["blocks"][$block["height"]]["coinbasetx"] = $block["tx"][0];
		$content["blocks"][$block["height"]]["coinstaketx"] = $block["tx"][1];
		$coinbaseTx = $blocknetd->getrawtransaction($block["tx"][0], 1);
		$coinstakeTx = $blocknetd->getrawtransaction($block["tx"][1], 1);
		$coinbase = $coinbaseTx["vout"][1]["value"];
		$coinstake = $coinstakeTx["vout"][0]["value"];
		// $superblock = $block["height"] % $sbinterval == 0;
		// if($superblock){
		// 	$content["blocks"][$block["height"]]["fees"] = 0;
		// }else{
			$content["blocks"][$block["height"]]["fees"] = round($coinbase + $coinstake, 5);
		// }
		$content["blocks"][$block["height"]]["fees"] = $coinbase;
		$content["totalFees"] += $content["blocks"][$block["height"]]["fees"];
		$content["blocks"][$block["height"]]["txcount"] = count($block["tx"]);
		$content["totalTx"] += $content["blocks"][$block["height"]]["txcount"];
		$blockHash = $block["previousblockhash"];
	}
	$content["avgTxSize"] = round(($content["totalSize"]/($content["totalTx"]))/1000,2);
	$content["avgSize"] = round($content["totalSize"]/(Config::DISPLAY_BLOCKS*1000),2);
	$content["totalSize"] = round($content["totalSize"]/1000000,2);
	$content["avgFee"] = round($content["totalFees"]/Config::DISPLAY_BLOCKS,2);
	$content["totalFees"] = round($content["totalFees"],2);
	$content["numberOfBlocks"] = Config::DISPLAY_BLOCKS;
	$content["timeframe"] = round(end($content["blocks"])["timeago"]/$blocktime,0);

	return $content;
}

function createForksContent(){
	global $blocknetd;

	$content["recentForks"] = 0;	// Count forks in last 24h

	$forks = $blocknetd->getchaintips();
	$i = 0;
	$lastTime = 0;

	foreach($forks as $fork){
		if($i == Config::DISPLAY_FORKS){
			break;
		}

		$content["blocks"][$i]["height"] = $fork["height"];
		$content["blocks"][$i]["hash"] = $fork["hash"];
		$content["blocks"][$i]["forklength"] = $fork["branchlen"];
		$content["blocks"][$i]["status"] = $fork["status"];

		if($fork["status"] != "headers-only" AND $fork["status"] != "unknown"){
			$block = $blocknetd->getblock($fork["hash"]);
			$content["blocks"][$i]["size"] = round($block["size"]/1000,2);
			//$content["blocks"][$i]["versionhex"] = $block["versionHex"];
			//$content["blocks"][$i]["voting"] = getVoting($block["versionHex"]);
			$content["blocks"][$i]["time"] = getDateTime($block["time"]);
			$lastTime = $block["time"];
			$content["blocks"][$i]["timeago"] = round((time() - $block["time"])/3600);
			$content["blocks"][$i]["txcount"] = count($block["tx"]);

			if($content["blocks"][$i]["timeago"] <= 24){
				$content["recentForks"]++;
			}
		}
		$i++;
	}

	$content["timeframe"] = round((time()-$lastTime)/3600);
	$content["forkCount"] = Config::DISPLAY_FORKS - 1;	// Don't count most recent block as a fork
	$content["recentForks"]--;	// Don't count most recent block as a fork

	return $content;
}

/**
 * @param null $editID
 * @return mixed
 */
function createRulesContent($editID = NULL){

	$rulesContent['rules'] = Rule::getRules();
	$rulesContent['jobToken'] = substr(hash('sha256', CONFIG::PASSWORD."ebe8d532"),0,24);
	$rulesContent['editRule'] = new Rule();

	if (file_exists('data/rules.log')){
		$log = file_get_contents('data/rules.log');
	}else{
		$log = "No logs available";
	}
	$rulesContent['log'] = $log;


	if(!is_null($editID)){
		$response = Rule::getByID($_GET['id']);
		if($response != FALSE){
			$rulesContent['editRule'] = $response;
		// TODO: Return repsonse to controller
		}else{
			$error = "Couldn't find Rule!";
		}
	}

	return $rulesContent;
}

function createMempoolContent(){
	global $blocknetd;

	$content['txs'] = $blocknetd->getrawmempool(TRUE);
	$content['txs'] = array_slice($content['txs'], 0, CONFIG::DISPLAY_TXS);
	$content['node'] = new Node();

	return $content;
}

function createNodesContent(){
	global $blocknetd, $newNodesCount;

	$nodes = getNodesData();
	$counts = $blocknetd->servicenodecount();

	$content['nodes'] = $nodes;
	$content['totalNodes'] = $counts["total"];
	$content['onlineNodes'] = $counts["online"];
	$content['nNodes'] = $newNodesCount;
	$content['geo'] = Config::NODES_GEO;

	return $content;
}

function createGovernanceContent(){
	global $blocknetd;
	$content["nextSuperblock"] = $blocknetd->nextsuperblock();
	$proposals = $blocknetd->listproposals($content["nextSuperblock"]-43200+1);
	$mnCount = $blocknetd->servicenodecount()["total"];
	$currentBlock = $blocknetd->getblockcount();
	$content["nextDate"] = "Estimated " . date("D j F Y H:i", time()+($content["nextSuperblock"]-$currentBlock)*60);
	$maxBudget = 40000;
	$content["budgetRequested"] = 0;
	$content["budgetPassing"] = 0;
	$content["budgetRemaining"] = $maxBudget;
	$content["pCount"] = 0;
	$content["passingCount"] = 0;
	$i = 0;
    foreach($proposals as $proposal){
		$blockStart = $proposal["superblock"];
		$blockEnd = $blockStart;
		//if($currentBlock <= $blockEnd)
		{
			$content["proposal"][$i]["hash"] = $proposal["hash"];
			$content["proposal"][$i]["name"] = $proposal["name"];
			$content["proposal"][$i]["superblock"] = $proposal["superblock"];
			$content["proposal"][$i]["amount"] = $proposal["amount"];
			$content["proposal"][$i]["address"] = $proposal["address"];
			$content["proposal"][$i]["URL"] = $proposal["url"];
			$content["proposal"][$i]["description"] = $proposal["description"];
			$content["proposal"][$i]["yeas"] = $proposal["votes_yes"];
			$content["proposal"][$i]["nays"] = $proposal["votes_no"];
			$content["proposal"][$i]["abstains"] = $proposal["votes_abstain"];
			$content["proposal"][$i]["status"] = $proposal["status"];
			$content["budgetRequested"] += $proposal["amount"];
			$content["proposal"][$i]["passingMargin"] = ($proposal["votes_yes"]-$proposal["votes_no"]-$proposal["votes_abstain"]);
			if($content["proposal"][$i]["passingMargin"] > $mnCount / 10) {
				$content["proposal"][$i]["passing"] = "Yes";
				$content["budgetPassing"] += $proposal["amount"];
				$content["passingCount"] += 1;
			}else{
				$content["proposal"][$i]["passing"] = "No";
			}
			$i++;			
		}
	}
	$content["pCount"] = $i;
	$content["budgetRemaining"] -= $content["budgetRequested"];
	if($content["budgetRequested"] > $maxBudget){
		$content["reqColour"] = "red";
	}elseif($content["budgetRequested"] > $maxBudget * 0.9){
		$content["reqColour"] = "orange";
	}else{
		$content["reqColour"] = "green";
	}
	if($content["budgetPassing"] > $maxBudget){
		$content["passingColour"] = "red";
	}elseif($content["budgetPassing"] > $maxBudget * 0.9){
		$content["passingColour"] = "orange";
	}else{
		$content["passingColour"] = "green";
	}
	if($content["budgetRemaining"] < 0){
		$content["remainingColour"] = "red";
	}elseif($content["budgetRemaining"] < $maxBudget * 0.1){
		$content["remainingColour"] = "orange";
	}else{
		$content["remainingColour"] = "green";
	}
	return $content;
}

function createOldGovernanceContent(){
	global $blocknetd;
	$content["nextSuperblock"] = $blocknetd->nextsuperblock();
	$proposals = $blocknetd->listproposals(1339200);
	$currentBlock = $blocknetd->getblockcount();
	$content["nextDate"] = "Estimated " . date("D j F Y H:i", time()+($content["nextSuperblock"]-$currentBlock)*60);
	$content["budgetRequested"] = 0;
	$content["budgetPassing"] = 0;
	$content["pCount"] = 0;
	$content["passingCount"] = 0;
	$i = 0;
    foreach($proposals as $proposal){
		$superblock = $proposal["superblock"];
		if($superblock < $currentBlock){
			$content["proposal"][$i]["hash"] = $proposal["hash"];
			$content["proposal"][$i]["name"] = $proposal["name"];
			$content["proposal"][$i]["superblock"] = $proposal["superblock"];
			$content["proposal"][$i]["amount"] = $proposal["amount"];
			$content["proposal"][$i]["address"] = $proposal["address"];
			$content["proposal"][$i]["URL"] = $proposal["url"];
			$content["proposal"][$i]["description"] = $proposal["description"];
			$content["proposal"][$i]["yeas"] = $proposal["votes_yes"];
			$content["proposal"][$i]["nays"] = $proposal["votes_no"];
			$content["proposal"][$i]["abstains"] = $proposal["votes_abstain"];
			$content["proposal"][$i]["status"] = $proposal["status"];
			$content["budgetRequested"] += $proposal["amount"];
			$content["proposal"][$i]["passingMargin"] = ($proposal["votes_yes"]-$proposal["votes_no"]-$proposal["votes_abstain"]);
			if($proposal["status"] == "passed") {
				$content["budgetPassing"] += $proposal["amount"];
				$content["passingCount"] += 1;
			}
			$i++;			
		}
	}
	$content["pCount"] = $i;
	return $content;
}

function createOpenOrdersContent(){
	global $blocknetd;

	$content = [];
	$content["openOrderCount"] = 0;
	$content["rolledBackCount"] = 0;
	$content["otherCount"] = 0;
	$content["totalCount"] = 0;

	$openorders = $blocknetd->dxGetOrders();
	$i = 0;
	foreach($openorders as $order){
		$content["order"][$i]["id"] = $order["id"];
		$content["order"][$i]["maker"] = $order["maker"];
		$content["order"][$i]["makerSize"] = $order["maker_size"];
		$content["order"][$i]["taker"] = $order["taker"];
		$content["order"][$i]["takerSize"] = $order["taker_size"];
		$content["order"][$i]["updatedAt"] = $order["updated_at"];
		$content["order"][$i]["createdAt"] = $order["created_at"];
		$content["order"][$i]["orderType"] = $order["order_type"];
		$content["order"][$i]["partialMinimum"] = $order["partial_minimum"];
		$content["order"][$i]["partialOMS"] = $order["partial_orig_maker_size"];
		$content["order"][$i]["partialOTS"] = $order["partial_orig_taker_size"];
		$content["order"][$i]["partialRepost"] = $order["partial_repost"];
		$content["order"][$i]["partialParentId"] = $order["partial_parent_id"];
		$content["order"][$i]["status"] = $order["status"];
		if($order["status"] == "open"){
			$content["openOrderCount"]++;
		}elseif($order["status"] == "rolled_back"){
			$content["rolledBackCount"]++;
		}else{
			$content["otherCount"]++;
		}
		$i++;
	}
	$content["totalCount"] = $i;
    return $content;
}

function createPastOrdersContent($days = 30){
	global $blocknetd;

	$content = [];
	//if($days == ""){
	//	$days = 30;
	//}
	$content["days"] = $days;
	$content["pastOrderCount"] = 0;
	$blocks = $days * 1440;
	$content["blocks"] = $blocks;

	$pastorders = $blocknetd->dxGetTradingData($blocks);
	$i = 0;
	foreach($pastorders as $order){
		$content["order"][$i]["time"] = getDateTime($order["timestamp"]);
		$content["order"][$i]["txid"] = $order["fee_txid"];
		$content["order"][$i]["snodekey"] = $order["nodepubkey"];
		$content["order"][$i]["xid"] = $order["id"];
		$content["order"][$i]["taker"] = $order["taker"];
		$content["order"][$i]["takerAmount"] = $order["taker_size"];
		$content["order"][$i]["maker"] = $order["maker"];
		$content["order"][$i]["makerAmount"] = $order["maker_size"];
		$i++;
	}
	$content["pastOrderCount"] = $i;
	return $content;
}

function createUnspentContent(){
	global $blocknetd, $error;
	
	$content = [];
	
	try{
		$unspents = $blocknetd->listunspent();
	}catch(\Exception $e){
		$error = "Wallet disabled!";
		return "";
	}
	$i = 0;

	foreach($unspents as $unspent){

		$content["utxo"][$i]["hash"] = $unspent["txid"];
		$content["utxo"][$i]["vout"] = $unspent["vout"];
		$content["utxo"][$i]["address"] = $unspent["address"];
		$content["utxo"][$i]["account"] = $unspent["account"];
		$content["utxo"][$i]["scriptpubkey"] = $unspent["scriptPubKey"];
		$content["utxo"][$i]["amount"] = $unspent["amount"];
		$content["utxo"][$i]["confs"] = $unspent["confirmations"];
		$content["utxo"][$i]["spendable"] = $unspent["spendable"];
		$i++;
	}
	$content['utxoCount'] = $i;
	$content['node'] = new Node();
	return $content;
}
?>
