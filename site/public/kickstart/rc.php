<?php include_once('secure.php');?>
#!/bin/bash

#Add the path
echo 'export PATH=$PATH:/opt/usr/bin/:/opt/bin:/opt/sbin:/opt/local/bin' >> /root/.bashrc && source /root/.bashrc
export PATH=$PATH:/opt/usr/bin/:/opt/bin:/opt/sbin:/opt/local/bin

#Shut down the internal interfaces
ip link set enp0s20f1 down
ip link set enp0s20f2 down
ip link set enp0s20f3 down
ip link set wlp1s0 down

#Temp workaround to clear logging
dmesg -n 1

#Run the flashing light sequenece

#Rename host check and run the highstate/reboot when done
<?php 
if (ISSET($_GET['ENV'])) {
  #This logic will need to be updated for future environments - ideally a DB call to something centrally managed
  $allowed = array("prdrekick", "prd", "test-aws", "dev");
  $env = strtolower($_GET['ENV']);
  if (in_array($env, $allowed)) { 
		echo '/usr/bin/salt-call state.highstate && yum -y update && init 0 > /dev/null 2>&1 &'."\n";
	}
  } else {echo "PARAMTER ERRROR IN KICKSTART";}
} ?>