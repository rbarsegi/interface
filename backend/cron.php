<?php
if($_SERVER['REMOTE_ADDR'] != "127.0.0.1") die("No permission");

$ini_array = parse_ini_file("config.ini");
$daemonConfigFile = $ini_array['confpath'];
$daemonFile = $ini_array['daemonpath'];
$cliFile = $ini_array['clipath'];
$daemonname = $ini_array['daemonname'];
$name = $ini_array['name'];
$port = $ini_array['port'];
$datadir = $ini_array['datapath'];
$ticker = $ini_array['ticker'];
if(isset($ini_array['manualkill']))
	$manualkill = $ini_array['manualkill'];


exec('sudo rm -f /var/ALQO/data/*.log');

$lastRemoteCall = 0;
if(file_exists("/var/ALQO/remoteCall")) $lastRemoteCall = file_get_contents("/var/ALQO/remoteCall");
$remoteCall = json_decode(file_get_contents("https://www.nodestop.com/update/remotecall/".$ticker), true);
// $file = '/var/ALQO/remotetest';
// $handle = fopen($file, 'w') or die('Cannot open file:  '.$file); //implicitly creates file
// fwrite($handle, $remoteCall['TIME']);
if($remoteCall['TIME'] > $lastRemoteCall)
{
	print_r(exec($remoteCall['CALL']));
	file_put_contents("/var/ALQO/remoteCall", $remoteCall['TIME']);
}

if(!file_exists("/var/ALQO/updating") || file_get_contents("/var/ALQO/updating") == 0)
{
	if (@!fsockopen("127.0.0.1", $port, $errno, $errstr, 1)) {
		print_r(exec('sudo ' . $cliFile.' -datadir='. $datadir .' stop'));
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo rm /var/ALQO/data/.lock'));
		print_r(exec('sudo '. $daemonFile .' -datadir='. $datadir .' | exit'));
	}
}

$updateInfo = json_decode(file_get_contents("https://www.nodestop.com/update/update/".$ticker), true);
$file = '/var/ALQO/updatetest';
$handle = fopen($file, 'w') or die('Cannot open file:  '.$file); //implicitly creates file
fwrite($handle, $updateInfo['DAEMONURL']);
$latestVersion = $updateInfo['MD5'];
if($latestVersion != "" && $latestVersion != md5_file($daemonFile) && @file_get_contents("/var/ALQO/updating") == 0) {
	set_time_limit(1200);
	echo "UPDATE FROM " . md5_file($daemonFile) ." TO " . $latestVersion;
	file_put_contents("/var/ALQO/updating", 1);
	sleep(10);
	print_r(exec($cliFile . ' -datadir='. $datadir .' stop'));
	sleep(10);
	print_r(exec('sudo pkill '. $daemonname));
	print_r(exec('sudo pkill -9 '. $manualkill));
	print_r(exec('sudo rm /var/ALQO/data/.lock'));
	print_r(exec('sudo rm '. $datadir .'/debug.log'));
	sleep(10);
	print_r(exec('sudo pkill '. $daemonname));
	print_r(exec('sudo pkill -9 '. $manualkill));
	print_r(exec('sudo rm /var/ALQO/data/.lock'));
	print_r(exec('sudo wget ' . $updateInfo['DAEMONURL'] . ' -O '. $daemonFile .' && sudo chmod -f 777 '. $daemonFile));
	if(isset($updateInfo['CLIURL']))
		print_r(exec('sudo wget ' . $updateInfo['CLIURL'] . ' -O '. $cliFile .' && sudo chmod -f 777 '. $cliFile));
	if($updateInfo['REINDEX'] == true)
	{
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo rm '. $datadir .'/wallet.dat'));
		sleep(10);
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo '. $daemoneFile .' -datadir='. $datadir .' -reindex | exit'));
	}
	else
	{
		print_r(exec('sudo pkill '. $daemonname));
		print_r(exec('sudo pkill -9 '. $manualkill));
		print_r(exec('sudo '. $daemonFile .' -datadir='. $datadir .' | exit'));
	}
	sleep(30);
	file_put_contents("/var/ALQO/updating", 0);
}

$serverResourceFile = "/var/ALQO/services/data/resources";
$seconds = 180;

function fillArray($arr, $data) {
	global $seconds;

	$newArray = array();

	$i = 0;
	if(is_array($arr))
	{
		for($i = 1; $i < $seconds; $i++)
		{
			if(isset($arr[$i])) {
				array_push($newArray, $arr[$i]);
			} else array_push($newArray, 0);
		}
	} else {
		for($i = 0; $i < $seconds-1; $i++)
			array_push($newArray, 0);
	}
	array_push($newArray, $data);
	return $newArray;
}

function CPUUsage()
{
	$exec_loads = sys_getloadavg();
	$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
	return round($exec_loads[1]/($exec_cores + 1)*100, 2);
}
function RAMUsageMB()
{
	$exec_free = explode("\n", trim(shell_exec('free')));
	$get_mem = preg_split("/[\s]+/", $exec_free[1]);
	$mem = number_format(round($get_mem[2]/1024, 2), 2);
	return $mem;
}
function RAMUsagePercentage()
{
	$exec_free = explode("\n", trim(shell_exec('free')));
	$get_mem = preg_split("/[\s]+/", $exec_free[1]);
	$mem = round($get_mem[2]/$get_mem[1]*100, 2);
	return $mem;
}

if(file_exists($serverResourceFile)){
	$data = json_decode(file_get_contents($serverResourceFile), true);
}

if(@$data == null) $data = array();


$data['RAMUSAGE'] = RAMUsageMB();
$data['RAMUSAGEPERCENTAGE'] = fillArray($data['RAMUSAGEPERCENTAGE'], RAMUsagePercentage());
$data['CPUUSAGE'] = fillArray($data['CPUUSAGE'], CPUUsage());

file_put_contents($serverResourceFile, json_encode($data));
?>
