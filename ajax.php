<?php
session_start();

$ini_array = parse_ini_file("backend/config.ini");


$serverResourceFile = "/var/ALQO/services/data/resources";
$daemonConfigFile = $ini_array['confpath'];
$daemonFile = $ini_array['daemonpath'];
$cliFile = $ini_array['clipath'];
$datadir = $ini_array['datapath'];
$port = $ini_array['port'];
$name = $ini_array['name'];
$nodename = $ini_array['nodename'];
$daemonname = $ini_array['daemonname'];
$initialFile = "/var/ALQO/_initial";
$passwordFile = "/var/ALQO/_webinterface_pw";
$data['userID'] = "admin";
$data['userPass'] =  @file_get_contents($passwordFile);
$genname = $ini_array['genname'];
$manualkill = $ini_array['manualkill'];
$ticker = $ini_array['ticker'];


//////////////////////////////
//		GENERATE JSON
//////////////////////////////
function generateJson($arr)
{
	echo json_encode($arr);
	die();
}
//////////////////////////////
//		SERVERRESOURCES
//////////////////////////////
function ServerResources()
{
	global $serverResourceFile;
	echo file_get_contents($serverResourceFile);
}
//////////////////////////////
//		RAM TOTAL
//////////////////////////////
function RAMTotal()
{
	$exec_free = explode("\n", trim(shell_exec('free')));
	$get_mem = preg_split("/[\s]+/", $exec_free[1]);
	$mem = number_format(round($get_mem[1]/1024, 2), 2);
	return $mem;
}
//////////////////////////////
//		SYSINFO
//////////////////////////////
function Sysinfo()
{
	if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
		return null;
	}

	$os         = shell_exec('cat /etc/os-release');
	$listIds    = preg_match_all('/.*=/', $os, $matchListIds);
	$listIds    = $matchListIds[0];

	$listVal    = preg_match_all('/=.*/', $os, $matchListVal);
	$listVal    = $matchListVal[0];

	array_walk($listIds, function(&$v, $k){
		$v = strtolower(str_replace('=', '', $v));
	});

	array_walk($listVal, function(&$v, $k){
		$v = preg_replace('/=|"/', '', $v);
	});

	$arr = array_combine($listIds, $listVal);
	$arr['TotalRAM'] = RAMTotal();
	return $arr;
}
//////////////////////////////
//		DAEMON DATA
//////////////////////////////
function readInfo() {
	$d = file_get_contents("/var/ALQO/services/data/getinfo");
	return json_decode($d, true);
}
function readPeerInfo() {
	$d = file_get_contents("/var/ALQO/services/data/getpeerinfo");
	return json_decode($d, true);

}
function readMasterNodeListFull() {
	$d = file_get_contents("/var/ALQO/services/data/masternode_list_full");
	return json_decode($d, true);

}
function readMasterNodeListRank() {
	$d = file_get_contents("/var/ALQO/services/data/masternode_list_rank");
	return json_decode($d, true);

}
function readMasterNodeStatus() {
	$d = file_get_contents("/var/ALQO/services/data/masternode_status");
	return json_decode($d, true);

}
//////////////////////////////
//		PAYOUT DATA
//////////////////////////////
function getPayoutData($walletAddr) {
	$d = file_get_contents("https://hosting.alqo.org/api.php?walletAddr=".$walletAddr);
	return json_decode($d, true);
}
//////////////////////////////
//		MASTERNODE INFO
//////////////////////////////
function Info()
{
	global $port;
	$arr = array();

	$info = readInfo();
	$peerInfo = readPeerInfo();
	$masternodeListFull = readMasterNodeListFull();
	$masternodeListRank = readMasterNodeListRank();
	$masternodeStatus = readMasterNodeStatus();

	if (@fsockopen("127.0.0.1", $port, $errno, $errstr, 1)) $arr['status'] = true; else $arr['status'] = false;
	$arr['block'] = $info['blocks'];
	$arr['difficulty'] = $info['difficulty'];
	$arr['walletVersion'] = $info['walletversion'];
	$arr['protocolVersion'] = $info['protocolversion'];
	$arr['version'] = $info['version'];
	$arr['connections'] = $info['connections'];

	$mnStatus = false;
	if(strpos($masternodeStatus['status'],'success') != false) $mnStatus = true;
	$arr['masternodeStatus'] = $mnStatus;

	$arr['masternodeIp'] = null;
	$arr['masternodePayoutWallet'] = null;
	$arr['masternodeWalletBalance'] = null;
	if($mnStatus)
	{
		$arr['masternodeIp'] = $masternodeStatus['service'];
		$arr['masternodePayoutWallet'] = $masternodeStatus['pubkey'];
		$arr['masternodeWalletBalance'] = file_get_contents("http://explorer.alqo.org/ext/getbalance/".$arr['masternodePayoutWallet']);
	}
	$arr['masternodePayoutData'] = getPayoutData($arr['masternodePayoutWallet']);
	return $arr;
}

