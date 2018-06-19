<?php
session_start();

$ini_array = parse_ini_file("backend/config.ini");


$serverResourceFile = "/var/ALQO/services/data/resources";
$daemonConfigFile = $ini_array['confpath'];
$daemonFile = $ini_array['daemonpath'];
$initialFile = "/var/ALQO/_initial";
$passwordFile = "/var/ALQO/_webinterface_pw";
$data['userID'] = "admin";
$data['userPass'] =  @file_get_contents($passwordFile);



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
	$arr = array();

	$info = readInfo();
	$peerInfo = readPeerInfo();
	$masternodeListFull = readMasterNodeListFull();
	$masternodeListRank = readMasterNodeListRank();
	$masternodeStatus = readMasterNodeStatus();

	if (@fsockopen("127.0.0.1", 55000, $errno, $errstr, 1)) $arr['status'] = true; else $arr['status'] = false;
	$arr['block'] = $info['blocks'];
	$arr['difficulty'] = $info['difficulty'];
	$arr['walletVersion'] = $info['walletversion'];
	$arr['protocolVersion'] = $info['protocolversion'];
	$arr['version'] = $info['version'];
	$arr['connections'] = $info['connections'];

	$mnStatus = false;
	if($masternodeStatus['status'] == "Masternode successfully started") $mnStatus = true;
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
	$updateInfo = json_decode(file_get_contents("https://builds.alqo.org/update.php"), true);
	$latestVersion = $updateInfo['MD5'];
	if($latestVersion != "" && $latestVersion != md5_file($daemonFile)) {
		set_time_limit(1200);
		echo "UPDATE FROM " . md5_file($daemonFile) ." TO " . $latestVersion;
		file_put_contents("/var/ALQO/updating", 1);
		sleep(10);
		print_r(exec('/var/ALQO/alqo-cli -datadir=/var/ALQO/data stop'));
		sleep(10);
		print_r(exec('sudo rm /var/ALQO/data/debug.log'));
		sleep(10);
		print_r(exec('sudo wget ' . $updateInfo['URL'] . ' -O ' . $daemonFile . ' && sudo chmod -f 777 /var/ALQO/alqod'));
		if($updateInfo['REINDEX'] == true)
		{
			sleep(10);
			print_r(exec('sudo rm /var/ALQO/data/wallet.dat'));
			sleep(10);
			print_r(exec('sudo '. $daemonFile .' -datadir=/var/ALQO/data -reindex | exit'));
		} else {
			print_r(exec('sudo '. $daemonFile .' -datadir=/var/ALQO/data | exit'));
		}
		sleep(30);
		file_put_contents("/var/ALQO/updating", 0);
	} else {
		print_r(exec('/var/ALQO/alqo-cli -datadir=/var/ALQO/data stop'));
		sleep(10);
		print_r(exec('sudo '. $daemonFile .' -datadir=/var/ALQO/data | exit'));
		print_r('sudo '. $daemonFile .' -datadir=/var/ALQO/data | exit');
		die();
	}
}

function reindexDaemon()
{
	print_r(exec('/var/ALQO/alqo-cli -datadir=/var/ALQO/data stop'));
	sleep(10);
	print_r(exec('sudo ' . $daemonFile .' -datadir=/var/ALQO/data -reindex | exit'));
	die();
}

function resetServer()
{
	print_r(exec('/var/ALQO/alqo-cli -datadir=/var/ALQO/data stop'));
	sleep(10);
	print_r(exec('sudo /var/www/html/backend/resetServer.sh'));
	sleep(10);
	print_r(exec('sudo /var/ALQO/alqod -datadir=/var/ALQO/data | exit'));
	die();
}

function checkIsMasternode()
{
	echo getLine("masternode");
}
function checkIsStaking()
{
	echo getLine("staking");
}
function setMasternode($nv)
{
	$v =getLine("masternode");
	setLine("masternode", $v, $nv);
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
	echo getLine("masternodeprivkey");
}
function setPrivKey($nv)
{
	$v =getLine("masternodeprivkey");
	setLine("masternodeprivkey", $v, $nv);
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
