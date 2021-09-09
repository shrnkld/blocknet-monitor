<?php

namespace App;

class Config {
	
	// Blocknet Monitor password for login. You should additionally change the name of 
    // CPM folder to something unique, if accessible via the web. You can also limit the access 
    // to a specific IP with the option below.
	const PASSWORD = 'LOGIN-PASSWORD';
    // IP that can can access Blocknet Monitor (by default only localhost (IPv4/v6) is allowed).
    // If empty (''), any IP can access. If 'localhost', only localhost can access. 
    // If specific IP (e.g. '84.12.32.297'), localhost and the specific IP can access.
	const ACCESS_IP = 'localhost';	
	const ACCESS_IP2 = 'localhost';	
	const ACCESS_IP3 = 'localhost';	
	
	
	// IP of blocknetd RPC server. Usually localhost. Be careful with remote servers. The connection is not encrypted.
	const RPC_IP = '127.0.0.1:41414';
	// RPC username / rpcauth specified in bitcoin.conf
	const RPC_USER = 'USERNAME';
	// RPC password / rpcauth specified in bitcoin.conf
	const RPC_PASSWORD = 'PASSWORD';

	 // Debug RPC entries and exits
	 const DEBUG = FALSE;
	
	// Use ip-api.com to get country, city and isp of peers. API is limited to 150 requests per minutes.
	// Peer geo data is stored as long as the peer is connected. A page reload (main/peers) only 
    // causes an API request if new peers connected (older than 2 minutes) since the last load. Up to 
    // 100 ips/peers are checked per request. You should not run into any trouble with the API limit.
    // Set the FALSE to not use the API.
	const PEERS_GEO = TRUE;
	const NODES_GEO = FALSE;
	// Maxmimum of seconds to wait for response from ip-api.com
    const GEO_TIMEOUT = 2;

	// Number of TXs displayed on mempool page
	const DISPLAY_TXS = 100;
	// Number of blocks displayed on blocks page
	const DISPLAY_BLOCKS = 25;	
	// Number of forks displayed on forks page
	const DISPLAY_FORKS = 25;

}
?>