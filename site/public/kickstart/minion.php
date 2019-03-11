<?php include_once('secure.php');?>
##Master
master: leonidas.todyl.com 

#ID (Dynamically replaced in kick.php)
id: null

#Security settings
hash_type: sha256

## Environment
<?php 
if (ISSET($_GET['ENV'])) {
  #This logic will need to be updated for future environments - ideally a DB call to something centrally managed
  $allowed = array("prdrekick", "prd", "test-aws", "dev");
  $env = strtolower($_GET['ENV']);
  if (in_array($env, $allowed)) { 
    if ($env == "prdrekick") {$env = "prd";}
	echo "environment: $env \n"; 
	echo "pillarenv: $env";
  } else {echo "PARAMTER ERRROR IN KICKSTART";}
} ?>