<?php

function loginScreen()
{
	global $sepsPageMessage;

	echo '<div class="bigform login"><form action="?" method="post"><input type="hidden" name="action" value="login" />';
	if (getVariableOrNull('noip')) echo '<input type="hidden" name="noipcheck" value="1" />';
	echo '<label for="username">Uživatel:</label> <input type="text" id="username" name="username" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('username')) . '" /><br />';
	echo '<label for="password">Heslo:</label> <input type="password" id="password" name="password" /><br />';
	echo '<input type="submit" value="Přihlásit se" />';
	if ($sepsPageMessage)
	{
		echo '<br /><a href="?action=resetpass">Zapomněl jsem heslo</a>';
	}
	echo '</form></div>';
}

function passwordResetForm($errmsg = null)
{
	echo '<div class="bigform passwordreset">';
	echo '<h2>Zapomenuté heslo</h2>';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="sendpassreset" />';
	if ($errmsg) echo "<div class='errmsg'>$errmsg</div>";
	echo '<div><small class="formhelp">Pokud jste zapomněli heslo, vyplňte své uživatelské jméno, nebo registrovaný e-mail a stiskněte tlačítko. Na váš e-mail bude doručena zpráva s dalšími pokyny.</small></div>';
	echo '<label for="username">Uživatelské jméno:</label> <input type="text" id="username" name="username" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('username')) . '" /><br />';
	echo '<label for="email">E-mail:</label> <input type="text" id="email" name="email" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('email')) . '" /><br />';
	echo '<input type="submit" value="Vygenerovat nové heslo" />';
	echo '</form></div>';
}

function sendPasswordReset()
{
	$username = getVariableOrNull('username');
	$email = getVariableOrNull('email');

	if (!$username && !$email)
	{
		passwordResetForm('Musíte zadat uživatelské jméno, nebo e-mailovou adresu.');
		return;
	}
	if ($username && $email)
	{
		passwordResetForm('Nezadávejte oba údaje, stačí zadat <em>buď</em> uživatelské jméno, <em>nebo</em> e-mailovou adresu.');
		return;
	}
	$query = null;
	if ($username)
	{
		$query = mysql_query("SELECT id, username, email FROM users WHERE username='" . mysql_real_escape_string($username) . "'");
	}
	else
	{
		$query = mysql_query("SELECT id, username, email FROM users WHERE email='" . mysql_real_escape_string($email) . "' AND emailvalidated=1 LIMIT 2");
	}

	$rows = mysql_num_rows($query);
	if ($rows != 1)
	{
		passwordResetForm($rows == 0 ? 'Žádný uživatel neodpovídá zadaným údajům' : 'Takovou e-mailovou adresu má více uživatelů, pro jejich rozlišení byste museli použít uživatelské jméno místo e-mailu');
		return;
	}

	$user = mysql_fetch_assoc($query);

	if (!sendInvitationTo($user['id'], $user['username'], $user['email'], 0, null, sepsEmailCodePasswordReset))
	{
		echo '<div class="errmsg">Chyba při odesílání nového hesla</div>';
		return;
	}

	echo '<div class="bigform passwordreset">';
	echo '<h2>Vygenerováno nové heslo</h2>';
	echo '<p>Na registrovaný e-mail byla odeslána zpráva s dalšími pokyny.</p>';
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
	global $sepsLoggedUser, $sepsLoggedUsername, $sepsLoggedUserHasUnvalidatedEmail, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess, $sepsLoggedUserEmail;

	if (!$sepsLoggedUser) return;

	$query = mysql_query("SELECT u.caption, u.username, u.email, u.emailvalidated FROM users u WHERE u.id=$sepsLoggedUser");
	$row = mysql_fetch_assoc($query);
	if (!$row)
	{
		performLogout();
		return;
	}

	$sepsLoggedUserCaption = $row['caption'];
	$sepsLoggedUsername = $row['username'];
	$sepsLoggedUserEmail = $row['emailvalidated'] ? $row['email'] : null;
	$sepsLoggedUserHasUnvalidatedEmail = $row['email'] && !$sepsLoggedUserEmail;

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