//////////////////////////////
//		DAEMON SETTINGS & RESTART
//////////////////////////////
function getLine($c)
{
	global $daemonConfigFile;
	shell_exec('sudo chmod -f 777 ' . $daemonConfigFile);
	$handle = fopen($daemonConfigFile, "r");
	$v = "";
	if($handle) {
		while(($line = fgets($handle)) !== false) {
			if(strpos($line, $c."=") !== false) {
				$v = explode("=", $line)[1];
				break;
			}
		}
	}
	$return = str_replace("\n", "", $v);
	$return = str_replace("\r", "", $return);
	$return = str_replace(" ", "", $return);
	return $return;
}
function setLine($c, $v, $nv)
{
	global $daemonConfigFile;
	shell_exec('sudo chmod -f 777 ' . $daemonConfigFile);
	$d = file_get_contents($daemonConfigFile);
	$d = str_replace($c."=".$v, $c."=".$nv, $d);
	file_put_contents($daemonConfigFile, $d);
}
function restartDaemon()
{
	global $daemonFile;
	global $cliFile;
	global $datadir;
	global $name;
	global $daemonname;
	global $ticker;
	$updateInfo = json_decode(file_get_contents("https://www.nodestop.com/update/update/".$ticker), true);
	$latestVersion = $updateInfo['MD5'];
	if($latestVersion != "" && $latestVersion != md5_file($daemonFile)) {
		set_time_limit(1200);
		echo "UPDATE FROM " . md5_file($daemonFile) ." TO " . $latestVersion;
		file_put_contents("/var/ALQO/updating", 1);
		sleep(10);
		print_r(exec($cliFile . ' -datadir='. $datadir .' stop'));
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo rm '. $datadir .'/debug.log'));
		sleep(10);
		print_r(exec($updateInfo['ADDITIONALCMD']));
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo wget ' . $updateInfo['DAEMONURL'] . ' -O ' . $daemonFile . ' && sudo chmod -f 777 ' . $daemonFile));
		if(isset($updateInfo['CLIURL']))
			print_r(exec('sudo wget ' . $updateInfo['CLIURL'] . ' -O '. $cliFile .' && sudo chmod -f 777 '. $cliFile));
		if($updateInfo['REINDEX'] == true)
		{
			sleep(10);
			print_r(exec('sudo rm '. $datadir .'/wallet.dat'));
			sleep(10);
			print_r(exec('sudo pkill '. $daemonname));
			print_r(exec('sudo pkill -9 '. $manualkill));
			print_r(exec('sudo rm /var/ALQO/data/.lock'));
			print_r(exec('sudo '. $daemonFile .' -datadir='. $datadir .' -reindex | exit'));
		} else {
			print_r(exec('sudo pkill '. $daemonname));
			print_r(exec('sudo pkill -9 '. $manualkill));
			print_r(exec('sudo rm /var/ALQO/data/.lock'));
			print_r(exec('sudo '. $daemonFile .' -datadir='. $datadir .' | exit'));
		}
		sleep(30);
		file_put_contents("/var/ALQO/updating", 0);
	} else {
		print_r(exec('sudo '. $cliFile . ' -datadir='. $datadir .' stop'));
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo rm /var/ALQO/data/.lock'));
		print_r(exec('sudo '. $daemonFile .' -datadir='. $datadir .' | exit'));
		die();
	}
}

function reindexDaemon()
{
	global $daemonFile;
	global $cliFile;
	global $datadir;
	print_r(exec('sudo ' . $cliFile . ' -datadir='. $datadir .' stop'));
	sleep(10);
	print_r(exec('sudo pkill '. $daemonname));
	print_r(exec('sudo pkill -9 '. $manualkill));
	print_r(exec('sudo rm /var/ALQO/data/.lock'));
	print_r(exec('sudo ' . $daemonFile .' -datadir='. $datadir .' -reindex | exit'));
	die();
}

