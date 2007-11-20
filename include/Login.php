<?php

function loginScreen()
{
	echo '<div class="bigform login"><form action="?" method="post"><input type="hidden" name="action" value="login" />';
	if (getVariableOrNull('noip')) echo '<input type="hidden" name="noipcheck" value="1" />';
	echo 'Uživatel: <input type="text" name="username" value="' . htmlspecialchars(getVariableOrNull('username')) . '" /><br />';
	echo 'Heslo: <input type="password" name="password" /><br />';
	echo '<input type="submit" value="Přihlásit se" />';
	echo '</form></div>';
}

function performLogout()
{
	global $sepsLoggedUser;
	$sepsLoggedUser = 0;
	if (isset($_SESSION['loggeduser'])) unset($_SESSION['loggeduser']);
	if (isset($_SESSION['noipcheck'])) unset($_SESSION['noipcheck']);
	if (isset($_SESSION['loginip'])) unset($_SESSION['loginip']);
}

function performLogin()
{
	global $sepsLoggedUser;

	performLogout();

	$username = getVariableOrNull('username');
	$password = getVariableOrNull('password');

	$loginSuccess = tryLogin($username, $password);

	if ($loginSuccess)
	{
		$sepsLoggedUser = $loginSuccess;

		$_SESSION['loggeduser'] = $sepsLoggedUser;
		if (getVariableOrNull('noipcheck')) $_SESSION['noipcheck'] = 1;
		else $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];
	}
	else
	{
		global $sepsPageMessage;
		$sepsPageMessage = 'Chybné jméno nebo heslo. Zkuste to znovu.';
	}
}

function loadLoggedUserInformation()
{
	global $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess, $sepsLoggedUserEmail;

	if (!$sepsLoggedUser) return;

	$query = mysql_query("SELECT u.caption, u.email, u.emailvalidated FROM users u WHERE u.id=$sepsLoggedUser");
	$row = mysql_fetch_assoc($query);
	if (!$row)
	{
		performLogout();
		return;
	}

	$sepsLoggedUserCaption = $row['caption'];
	$sepsLoggedUserEmail = $row['emailvalidated'] ? $row['email'] : null;

	$query = mysql_query("SELECT BIT_OR(access) FROM usersprojects WHERE user=$sepsLoggedUser");
	$access = mysql_fetch_row($query);
	$sepsLoggedUserMaxAccess = $access[0];
}

function hashPassword($password)
{
	global $sepsPasswordHashingAlgorithm;
	return hash($sepsPasswordHashingAlgorithm, $password);
}

function tryLogin($username, $password)
{
	if (!$username) return FALSE;

	$hash = hashPassword($password);

	$query = mysql_query("SELECT u.id FROM users u WHERE u.username='" . mysql_real_escape_string($username) . "' AND u.password='$hash'");
	$row = mysql_fetch_row($query);
	if ($row) return $row[0]; else return FALSE;
}
