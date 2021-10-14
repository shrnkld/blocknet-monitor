<?php

namespace App;

use SQLite3;

function createMainContent(){
	global $blocknetd, $db, $trafficCIn, $trafficCOut, $newPeersCount;

	date_default_timezone_set('UTC');

	$peers = getPeerData();
	$peerCount = count($peers);

	$content = [];
	$nodecounts = $blocknetd->servicenodecount();
	$content['totalNodes'] = $nodecounts['total'];
	$content['onlineNodes'] = $nodecounts['online'];
	$content['xrNodes'] = count($blocknetd->xrConnectedNodes()['reply']);

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
	//$txoutset = $blocknetd->gettxoutsetinfo();
	//$content['issued'] = floor($txoutset['total_amount']);
	//$content['marketCap'] = round($txoutset['total_amount'] * $content['priceInfo']['BLOCK/USD'], 0);
	$txoutset = chainzAPI("totalcoins");
	$content['issued'] = floor($txoutset);
	$content['marketCap'] = round($txoutset * $content['priceInfo']['BLOCK/USD'], 0);

	// Open orders count
	$openorders = $blocknetd->dxGetOrders();
	$content['openOrders'] = count($openorders);
	
	// Completed orders
	updatePastOrders();
	$content['recentOrders'] = $db->querySingle('SELECT COUNT(*) FROM "pastorders" WHERE "timestamp" >= strftime("%s","now")-86400');
	$content['alltimeOrders'] = $db->querySingle('SELECT COUNT(*) FROM "pastorders"');

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
	$content['tTraf'] = ($netinfo['totalbytesrecv'] + $netinfo['totalbytessent'])/1000000;
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

function createBlocksContent(){
	global $blocknetd;

	$content = [];
	$content['totalTx'] = 0;
	$content['totalFees'] = 0;
	$content['totalSize'] = 0;
	$content['segwitCount'] = 0;
	$blocktime = 60;

	$blockHash = $blocknetd->getbestblockhash();

	for($i = 0; $i < Config::DISPLAY_BLOCKS; $i++){
		$block = $blocknetd->getblock($blockHash);
		if($i==0){ 
			$content['latest'] = $block['height'];
		}
		$content['blocks'][$block['height']]['hash'] = $block['hash'];
		$content['blocks'][$block['height']]['size'] = round($block['size']/1000,2);
		$content['totalSize'] += $block['size'];
		$content['blocks'][$block['height']]['versionhex'] = "N/A";
		$content['blocks'][$block['height']]['voting'] = "N/A";
		$content['blocks'][$block['height']]['time'] = getDateTime($block['time']);
		$content['blocks'][$block['height']]['timeago'] = round((time() - $block['time'])/60);
		$content['blocks'][$block['height']]['coinbasetx'] = $block['tx'][0];
		$content['blocks'][$block['height']]['coinstaketx'] = $block['tx'][1];
		$coinbaseTx = $blocknetd->getrawtransaction($block['tx'][0], 1);
		$coinstakeTx = $blocknetd->getrawtransaction($block['tx'][1], 1);
		$coinbase = $coinbaseTx['vout'][1]['value'];
		$coinstake = $coinstakeTx['vout'][0]['value'];
		$content['blocks'][$block['height']]['fees'] = round($coinbase + $coinstake, 5);
		$content['blocks'][$block['height']]['fees'] = $coinbase;
		$content['totalFees'] += $content['blocks'][$block['height']]['fees'];
		$content['blocks'][$block['height']]['txcount'] = count($block['tx']);
		$content['totalTx'] += $content['blocks'][$block['height']]['txcount'];
		$blockHash = $block['previousblockhash'];
	}
	$content['avgTxSize'] = round(($content['totalSize']/($content['totalTx']))/1000,2);
	$content['avgSize'] = round($content['totalSize']/(Config::DISPLAY_BLOCKS*1000),2);
	$content['totalSize'] = round($content['totalSize']/1000000,2);
	$content['avgFee'] = round($content['totalFees']/Config::DISPLAY_BLOCKS,2);
	$content['totalFees'] = round($content['totalFees'],2);
	$content['numberOfBlocks'] = Config::DISPLAY_BLOCKS;
	$content['timeframe'] = round(end($content['blocks'])['timeago']/$blocktime,0);

	return $content;
}

function createForksContent(){
	global $blocknetd;

	$content['recentForks'] = 0;	// Count forks in last 24h

	$forks = $blocknetd->getchaintips();
	$i = 0;
	$lastTime = 0;

	foreach($forks as $fork){
		if($i == Config::DISPLAY_FORKS){
			break;
		}

		$content['blocks'][$i]['height'] = $fork['height'];
		$content['blocks'][$i]['hash'] = $fork['hash'];
		$content['blocks'][$i]['forklength'] = $fork['branchlen'];
		$content['blocks'][$i]['status'] = $fork['status'];

		if($fork['status'] != "headers-only" AND $fork['status'] != "unknown"){
			$block = $blocknetd->getblock($fork['hash']);
			$content['blocks'][$i]['size'] = round($block['size']/1000,2);
			//$content['blocks'][$i]['versionhex'] = $block['versionHex'];
			//$content['blocks'][$i]['voting'] = getVoting($block['versionHex']);
			$content['blocks'][$i]['time'] = getDateTime($block['time']);
			$lastTime = $block['time'];
			$content['blocks'][$i]['timeago'] = round((time() - $block['time'])/3600);
			$content['blocks'][$i]['txcount'] = count($block['tx']);

			if($content['blocks'][$i]['timeago'] <= 24){
				$content['recentForks']++;
			}
		}
		$i++;
	}

	$content['timeframe'] = round((time()-$lastTime)/3600);
	$content['forkCount'] = Config::DISPLAY_FORKS - 1;	// Don't count most recent block as a fork
	$content['recentForks']--;	// Don't count most recent block as a fork

	return $content;
}

function createMempoolContent(){
	global $blocknetd;

	$content['txs'] = $blocknetd->getrawmempool(TRUE);
	$content['txs'] = array_slice($content['txs'], 0, CONFIG::DISPLAY_TXS);
	$content['node'] = new Node();

	return $content;
}

function updateSnodesContent(){
    global $blocknetd, $db;

	$db->exec('DELETE FROM "servicenodes"');
	$db->exec('DELETE FROM "dxWallets"');
	$db->exec('DELETE FROM "xrServices"');
	$db->exec('DELETE FROM "xcServices"');

	$servicenodes = $blocknetd->servicenodelist();
	$xrConnectedNodes = $blocknetd->xrConnectedNodes();
	$i = 0;
	$j = 0;
	$now = time();

	$db->exec('BEGIN TRANSACTION');
	$statement1 = $db->prepare('INSERT INTO "servicenodes" ("nodepubkey", "tier", "address", "payment_address", "timelastseen", "exr", "status", "score", "updated")
								VALUES (:nodepubkey, :tier, :address, :paymentaddress, :timelastseen, :exr, :status, :score, :updated)');
	$statement2 = $db->prepare('UPDATE "servicenodes" SET "xr"=:xr, "dxcount"=:dxcount, "xrcount"=:xrcount, "xccount"=:xccount, "services"=:services WHERE "nodepubkey"=:nodepubkey');
	$statement3 = $db->prepare('INSERT INTO "dxWallets" VALUES(:coin, :nodepubkey)');
	//$statement4 = $db->prepare('INSERT INTO "xrServices" VALUES(:nodepubkey, :command, :coin, :fee, :paymentAddress, :requestLimit, :fetchLimit, :timeout, :disabled, :updated)');
	//$statement5 = $db->prepare('INSERT INTO "xcServices" ("nodepubkey", "xcservice", "payment_address", "updated") VALUES(:nodepubkey, :name, :paymentAddress, :updated)');

	foreach($servicenodes as $node){
		$statement1->bindValue(':nodepubkey', $node['snodekey']);
		$statement1->bindValue(':tier', $node['tier']);
		$statement1->bindValue(':address', $node['address']);
		$statement1->bindValue(':paymentaddress', $node['address']);
		$statement1->bindValue(':timelastseen', $node['timelastseen']);
		$statement1->bindValue(':exr', $node['exr']);
		$statement1->bindValue(':status', $node['status']);
		$statement1->bindValue(':score', $node['score']);
		$statement1->bindValue(':updated', $now);
		try {
			$statement1->execute();
		} catch (\Exception $e) {
			print("Insert servicenode failed with " .$e->GetMessage()."\n");
			$j++;
		}
		$xr = FALSE;
		$xrcount = 0;
		$xccount = 0;
		$dxcount = 0;
		$services = '';
		$statement2->bindvalue(':nodepubkey', $node['snodekey']);
		foreach($node['services'] as $service) {
			if($service == 'xr'){
				$xr = TRUE;
			}elseif(substr($service, 0, 4) == 'xr::'){
				$xrcount ++;
				$services .= ','.$service;
			}elseif(substr($service, 0, 5) == 'xrs::'){
				$xccount ++;
		        $services .= ','.$service;
			}else{
				$dxcount ++;
				$services .= ','.$service;
				$statement3->bindvalue(':nodepubkey', $node['snodekey']);
				$statement3->bindvalue(':coin', $service);
				$statement3->execute();
			}
		}
		$statement2->bindvalue(':xr', $xr);
		$statement2->bindvalue(':xrcount', $xrcount);
		$statement2->bindvalue(':xccount', $xccount);
		$statement2->bindvalue(':dxcount', $dxcount);
		$statement2->bindvalue(':services', trim($services, ','));
		try {
			$statement2->execute();
		} catch (\Exception $e) {
			print("Update servicenode failed with " .$e->GetMessage()."\n");
			$j++;
		}
		$i++;
	}
	$statement1->close();
	$statement2->close();
	$statement3->close();
	//$statement4->close();
	//$statement5->close();
	$db->exec('COMMIT');
	
	$db->exec('BEGIN TRANSACTION');
	$statement1 = $db->prepare('UPDATE "servicenodes" 
								SET "score"=:score, 
									"banned"=:banned, 
									"payment_address"=:paymentaddress, 
									"tier"=:tier, 
									"fee_default"=:feedefault, 
									"updated"=:updated 
								WHERE "nodepubkey" = :nodepubkey');
	$statement2 = $db->prepare('INSERT INTO "xrServices" 
								VALUES(:nodepubkey, 
									   :xrservice, 
									   :coin, 
									   :fee, 
									   :paymentaddress, 
									   :requestlimit, 
									   :fetchlimit, 
									   :timeout, 
									   :disabled,
									   :updated)');
	$statement3 = $db->prepare('INSERT INTO "xcServices" 
								VALUES(:nodepubkey, 
									   :xcservice, 
									   :parameters,
									   :fee, 
									   :paymentaddress, 
									   :requestlimit, 
									   :fetchlimit, 
									   :timeout, 
									   :disabled,
									   :description,
									   :updated)');

	foreach($xrConnectedNodes['reply'] as $node){
		$statement1->bindValue(':nodepubkey', $node['nodepubkey']);
		$statement1->bindValue(':score', $node['score']);
		$statement1->bindValue(':banned', $node['banned']);
		$statement1->bindValue(':paymentaddress', $node['paymentaddress']);
		$statement1->bindValue(':tier', $node['tier']);
		$statement1->bindValue(':feedefault', $node['feedefault']);
		$statement1->bindValue(':updated', $now);
		try {
			$statement1->execute();
		} catch (\Exception $e) {
			print("Update servicenode failed with " .$e->GetMessage()."\n");
		}

		foreach($node['spvconfigs'] as $onespv){
			$statement2->bindValue(':nodepubkey', $node['nodepubkey']);
			$statement2->bindValue(':coin', trim($onespv['spvwallet']));
			foreach($onespv['commands'] as $command){
				if($command['command'] != "xrGetConfig"){ 
					$statement2->bindValue(':xrservice', $command['command']);
					$statement2->bindValue(':fee', $command['fee']);
					$statement2->bindValue(':paymentaddress', $command['paymentaddress']);
					$statement2->bindValue(':requestlimit', $command['requestlimit']);
					$statement2->bindValue(':fetchlimit', $command['fetchlimit']);
					$statement2->bindValue(':timeout', $command['timeout']);
					$statement2->bindValue(':disabled', $command['disabled']);
					$statement2->bindValue(':updated', $now);
					$statement2->execute();
				}
			}
		}

		foreach($node['services'] as $service => $command){
			$statement3->bindValue(':nodepubkey', $node['nodepubkey']);
			$statement3->bindValue(':xcservice', $service);
			$statement3->bindValue(':parameters', $command['parameters']);
			$statement3->bindValue(':fee', $command['fee']);
			$statement3->bindValue(':paymentaddress', $command['paymentaddress']);
			$statement3->bindValue(':requestlimit', $command['requestlimit']);
			$statement3->bindValue(':fetchlimit', $command['fetchlimit']);
			$statement3->bindValue(':timeout', $command['timeout']);
			$statement3->bindValue(':disabled', $command['disabled']);
			$statement3->bindValue(':description', $command['help']);
			$statement3->bindValue(':updated', $now);
			$statement3->execute();
		}
	}
	$statement1->close();
	$statement2->close();
	$statement3->close();
	$db->exec('COMMIT');
}

function createSNodesContent(){
	global $blocknetd, $db;
	updateSnodesContent();

	$statement1 = $db->prepare('SELECT * FROM "servicenodes" ORDER BY "timelastseen" DESC');
	$servicenodes = $statement1->execute();
	$nodes = [];
	$exr = 0;
	$online = 0;
    while ($snode = $servicenodes->fetchArray())
    {
		$snodeObj = new SNode($snode);
		$nodes[] = $snodeObj;
		$exr += (int)($snodeObj->exr == 1);
		$online += (int)($snodeObj->status == 'running');
	}
	$content['nodes'] = $nodes;
	$content['totalNodes'] = count($nodes);
	$content['onlineNodes'] = $online;
	$content['exrNodes'] = $exr;
	$content['xrNodes'] = count($blocknetd->xrConnectedNodes()['reply']);
    $content['geo'] = FALSE;

    return $content;
}

function createSNodeDetails($nodepubkey){
	global $blocknetd, $db;
	//updateSnodesContent();

	$statement1 = $db->prepare('SELECT * FROM "servicenodes" WHERE "nodepubkey" = :nodepubkey');
	$statement1->bindValue(':nodepubkey', $nodepubkey);
	$details = $statement1->execute();
	$nodes = [];
    while ($snode = $details->fetchArray())
    {
		$snodeObj = new SNode($snode);
		$nodes[] = $snodeObj;
	}
	$content = $nodes[0];
	//print('Node details:');
	//var_dump($content);

    return $content;
}

function createXcServices($snode = '', $service = ''){
	global $db;
	updateSnodesContent();

	$content['request'] = '';
	if($snode.$service == ''){
		$statement1 = $db->prepare('SELECT * FROM "xcservices" ORDER BY "timelastseen" DESC');
		$content['request'] = 'All services on all nodes.';
	}else{
	    $query = 'SELECT * FROM "xcservices" WHERE 1=1';
	    if($snode != ''){
			$query .= ' AND "nodepubkey" = :snode';
			$content['request'] .= 'servicenode='.$snode;
		}
	    if($service != ''){
			$query .= ' AND "xcservice" = :service';
			$content['request'] .= ' service='.$service;
		}
		$query .= ' ORDER BY "timelastseen" DESC';
	    $statement1 = $db->prepare($query);
		$statement1->bindValue(':snode', $snode);
		$statement1->bindValue(':service', $service);
	}
	$XCservices = $statement1->execute();

	$services = [];

    while ($service = $XCservices->fetchArray())
    {
		$services[] = $service;
	}
	$content['services'] = $services;
	$content['servicesCount'] = count($services);

    return $content;
}

function createXrServices($snode = '', $coin = '', $service = ''){
	global $db;
	updateSnodesContent();

	$content['request'] = '';
	if($snode.$coin.$service == ''){
		$statement1 = $db->prepare('SELECT * FROM "xrservices" ORDER BY "timelastseen" DESC');
		$content['request'] = 'All services on all nodes.';
	}else{
	    $query = 'SELECT * FROM "xrservices" WHERE 1=1';
	    if($snode != ''){
			$query .= ' AND "nodepubkey" = :snode';
			$content['request'] .= 'servicenode='.$snode;
		}
	    if($coin != ''){
			$query .= ' AND "coin" = :coin';
			$content['request'] .= ' coin='.$coin;
		}
	    if($service != ''){
			$query .= ' AND "xrservice" = :service';
			$content['request'] .= ' service='.$service;
		}
		$query .= ' ORDER BY "timelastseen" DESC';
	    $statement1 = $db->prepare($query);
		$statement1->bindValue(':snode', $snode);
		$statement1->bindValue(':coin', $coin);
		$statement1->bindValue(':service', $service);
	}
	$XRservices = $statement1->execute();
	$services = [];
	
    while ($service = $XRservices->fetchArray())
    {
		$services[] = $service;
	}
	$content['services'] = $services;
	$content['servicesCount'] = count($services);

    return $content;
}

function createDxXrWallets(){
    global $db;
   
	$wallets = [];
    $statement1 = $db->prepare('SELECT DISTINCT("coin"), COUNT("coin") AS "wallets" FROM "dxwallets" GROUP BY "coin"');
    $statement2 = $db->prepare('SELECT DISTINCT("coin"), COUNT("coin") AS "wallets" FROM "spvwallets" GROUP BY "coin"');

	$dxCount = 0;
	$spvCount = 0;
    $result = $statement1->execute();
    while ($row = $result->fetchArray())
    {
		$wallets[$row['coin']]['dx'] = $row['wallets'];
		$wallets[$row['coin']]['spv'] = '0';
		$dxCount++;
	}
    $result = $statement2->execute();
    while ($row = $result->fetchArray())
    {
		$wallets[$row['coin']]['spv'] = $row['wallets'];
		if(!isset($wallets[$row['coin']]['dx'])){
			$wallets[$row['coin']]['dx'] = '0';
		}
		$spvCount++;
	}
    $statement1->close();
    $statement2->close();
	$content['wallets'] = $wallets;
	$content['dxCount'] = $dxCount;
	$content['spvCount'] = $spvCount;
    return $content;
}

function createGovernanceContent(){
	global $blocknetd;
	$content['nextSuperblock'] = $blocknetd->nextsuperblock();
	$proposals = $blocknetd->listproposals($content['nextSuperblock']-43200+1);
	$mnCount = $blocknetd->servicenodecount()['total'];
	$currentBlock = $blocknetd->getblockcount();
	$content['nextDate'] = "Estimated " . date("D j F Y H:iT", time()+($content['nextSuperblock']-$currentBlock)*60);
	$content['pCutoff'] = "Estimated new proposals deadline: " . date("D j F Y H:iT", time()+($content['nextSuperblock']-2880-$currentBlock)*60);
	$content['vCutoff'] = "Estimated voting deadline: " . date("D j F Y H:iT", time()+($content['nextSuperblock']-60-$currentBlock)*60);
	if($currentBlock >= $content['nextSuperblock'] - 1440 * 2){
		$content['pCutoffColour'] = "red";
		$content['pCutoff'] = "New proposals submission window for this superblock is closed.";
	}elseif($currentBlock >= $content['nextSuperblock'] - 1440 * 4){
		$content['pCutoffColour'] = "orange";
	}else{$content['pCutoffColour'] = "green";}
	if($currentBlock >= $content['nextSuperblock'] - 60){
		$content['vCutoffColour'] = "red";
		$content['vCutoff'] = "Voting window for this superblock is closed.";
	}elseif($currentBlock >= $content['nextSuperblock'] - 1440 * 2 - 60){
		$content['vCutoffColour'] = "orange";
	}else{$content['vCutoffColour'] = "green";}
	$maxBudget = 40000;
	$content['budgetRequested'] = 0;
	$content['budgetPassing'] = 0;
	$content['budgetRemaining'] = $maxBudget;
	$content['pCount'] = 0;
	$content['passingCount'] = 0;
	$i = 0;
    foreach($proposals as $proposal){
		$content['proposal'][$i]['hash'] = $proposal['hash'];
		$content['proposal'][$i]['name'] = $proposal['name'];
		$content['proposal'][$i]['superblock'] = $proposal['superblock'];
		$content['proposal'][$i]['amount'] = $proposal['amount'];
		$content['proposal'][$i]['address'] = $proposal['address'];
		$content['proposal'][$i]['URL'] = $proposal['url'];
		$content['proposal'][$i]['description'] = $proposal['description'];
		$content['proposal'][$i]['yeas'] = $proposal['votes_yes'];
		$content['proposal'][$i]['nays'] = $proposal['votes_no'];
		$content['proposal'][$i]['abstains'] = $proposal['votes_abstain'];
		$content['proposal'][$i]['status'] = $proposal['status'];
		$content['budgetRequested'] += $proposal['amount'];
		$content['proposal'][$i]['passingMargin'] = ($proposal['votes_yes']-$proposal['votes_no']-$proposal['votes_abstain']);
		if($content['proposal'][$i]['status'] == 'passing') {
			$content['budgetPassing'] += $proposal['amount'];
			$content['passingCount'] += 1;
		}
		$i++;			
	}
	$content['pCount'] = $i;
	$content['budgetRemaining'] -= $content['budgetRequested'];
	if($content['budgetRequested'] > $maxBudget){
		$content['reqColour'] = "red";
	}elseif($content['budgetRequested'] > $maxBudget * 0.9){
		$content['reqColour'] = "orange";
	}else{
		$content['reqColour'] = "green";
	}
	if($content['budgetPassing'] > $maxBudget){
		$content['passingColour'] = "red";
	}elseif($content['budgetPassing'] > $maxBudget * 0.9){
		$content['passingColour'] = "orange";
	}else{
		$content['passingColour'] = "green";
	}
	if($content['budgetRemaining'] < 0){
		$content['remainingColour'] = "red";
	}elseif($content['budgetRemaining'] < $maxBudget * 0.1){
		$content['remainingColour'] = "orange";
	}else{
		$content['remainingColour'] = "green";
	}
	return $content;
}

function updatePastProposals(){
	global $blocknetd, $db;
	$height = $blocknetd->getblockcount();
	$lastSuperblock = intdiv($height, 43200) * 43200;
	$lastProposal = $db->querySingle('SELECT "lastproposal" FROM "events"') or 1296000;
 
	if($lastSuperblock <> $lastProposal){
		//print("Checking proposals since block ".$lastProposal."\n");
 
		$proposals = $blocknetd->listproposals($lastProposal + 1);
	
		$statement = $db->prepare('INSERT INTO "pastproposals" (
			"hash","name","superblock","amount","address","url","description","yeas","nays","abstains","status")
			 VALUES (:phash, :pname, :psuperblock, :pamount, :paddress, :purl, :pdescription, :pyeas, :pnays, :pabstains, :pstatus)');
		$statement2 = $db->prepare('UPDATE "events" set "lastproposal" = :height');
		$statement2->bindValue(':height', $lastSuperblock);
	
		$db->exec("BEGIN");
		$i = 0;
		$j = 0;
		foreach($proposals as $proposal){
			if($proposal['superblock'] <= $height){
				$statement->bindValue(':phash', $proposal['hash']);
				$statement->bindValue(':pname', $proposal['name']);
				$statement->bindValue(':psuperblock', $proposal['superblock']);
				$statement->bindValue(':pamount', $proposal['amount']);
				$statement->bindValue(':paddress', $proposal['address']);
				$statement->bindValue(':purl', $proposal['url']);
				$statement->bindValue(':pdescription', $proposal['description']);
				$statement->bindValue(':pyeas', $proposal['votes_yes']);
				$statement->bindValue(':pnays', $proposal['votes_no']);
				$statement->bindValue(':pabstains', $proposal['votes_abstain']);
				$statement->bindValue(':pstatus', $proposal['status']);
				try {
					$statement->execute();
					$i++;
				} catch (\Exception $e) {
					print("Insert failed with " .$e->GetMessage()."\n");
					$j++;
				}
			}
		}
		$statement2->execute();
		$db->exec("COMMIT");
		//print("Proposal inserts succeeded: ".$i." failed: ".$j."\n");    
	}else{
		//print("No completed proposals to insert\n");
	}

	// Now "polish" the proposals: find those which passed but did not get paid
    // and change their status to "not paid".

    // First build the work table of all payments made in each oversubscribed superblock
	$db->exec('DELETE FROM "scratch"');
    $statement1 = $db->prepare('SELECT "superblock" FROM "pastproposals" WHERE "status"="passed" 
                                GROUP BY "superblock"
                                HAVING SUM("amount")>40000');
    $statement2 = $db->prepare('INSERT INTO "scratch" VALUES(:superblock, :txid, :amount, :addr)');

    $result = $statement1->execute();
    $db->exec('BEGIN');
    $i = 0;
    $j = 0;
    while ($row = $result->fetchArray()) {
        $i++;
        $blockhash = $blocknetd->getblockhash($row['superblock']);
        $block = $blocknetd->getblock($blockhash);
        $txid = $block['tx'][1];
        $statement2->bindValue(':superblock', $row['superblock']);
        $statement2->bindValue(':txid', $txid);
        $txdets = $blocknetd->getrawtransaction($txid,1);
        foreach($txdets['vout'] as $n => $payment) {
            if($n < 1) {
                continue;
            }
            $j++;
            $statement2->bindValue(':amount', $payment['value']);
            $statement2->bindValue(':addr', $payment['scriptPubKey']['addresses'][0]);
            $statement2->execute();
        }
    }
    $result->finalize();
    $db->exec('COMMIT');
    $statement1->close();
    $statement2->close();

    // now find the unpaid passing proposals and update their status
    $db->exec('UPDATE "pastproposals" SET "status"="not paid" WHERE "hash" IN
                                (SELECT "a"."hash" FROM "pastproposals" "a" LEFT JOIN "scratch" "b" 
                                 ON "a"."superblock"="b"."superblock" AND "a"."amount"="b"."amount"
                                 AND "a"."address"="b"."address"
                                 WHERE "a"."status"="passed" AND "a"."superblock" IN
                                 (SELECT "superblock" FROM "pastproposals" WHERE "status"="passed" 
                                  GROUP BY "superblock" HAVING SUM("amount")>40000)
                                 AND "b"."amount" IS NULL)');

    //if ($i + $j > 0) {
    //    print("Polished ".$j." proposals in ".$i." superblocks\n");
    //}
}

function createPastProposalsContent(){
	global $blocknetd, $db;
	updatePastProposals();

	$content['nextSuperblock'] = $blocknetd->nextsuperblock();
	$currentBlock = $blocknetd->getblockcount();
	$content['nextDate'] = "Estimated " . date("D j F Y H:iT", time()+($content['nextSuperblock']-$currentBlock)*60);
	$content['budgetRequested'] = 0;         // total requested
	$content['budgetPaid'] = 0;              // passed and paid
	$content['budgetNotPaid'] = 0;           // passed but not paid
	$content['budgetFailed'] = 0;            // not passed
	$content['pCount'] = 0;
	$content['passedCount'] = 0;
	$content['paidCount'] = 0;
	$content['notPaidCount'] = 0;
	$content['failedCount'] = 0;
	$statement1 = $db->prepare('SELECT * FROM "pastproposals" ORDER BY "superblock"');
	$proposals = $statement1->execute();
	$lastsuperblock = 0;
	$i = 0;
    while ($proposal = $proposals->fetchArray())
    {
		$superblock = $proposal['superblock'];
		if($superblock != $lastsuperblock){
			$superblockhash = $blocknetd->getblockhash($superblock);
			$lastsuperblock = $superblock;
		}
		$content['proposal'][$i]['hash'] = $proposal['hash'];
		$content['proposal'][$i]['name'] = $proposal['name'];
		$content['proposal'][$i]['superblock'] = $proposal['superblock'];
		$content['proposal'][$i]['superblockhash'] = $superblockhash;
		$content['proposal'][$i]['amount'] = $proposal['amount'];
		$content['proposal'][$i]['address'] = $proposal['address'];
		$content['proposal'][$i]['URL'] = $proposal['url'];
		$content['proposal'][$i]['description'] = $proposal['description'];
		$content['proposal'][$i]['yeas'] = $proposal['yeas'];
		$content['proposal'][$i]['nays'] = $proposal['nays'];
		$content['proposal'][$i]['abstains'] = $proposal['abstains'];
		$content['proposal'][$i]['status'] = $proposal['status'];
		$content['budgetRequested'] += $proposal['amount'];
		$content['proposal'][$i]['passingMargin'] = ($proposal['yeas']-$proposal['nays']-$proposal['abstains']);
	    $i++;
		if($proposal['status'] == 'passed') {
			$content['budgetPaid'] += $proposal['amount'];
			$content['paidCount']++;
		}elseif($proposal['status'] == 'not paid') {
			$content['budgetNotPaid'] += $proposal['amount'];
			$content['notPaidCount']++;
		}else{
			$content['budgetFailed'] += $proposal['amount'];
			$content['failedCount']++;
		}
	}
    $proposals->finalize();
    $statement1->close(); 
    $content['pCount'] = $i;
	return $content;
}

function createOpenOrdersContent(){
	global $blocknetd;
	// Each order looks like
	//{
	//	"id": "19dce16f9c5058334c5897ac781eea73f9764d92ded55a685bf332cd852f84bb",
	//	"maker": "BLOCK",
	//	"maker_size": "136.576143",
	//	"taker": "LTC",
	//	"taker_size": "2.643747",
	//	"updated_at": "2021-03-29T20:04:08.341Z",
	//	"created_at": "2021-03-29T20:04:08.207Z",
	//	"order_type": "exact",
	//	"partial_minimum": "0.000000",
	//	"partial_orig_maker_size": "136.576143",
	//	"partial_orig_taker_size": "2.643747",
	//	"partial_repost": false,
	//	"partial_parent_id": "",
	//	"status": "open"
	//}
	
	$content = [];
	$content['openOrderCount'] = 0;
	$content['rolledBackCount'] = 0;
	$content['otherCount'] = 0;
	$content['totalCount'] = 0;

	$openorders = $blocknetd->dxGetOrders();
	$i = 0;
	foreach($openorders as $order){
		$content['order'][$i]['id'] = $order['id'];
		$content['order'][$i]['maker'] = $order['maker'];
		$content['order'][$i]['makerSize'] = $order['maker_size'];
		$content['order'][$i]['taker'] = $order['taker'];
		$content['order'][$i]['takerSize'] = $order['taker_size'];
		$content['order'][$i]['updatedAt'] = $order['updated_at'];
		$content['order'][$i]['createdAt'] = $order['created_at'];
		$content['order'][$i]['orderType'] = $order['order_type'];
		$content['order'][$i]['partialMinimum'] = $order['partial_minimum'];
		$content['order'][$i]['partialOMS'] = $order['partial_orig_maker_size'];
		$content['order'][$i]['partialOTS'] = $order['partial_orig_taker_size'];
		$content['order'][$i]['partialRepost'] = $order['partial_repost'];
		$content['order'][$i]['partialParentId'] = $order['partial_parent_id'];
		$content['order'][$i]['status'] = $order['status'];
		//$content['order'][$i]['fair'] = $blocknetd->xrService('ccfairprice2',$order['maker_size'],$order['maker'],$order['taker'],$order['taker_size']);
		$content['order'][$i]['fair'] = 'API rate limit exceeded';
		if($order['status'] == "open"){
			$content['openOrderCount']++;
		}elseif($order['status'] == "rolled_back"){
			$content['rolledBackCount']++;
		}else{
			$content['otherCount']++;
		}
		$i++;
	}
	$content['totalCount'] = $i;
	$content['blocknetd'] = $blocknetd;
    return $content;
}

function updatePastOrders() {
	global $blocknetd, $db;
	
    $height = $blocknetd->getblockcount();
    $lastheight = $db->querySingle('SELECT "lastorderheight" from "events"');
    $blocks = $height - $lastheight;
    if($blocks > 0){
        //print("Fetching ".$blocks." blocks\n");
 
        $pastorders = $blocknetd->dxGetTradingData($blocks);

        $statement = $db->prepare('INSERT INTO "pastorders" ("id", "timestamp", "fee_txid", "nodepubkey", "taker", "taker_size", "maker", "maker_size") VALUES (:id, :tstamp, :fee_txid, :nodepubkey, :taker, :taker_size, :maker, :maker_size)');
        $statement2 = $db->prepare('UPDATE "events" set "lastorderheight" = :height');
        $statement2->bindValue(':height', $height);

        $db->exec("BEGIN");

        //$i = 0;
        //$j = 0;
        foreach($pastorders as $order){
            $statement->bindValue(':id', $order['id']);
            $statement->bindValue(':tstamp', $order['timestamp']);
            $statement->bindValue(':fee_txid', $order['fee_txid']);
            $statement->bindValue(':nodepubkey', $order['nodepubkey']);
            $statement->bindValue(':taker', $order['taker']);
            $statement->bindValue(':taker_size', $order['taker_size']);
            $statement->bindValue(':maker', $order['maker']);
            $statement->bindValue(':maker_size', $order['maker_size']);
            try {
                $statement->execute();
            } catch (\Exception $e) {
                //print("Insert failed with " .$e->GetMessage()."\n");
                //$j++;
            }
            //$i++;
        }

        $statement2->execute();
        $db->exec("COMMIT");
		$statement->close();
		$statement2->close();
       //$rows = $i - $j;
       //print("Found ".$i." new completed trades, ".$rows." rows inserted.\n");    
    }
}

function createPastOrdersContent($days = 1, $maker = '', $taker = '', $snode = ''){
	global $blocknetd, $db;

	updatePastOrders();

	$content = [];
	if($days == 0){   // special case: 
		if($maker.$taker.$snode <> ''){
			$genesis = $blocknetd->getblock($blocknetd->getblockhash(0))['time'];
			$days = intdiv((time() - $genesis), 86400); // so long as there is some search criteria
		}else{
			$days = 1; // otherwise stick to the normal default
		}
	}
	$content['days'] = $days;
	$content['pastOrderCount'] = 0;
	$blocks = $days * 1440;
	$content['blocks'] = $blocks;
	$content['request'] = 'days='.$days;

    $query = 'SELECT * FROM "pastorders" WHERE "timestamp" >= :since';
	if($maker <> ''){
		$query .= ' AND "maker" = :maker';
		$content['request'] .= ' and maker='.$maker;
	}
	if($taker <> ''){
		$query .= ' AND "taker" = :taker';
		$content['request'] .= ' and taker='.$taker;
	}
	if($snode <> ''){
		$query .= ' AND "nodepubkey" = :snode';
		$content['request'] .= ' and servicenode='.$snode;
	}
    //print($query);
    $statement = $db->prepare($query);
    $statement->bindValue(':since', time() - $days * 86400);
    $statement->bindValue(':maker', $maker);
    $statement->bindValue(':taker', $taker);
    $statement->bindValue(':snode', $snode);

    $result = $statement->execute();
	$i = 0;
    while ($order = $result->fetchArray()) {
		$content['order'][$i]['time'] = getDateTime($order['timestamp']);
		$content['order'][$i]['txid'] = $order['fee_txid'];
		$content['order'][$i]['snodekey'] = $order['nodepubkey'];
		$content['order'][$i]['xid'] = $order['id'];
		$content['order'][$i]['taker'] = $order['taker'];
		$content['order'][$i]['takerAmount'] = $order['taker_size'];
		$content['order'][$i]['maker'] = $order['maker'];
		$content['order'][$i]['makerAmount'] = $order['maker_size'];
		$i++;
	}
	$content['pastOrderCount'] = $i;
	$result->finalize();
    $statement->close();

	return $content;
}

function createTradesAndFees($days = ''){
	global $db;
	
	updatePastOrders();
	
	$content = [];
	if($days == ''){
		$content['period'] = 'All time';
		$content['days'] = 0; // Special case
	}else{
	    $content['period'] = 'Last '.$days.' days.';
		$content['days'] = $days;
	    //$blocks = $days * 1440;
	    //$content['blocks'] = $blocks;
    }
	$content['nodeCount'] = 0;
	
	$query = 'SELECT "nodepubkey", count(*) AS "trades", count(*) * 0.015 AS "fees" FROM "pastorders"';
	if($days <> ''){
		$query .= ' WHERE "timestamp" >= strftime("%s","now")-86400*'.$days;
		//$content['request'] .= ' days='.$days;
	}
	$query .= ' GROUP BY "nodepubkey" ORDER BY "fees" DESC';

	//print($query);
	$statement = $db->prepare($query);
	
	$result = $statement->execute();
	$i = 0;
	$j = 0;
	$k = 0;
	while ($row = $result->fetchArray()) {
		$content['taf'][$i]['servicenode'] = $row['nodepubkey'];
		$content['taf'][$i]['trades'] = $row['trades'];
		$content['taf'][$i]['fees'] = $row['fees'];
		$j += $row['trades'];
		$k += $row['fees'];
		$i++;
	}
	$result->finalize();
    $statement->close();
	$content['nodeCount'] = $i;
	$content['totalTrades'] = $j;
	$content['totalFees'] = $k;

	return $content;
}

?>