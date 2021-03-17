# Blocknet Monitor v0.1.1

Blocknet Monitor (BPM) is a visualisation and monitoring system for the Blocknet network.
![](assets/images/blocknetmonitor.png)


## Features

* Extensive dashboard with general information about the node, connected peers and the blockchain
* Overview of servicenodes
* Overview of BlockDX open orders
* Overview of BlockDX recently completed orders
* Overview of active proposals in the Blocknet Decentralised Governance system
* Overview of past proposals in the Blocknet Decentralised Governance system
* Overview of all connected peers including country, ISP, client, traffic usage, supported services...
* Overview of the last received blocks
* Overview of recent orphaned blocks / alternative chains
* Overview of the memory pool and the inflight transactions


## Requirements

* Blocknet v4.3.1+
* Web server (e.g. Apache, PHP built-in web server)
* PHP 7.0.0+
*   php-cli php-common php-curl php-fpm php-json php-mbstring php-opcache php-readline
* cURL
* ccxt


## Installation

1. Download Blocknet Platorm Monitor either from [here](https://github.com/walkjivefly/blocknet-monitor/releases) or by cloning this  repository.
2. Edit `src/Config.php` to enter your blocknetd RPC credentials, set a password and change other settings.
3. Upload the folder to the public directory of your web server. If the folder is accesible via the internet, I recommend renaming the folder to something unique. Although Blocknet Monitor is password protected and access can be limited to a specific IP, there can be security flaws and bugs.
4. Open the URL to the folder in your browser and login with the password choosen in `src/Config.php`.
5. Optional: Run `chmod -R 770 /path-to-folder/{data, src, views}`. Only necessary for non Apache (`AllowOverride All` necessary) and publicly accessible web server. For more information, read the next section.


## Security
 
* Access to Blocknet Monitor is by default limited to localhost. This can be expanded to a specific IP or disabled. If disabled, make sure to protect the Blocknet Monitor folder (.htaccess, rename it to something unique 
that an attacker will not guess). An attacker could "guess" your password, since there is no build-in brute force protection (if IP protection is disabled).
* The `data` folder contains your rules, logs and geo information about your peers. Make sure to protect (e.g. `chmod -R 770 data`) this sensitive information if your web server is publicly accessible. The previously mentioned
IP protection doesn't work here. If you use `Apache` you are fine, since the folder is protected with `.htaccess` (make sure `AllowOverride All` is set in your `apache2.conf` file).


## Roadmap

- [ ] Loadsa brilliant stuff 


## Suggested enhancements for community participation

- [ ] Improve project structure
- [ ] Improve error handling
- [ ] More help icons
- [ ] Use popover for help
- [ ] Display expanded peer/block info (popup)
- [ ] More customization settings
- [ ] Sort mempool tx, request more



## Donate

If you find the Blocknet Monitor useful I'm happy to accept donations at 
[Bm4tVuT87Ti4gKud9QZZhzBY4fe22QbLvW](https://chainz.cryptoid.info/block/search.dws?q=Bm4tVuT87Ti4gKud9QZZhzBY4fe22QbLvW)

