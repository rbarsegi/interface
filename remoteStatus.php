<?php

$ini_array = parse_ini_file("backend/config.ini");
$port = $ini_array['port'];
$setaddress = file_get_contents("/var/ALQO/address");
$cliFile = $ini_array['clipath'];
$datadir = $ini_array['datapath'];

$status = true;
if (@!fsockopen("127.0.0.1", $port, $errno, $errstr, 1)) {
	$status = false;
}

$info = json_decode(file_get_contents("/var/ALQO/services/data/getinfo"), true);
$jsonmstatus = json_decode(file_get_contents("/var/ALQO/services/data/masternode_status"), true);
$mstatus = stripslashes(file_get_contents("/var/ALQO/services/data/masternode_status"));

if(isset($jsonmstatus["addr"])!=false) $currentaddress=$jsonmstatus["addr"];
if(isset($jsonmstatus["pubkey"])!=false) $currentaddress=$jsonmstatus["pubkey"];
if(isset($jsonmstatus["payee"])!=false) $currentaddress=$jsonmstatus["payee"];

if ($setaddress != $currentaddress)
{
	$file = '/var/ALQO/address';
	$handle = fopen($file, 'w') or die('Cannot open file:  '.$file); //implicitly creates file
	fwrite($handle, $currentaddress);
	exec($cliFile . ' -datadir='. $datadir .' importaddress '. $currentaddress . ' true');
}


exec('sudo ' . $cliFile . ' -datadir='. $datadir . ' getbalance "*" 1 true true', $balance);

if(strpos(end($balance),'code')!==false || strpos(end($balance),'error')!==false)
{
	exec('sudo ' . $cliFile . ' -datadir='. $datadir . ' getbalance "*" 1 true', $balance);
}

$return["status"] = $status;
$return["version"] = $info["version"];
$return["mstatus"] = $mstatus;
$return["info"] = $info;
$return["balance"] = end($balance);

echo json_encode($return);

?>
