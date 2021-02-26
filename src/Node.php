<?php

namespace App;

class Node {
	public $blockHeight;
	public $pruMode;
	public $chain;
	public $client;
	public $ipv4;
	public $ipv6;
	public $tor;
	public $toConn;
	public $cTime; // Current node time
	public $serivces;
	public $proVer;
	public $localRelay;
	public $timeOffset;
	public $port;
	public $minRelayFee;
	public $mempoolTx;
	public $sizeOfmempoolTx;
	public $mempoolMinFee;
	public $maxMempool;
	public $mempoolUsage;
	public $mempoolUsageP;
	public $mempoolLimited; // Bool: If mempool is beeing limited
	public $tIn;
	public $tOut;
	public $tTotal; // Int : Total traffic
	public $tLimitSet; // Bool : If a t limit is set
	public $tLimited; // Bool: Is limit is active
	public $tUsed; // Int: In MB amount of t used in current cycle
	public $tMax; // Int: In MB the daily t limit
	public $tTimeLeft; // Int : Time in minutes that are left till the limit is reset
	public $tLimitP; // Int: Percentage of Limit used
	public $bHeight; // Int: current block height (as far as node knows)
	public $bHeightAgo; // Int: Minutes since last received block
	public $hHeight; // Int: current max header height (blocks not download by node)
	public $diff; // Int: current network difficulty
	public $hashRate; // Int: current network hash rate
	public $mNetTime; // Int: current network mediatime
	public $softForks; // Arr: List of current forks
	public $walActive; // Bool: if wallet enabled
	public $walVer; // Arr: Wallet Version
	public $walBal; // Arr: Wallet balance
	public $walUbal; // Arr: Wallet unconfirmed balance
	public $walIbal; // Arr: Wallet immature_balance
	public $walTxcount; // Arr: Wallet txcount
	
	
	function __construct() {
		global $blocknetd;
		$networkInfo = $blocknetd->getnetworkinfo();
		$mempoolInfo = $blocknetd->getmempoolinfo();
		$blockchainInfo = $blocknetd->getblockchaininfo();
		$miningInfo = $blocknetd->getmininginfo();
		$tInfo = $blocknetd->getnettotals();
		
		$this->blockHeight = checkInt($blockchainInfo["blocks"]);
		$this->pruMode = false;   // after codebase upgrade    checkBool($blockchainInfo["pruned"]);
		$this->chain = ucfirst(htmlspecialchars($blockchainInfo["chain"]));
		// Gets different IPs
		$ipAddresses =$networkInfo["localaddresses"];
		foreach($ipAddresses as $ipAddress){
			if(preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ipAddress["address"])){
				$this->ipv4 = $ipAddress["address"];
			}
			if(preg_match("/^[0-9a-z]{1,4}(:[0-9a-z]{0,4}){0,6}$/", $ipAddress["address"])){
				$this->ipv6 = $ipAddress["address"];
			}
			if(preg_match("/^[0-9a-z]{16}\.onion$/", $ipAddress["address"])){
				$this->tor = $ipAddress["address"];
			}		
		}
		$this->toConn = checkInt($networkInfo["connections"]);
		$this->client = str_replace('/','',htmlspecialchars($networkInfo["subversion"]));
		$this->proVer = checkInt($networkInfo["protocolversion"]);
		$this->services = getServices($networkInfo["localservices"]);
		$this->localRelay = false;   // after codebase upgrade   checkBool($networkInfo["localrelay"]);
		$this->timeOffset = checkInt($networkInfo["timeoffset"]);
		$this->port = checkInt($networkInfo["localaddresses"][0]["port"]);
		$this->cTime = getDateTime($tInfo["timemillis"]/1000);
		$this->minRelayFee = checkInt($networkInfo["relayfee"]);
		//Mempool
		$this->mempoolTx = checkInt($mempoolInfo["size"]);
		$this->mempoolSize =  round(checkInt($mempoolInfo["bytes"])/1000000,1);
		$this->mempoolMinFee = 0; // after codebase update   checkInt($mempoolInfo["mempoolminfee"]);
		$this->mempoolUsage = bytesToMb($mempoolInfo["usage"]);
		//$this->maxMempool = bytesToMb($mempoolInfo["maxmempool"]);
		$this->maxMempool = 300;  // Use the default since actual value isn't available in RPC (yet)
		$this->mempoolUsageP = calcMpUsage($this->mempoolUsage,$this->maxMempool);
		$this->mempoolLimited = checkMemPoolLimited($this->mempoolMinFee, $this->minRelayFee);
		// Traffic
		$this->tIn = round(bytesToMb($tInfo["totalbytesrecv"]),2);
		$this->tOut = round(bytesToMb($tInfo["totalbytessent"]),2);
		$this->tTotal = $this->tIn + $this->tOut;
		$this->tLimitSet = false; // after codebase update  getTrafficLimitSet($tInfo["uploadtarget"]["target"]);
		$this->tLimited = false; // after codebase update   checkBool($tInfo["uploadtarget"]["target_reached"]);
		$this->tMax = 0; // after codebase update  bytesToMb($tInfo["uploadtarget"]["target"]);
		$this->tUsed = 0; // after codebase update  round($this->tMax - bytesToMb($tInfo["uploadtarget"]["bytes_left_in_cycle"]),0);
		$this->tTimeLeft = 0; // after codebase update  round(checkInt($tInfo["uploadtarget"]["time_left_in_cycle"])/60,1); // In minutes
		if($this->tLimitSet){
			$this->tLimitP = ceil(($this->tUsed/$this->tMax)*100);
		}
		// Blockchain
		$this->bHeight = checkInt($blockchainInfo["blocks"]);
		$this->hHeight = checkInt($blockchainInfo["headers"]);
		
		$blockInfo = $blocknetd->getblock($blockchainInfo["bestblockhash"]);
		$this->bHeightAgo = round((time()-checkInt($blockInfo["time"]))/60,1);
		
		$this->diff = checkInt($blockchainInfo["difficulty"]);
		$this->hashRate = round(checkInt($miningInfo["networkhashps"])/1000000,3);
		// Blockchain -> Soft forks
		$this->softForks = ""; // after codebase upgrade  checkSoftFork($blockchainInfo["bip9_softforks"]);	
		// Wallet Function
		try{
			$walletInfo = $blocknetd->getwalletinfo();
			$this->walVer = checkInt($walletInfo["walletversion"]);	
			$this->walBal = checkInt($walletInfo["balance"]);	
			$this->waluBal = 0; // checkInt($walletInfo["unconfirmed_balance"]);	
			$this->waliBal = 0; // checkInt($walletInfo["immature_balance"]);	
			$this->walTxcount = checkInt($walletInfo["txcount"]);	
			//$this->walUnspent = checkInt($walletInfo["txcount"]);
			$this->walActive = true;		
		}catch(\Exception $e){
			$this->walActive = false;
		}
		
	}
}
