<?php

namespace App;

class MSNode{	
	public $id; // int
	public $IP; // string
	public $type; // MN or SN
	public $txid; // string
	public $status; // string
	public $protocol; // string
	public $address; // string
	public $lastSeen; // string
	public $timeActive; // string
	public $lastPaid; // string
	public $country; // string
	public $countryCode; // string
	public $region; // string
	public $city; // string
	public $isp; // string
	public $hosted; // bool
	
	function __construct($msNode) {
		$this->id = $msNode["id"];
		$this->IP = $msNode["IP"];
		$this->type = $msNode["type"];
		$this->txid = $msNode["txid"];
		$this->status = $msNode["status"];
		$this->protocol = $msNode["protocol"];
		$this->address = $msNode["address"];
		$this->lastSeen = $msNode["lastseen"];
		$this->timeActive = $msNode["activetime"];
		$this->lastPaid = $msNode["lastpaid"];
//		$this->country = "UN";
//		$this->countryCode = "Unknown";
//		$this->region = "Unknown";
//		$this->city = "Unknown";
//		$this->isp = "Unknown";
//		$this->hosted = false;
	}			
}
?>