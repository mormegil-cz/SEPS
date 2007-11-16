<?php

session_name('sepssession');
session_start();

$sepsLoggedUser = getSessionOrNull('loggeduser');
$action = getVariableOrNull('action');

if (!getSessionOrNull('noipcheck'))
{
	$loginIp = getSessionOrNull('loginip');
	if ($sepsLoggedUser && $_SERVER['REMOTE_ADDR'] != $loginIp)
	{
		unset($_SESSION['loggeduser']);
		header('HTTP/1.0 403 Forbidden');
		die('Změna IP adresy; přihlašte se znovu');
	}
}

include('./include/Database.php');
initDatabase();

$sepsLoggedUserCaption = null;
$sepsLoggedUserMaxAccess = 0;

require_once('./include/Login.php');
loadLoggedUserInformation();
