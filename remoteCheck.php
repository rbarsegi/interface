<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: text/plain");

exec('sudo chmod -f 777 /var/ALQO/data/debug.log');

echo file_get_contents("/var/ALQO/services/data/getinfo");

echo "

";

$debugLog = "/var/ALQO/data/debug.log";
echo file_get_contents($debugLog);
?>