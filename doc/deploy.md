# Blocknet Monitor deployment

** These instructions are a Work In Progress **

Follow these steps to install a Blocknet Monitor instance on a server where
Blocknet Core is already running. These steps demonstrate the process on a 
Ubuntu 16.04 server. 

## 1. Install Apache and PHP
  ```
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-curl
```

## 2. Open firewall for Apache traffic (this example assumes you're using ufw)
```
sudo ufw allow "Apache"
```

## 3. Verify the server is running (it should be started automatically on installation)
```
sudo systemctl status apache2
```
   * screenshot here
   * point a web browser at the server and check the Apache default page is displayed

## 4. Setup virtual host
### 4a. configure domain name
```
export BM=/var/www/html/blockmonitor
sudo mkdir -p $BM
sudo chown -R $USER:$USER $BM
sudo chmod -R 755 $BM
```

### 4b. Create a test homepage
```
cat >$BM/index.html <<EOF
<html>
<head>
<title>Welcome to the Block Monitor!</title>
</head>
<body>
<h1>Be patient, the real Blocknet Monitor will be here soon!</h1>
</body>
</html>
EOF
```

### 4c. Create virtual host file
```
sudo cat >/etc/apache2/sites-available/monitor.conf <<EOF
<VirtualHost *:80>
   ServerAdmin your.email@domain.name
   ServerName your.servername.domain
   ServerAlias your.servername.domain
   DocumentRoot /var/www/html/blockmonitor
   ErrorLog ${APACHE_LOG_DIR}/error.log
   CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
```

### 4d. Enable the domain
```
sudo a2ensite monitor.conf
```

### 4e. Disable the default domain
```
sudo a2dissite 000-default.conf
```

### 4f. Reload the config and restart the service
```
sudo service apache2 reload
```
### 4g. Test for errors
```
sudo apache2ctl configtest
```

### 4h. Force resolution of AH00558 server name error if present
```
echo "ServerName your.servername.doimain" | sudo tee /etc/apache2/conf-available/servername.conf
sudo a2enconf servername
sudo service apache2 reload
sudo apache2ctl configtest
```

##5. clone the BM repo into the 
