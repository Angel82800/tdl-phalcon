<?php include_once('secure.php');?>
#SALT CORE
platform: UTM
product: shield
<?php 
if (ISSET($_GET['ENV'])) {
  #This logic will need to be updated for future environments - ideally a DB call to something centrally managed
  $allowed = array("prdrekick", "prd", "test-aws", "dev");
  $env = strtolower($_GET['ENV']);
  if (in_array($env, $allowed)) { 
    if ($env == "prdrekick") {$env = "prd";}
	echo "env: $env"; 
  } else {echo "PARAMTER ERRROR IN KICKSTART";}
} ?>

#SALT CORE
platform: UTM
product: shield
env: prd

#CUSTOMER INFO
customer-full: "Todyl Inc"
customer-short: Todyl
customer-id: 1
customer-region: NYC
customer-vertical: Tech
customer-activated: on

#RELEASE
shield-release: prototype

#SERVICE CONTROL
service-squid: on
service-squidguard: on
service-e2guardian: off
service-clamav: on
service-freshclam: on
service-bind: on
service-dhcp: on
service-nat: on
service-snort: on
service-lighttpd: on
service-openvpn: off

#FILTERING CONTROL
filter-adult: off
filter-adult+other: off
filter-gambling: off
filter-violence: off
filter-drugs: off
filter-ads: off

#PROXY CONTROL
proxy-bypass-ssl: on
proxy-bypass-skype: off
proxy-bypass-slack: off

#WIRELSS CONTROL
wireless-pass: demo2017!
wireless-enabled: on
wireless-ac: off

#VPN CONTROL
vpn-port: 1194
vpn-proto: udp
vpn-tunnel: tun
vpn-ip: "10.254.0.0"
vpn-mask: "255.255.0.0"
vpn-cidr: "10.254.0.0/16"