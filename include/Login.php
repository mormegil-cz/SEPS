<?php

function loginScreen()
{
	echo '<div class="loginform"><form action="?" method="post"><input type="hidden" name="action" value="login" />';
	if (getVariableOrNull('noip')) echo '<input type="hidden" name="noipcheck" value="1" />';
	echo 'Uživatel: <input type="text" name="username" /><br />';
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

	// TODO: heslo
	$loginSuccess = true;

	if ($loginSuccess)
	{
		$sepsLoggedUser = 1;

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
	global $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess;

	if (!$sepsLoggedUser) return;

	$query = mysql_query("SELECT u.caption FROM users u WHERE u.id=$sepsLoggedUser");
	$row = mysql_fetch_assoc($query);
	if (!$row)
	{
		performLogout();
		return;
	}

	$sepsLoggedUserCaption = $row['caption'];

	$query = mysql_query("SELECT BIT_OR(access) FROM usersprojects WHERE user=$sepsLoggedUser");
	$access = mysql_fetch_row($query);
	$sepsLoggedUserMaxAccess = $access[0];
}
