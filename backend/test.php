<?php


// Parse without sections
$ini_array = parse_ini_file("config.ini");
$daemonFile = $ini_array['daemonpath'];
print_r($daemonFile);


?>
