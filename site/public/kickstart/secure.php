<?php
//Variables
$szRemoteIP = get_ip();

//Allowed IPs
$arrAllowedIPs = array("38.88.18.131", //Portwell
                       "38.88.18.132", //Portwell
                       "38.88.18.133", //Portwell
                       "38.88.18.134", //Portwell
					   "38.88.18.135", //Portwell
					   "38.88.18.136", //Portwell
					   "38.88.18.137", //Portwell
					   "38.88.18.138", //Portwell
					   "38.88.18.139", //Portwell
					   "38.88.18.140", //Portwell
					   "38.88.18.141", //Portwell
					   "108.30.141.162", //BK
					   "65.206.95.146", //WeWork-CB
                       "52.1.182.131", //USEAST.VPN
					   "10.50.10.1"); //Dev

if (!in_array($szRemoteIP, $arrAllowedIPs) && check_key() === false )
{
  //Deny Access
  header("Location: https://www.todyl.com");
  exit(0);
}

function get_ip() {
		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
		} else {
			$headers = $_SERVER;
		}
		if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$the_ip = $headers['X-Forwarded-For'];
		} elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )
		) {
			$the_ip = $headers['HTTP_X_FORWARDED_FOR'];
		} else {
			
			$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		}
		return $the_ip;
	}

function check_key() {
	    global $AllowedAuthKey;
		if (file_exists('/etc/secure.xml')) {
          $xml = simplexml_load_file('/etc/secure.xml');
          $AllowedAuthKey = $xml->kickstart->{'auth-key'};
        } else {
           exit('Failed to open xml');
        }
		if ( isset ( $_GET["key"] ) ) {
			if ( $_GET["key"] == $AllowedAuthKey ) { return true; }
		} else { return false; }
	}
?>