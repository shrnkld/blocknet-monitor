<?php

namespace App;

class SNode{	
	public $snodekey; // string
	public $tier; // string
	public $address; // string
	public $timeLastSeen;  // int (epoch)
	public $timeLastSeenStr;  // string
	public $exr; // bool
	public $status; // string
	public $score; // int
	public $services; // string
	//public $servicesOriginal; // string
	//public $ip; // string
	//public $ipOriginal;
	//public $ipv6; // bool
	//public $country; // string
	//public $countryCode; // string
	//public $region; // string
	//public $city; // string
	//public $isp; // string
	//public $hosted; // bool
	
	function __construct($snode) {
		//$this->snodekey = $snode['snodekey'];
		$this->snodekey = $snode['nodepubkey'];
		$this->tier = $snode['tier'];
		$this->address = $snode['address'];
		//$this->ip = getCleanIP($snode['addr']);
		//$this->ipOriginal = checkIpPort($snode['addr']);
		//$this->ipv6 = checkIfIpv6($this->ip);
		$this->timeLastSeen = checkInt($snode['timelastseen']);
		$this->timeLastSeenStr = getDateTime($snode['timelastseen']);
		$this->exr = $snode['exr'];
		$this->status = $snode['status'];
		$this->score = $snode['score'];
		$this->services = "Coming soon";
		//$this->services = getServices($snode['services']);
		//$this->servicesOriginal = checkServiceString($snode['services']);
        //$this->country = "UN";
        //$this->countryCode = "Unknown";
        //$this->region = "Unknown";
        //$this->city = "Unknown";
        //$this->isp = "Unknown";
        //$this->hosted = false;
    }			
}
?>