function resetServer()
{
	global $daemonFile;
	global $cliFile;
	global $datadir;
	global $daemonname;
	print_r(exec('sudo ' . $cliFile . ' -datadir='. $datadir .' stop'));
	print_r(exec('sudo pkill '. $daemonname));
	print_r(exec('sudo pkill -9 '. $manualkill));
	print_r(exec('sudo /var/www/html/backend/resetServer.sh'));
	sleep(10);
	print_r(exec('sudo pkill '. $daemonname));
	print_r(exec('sudo pkill -9 '. $manualkill));
	print_r(exec('sudo rm /var/ALQO/data/.lock'));
	restartDaemon();
	die();
}

function checkIsMasternode()
{
	global $nodename;
	echo getLine($nodename);
}
function checkIsStaking()
{
	echo getLine("staking");
}
function setMasternode($nv)
{
	global $nodename;
	$v =getLine($nodename);
	setLine($nodename, $v, $nv);
	echo $nv;
}
function setStaking($nv)
{
	$v =getLine("staking");
	setLine("staking", $v, $nv);
	echo $nv;
}

function getPrivKey()
{
	global $nodename;
	echo getLine($nodename . "privkey");
}
function setPrivKey($nv)
{
	global $nodename;
	$v =getLine($nodename . "privkey");
	setLine($nodename . "privkey", $v, $nv);
	echo $v;
}


//////////////////////////////
//		MAIN
//////////////////////////////
if(isset($_GET['initialCode'])) {
	if(file_exists($initialFile)) {
		$initialCode = file_get_contents($initialFile);
		$initialCode = str_replace("\n", "", $initialCode);
		$initialCode = str_replace("\r", "", $initialCode);
		if($initialCode == $_GET['initialCode']) die("true");
	}
	die("false");
}


if(isset($_GET['fresh'])) {
	$genkey = getLine($nodename . "privkey");
	if ($genkey == "" | $genkey == "0") {
		do {
			sleep(10);
			if (isset($genname))
				exec('sudo ' . $cliFile . ' -datadir='. $datadir .' '.$genname .' genkey 2>&1',$newgenkey);
			else
				exec('sudo ' . $cliFile . ' -datadir='. $datadir .' '.$nodename .' genkey 2>&1',$newgenkey);
		} while(strpos(strtolower(end($newgenkey)),'connect')!==false || strpos(strtolower(end($newgenkey)),'loading')!==false || strpos(strtolower(end($genkey)),'response')!==false);

		$v =getLine($nodename . "privkey");
		setLine($nodename . "privkey", $v, end($newgenkey));

		$v =getLine($nodename);
		setLine($nodename, $v, '1');
		exec('sudo ' . $cliFile . ' -datadir='. $datadir .' stop');

		sleep(10);
		exec('sudo pkill '. $daemonname);
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo rm /var/ALQO/data/.lock'));
		exec('sudo ' . $daemonFile .' -datadir='. $datadir .' | exit');
		sleep(10);
		exec('/var/ALQO/data/services/service.sh');
		echo end($newgenkey);
	}
	die();
}



if(isset($_SESSION['loggedIn']) && isset($_SESSION['userID'])) {
	if($_SESSION['loggedIn'] == true && $_SESSION['userID'] == $data['userID']) {

		if(isset($_GET['sysinfo']))
			generateJson(Sysinfo());

		if(isset($_GET['serverresources']))
			ServerResources();

		if(isset($_GET['info']))
			generateJson(Info());

		if(isset($_GET['isMasternode']))
			checkIsMasternode();

		if(isset($_GET['isStaking']))
			checkIsStaking();

		if(isset($_GET['setMasternode']))
			setMasternode($_GET['setMasternode']);

		if(isset($_GET['setStaking']))
			checkIsMasternode($_GET['setStaking']);

		if(isset($_GET['getPrivKey']))
			getPrivKey();

		if(isset($_GET['setPrivKey']))
			setPrivKey($_GET['setPrivKey']);

		if(isset($_GET['restartDaemon']))
			echo restartDaemon();

		if(isset($_GET['reindexDaemon']))
			echo reindexDaemon();

		if(isset($_GET['resetServer']))
			echo resetServer();

	}
	die();
}

die("Permission denied.");

?>
