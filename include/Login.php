<?php

function loginScreen()
{
	global $sepsPageMessage, $sepsTitle;

	echo '<div class="container" style="margin-top:30px"><div class="col-md-4 col-md-offset-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><strong>Přihlášení do systému ' . htmlspecialchars($sepsTitle) . '</strong></h3></div><div class="panel-body">';
	echo '<form action="?' . htmlspecialchars($_SERVER['QUERY_STRING']) . '" method="post"><input type="hidden" name="action" value="login" />';
	generateCsrfToken();
	if (getVariableOrNull('noip')) echo '<input type="hidden" name="noipcheck" value="1" />';
	if ($sepsPageMessage) echo "<div class='alert alert-danger'>$sepsPageMessage</div>";
	echo '<div class="form-group"><label for="username">Uživatel:</label><input type="text" class="form-control" id="username" name="username" placeholder="Zadejte uživatelské jméno" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('username')) . '" /></div>';
	echo '<div class="form-group"><label for="password">Heslo';
	//if ($sepsPageMessage)
	{
		echo ' (<a href="?action=resetpass" tabindex="5">zapomenuté heslo</a>)';
	}
	echo ':</label><input type="password" class="form-control" id="password" name="password" placeholder="Heslo" /></div>';
	echo '<button type="submit" class="btn btn-primary">Přihlásit se</button>';
	echo '</form>';
	echo '</div></div></div></div>';
}

function passwordResetForm($errmsg = null)
{
	echo '<div class="container" style="margin-top:30px"><div class="col-md-4 col-md-offset-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><strong>Zapomenuté heslo</strong></h3></div><div class="panel-body">';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="sendpassreset" />';
	generateCsrfToken();
	echo '<span class="help-block">Pokud jste zapomněli heslo, vyplňte své uživatelské jméno, nebo registrovaný e-mail a stiskněte tlačítko. Na váš e-mail bude doručena zpráva s dalšími pokyny.</span>';
	if ($errmsg) echo "<div class='alert alert-danger'>$errmsg</div>";
	echo '<div class="form-group"><label for="username">Uživatelské jméno:</label><input type="text" class="form-control" id="username" name="username" placeholder="Zadejte uživatelské jméno" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('username')) . '" /></div>';
	echo '<div class="form-group"><label for="email">E-mail:</label><input type="text" class="form-control" id="email" name="email" placeholder="nebo registrovaný e-mail" maxlength="100" value="' . htmlspecialchars(getVariableOrNull('email')) . '" /></div>';
	echo '<button type="submit" class="btn btn-primary">Vygenerovat nové heslo</button> <a href="?" class="btn btn-default">Storno</a>';
	echo '</form>';
	echo '</div></div></div></div>';
}

function sendPasswordReset()
{
	$username = getVariableOrNull('username');
	$email = strtolower(trim(getVariableOrNull('email')));

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

	echo '<div class="container" style="margin-top:30px"><div class="col-md-4 col-md-offset-4"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><strong>Vygenerováno nové heslo</strong></h3></div><div class="panel-body">';
	echo "<div class='alert alert-default'>Na registrovaný e-mail byla odeslána zpráva s dalšími pokyny.</div>";
	echo '</div></div></div></div>';
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
	global $sepsLoggedUser, $action;

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

		// fuj! záplata
		if (isset($_GET['action']))
		{
			$getaction = $_GET['action'];
			if ($getaction && $getaction != 'logout') $action = $getaction;
		}
	}
	else
	{
		global $sepsPageMessage;
		$sepsPageMessage = 'Chybné jméno nebo heslo. Zkuste to znovu.';
		return false;
	}
}

function loadLoggedUserInformation()
{
	global $sepsLoggedUser, $sepsLoggedUsername, $sepsLoggedUserHasUnvalidatedEmail, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess, $sepsLoggedUserEmail, $sepsLoggedUserGlobalRights;

	if (!$sepsLoggedUser) return;

	$query = mysql_query("SELECT u.caption, u.username, u.email, u.emailvalidated, u.globalrights FROM users u WHERE u.id=$sepsLoggedUser");
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
	$sepsLoggedUserGlobalRights = $row['globalrights'];

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
	$username = mb_strtolower(trim($username));

	$hash = hashPassword($password);

	$query = mysql_query("SELECT u.id FROM users u WHERE u.username='" . mysql_real_escape_string($username) . "' AND u.password='$hash'");
	$row = mysql_fetch_row($query);
	if ($row) return $row[0]; else return FALSE;
}

function tryApiLogin($username, $token)
{
	if (!$username || !$token) return FALSE;
	$username = mb_strtolower(trim($username));

	$query = mysql_query("SELECT u.id FROM users u WHERE u.username='" . mysql_real_escape_string($username) . "' AND u.apitoken='" . mysql_real_escape_string($token) . "'");
	$row = mysql_fetch_row($query);
	if ($row) return $row[0]; else return FALSE;
}

function generateCsrfToken()
{
	global $sepsCsrfToken;
	if (isset($sepsCsrfToken))
	{
		if ($_SESSION['csrftoken'] != $sepsCsrfToken) die('Unexpected token value');
		$token = $sepsCsrfToken;
	}
	else
	{
		$token = generateRandomToken(10);
		$_SESSION['csrftoken'] = $token;
		$sepsCsrfToken = $token;
	}
	echo "<input name='csrftoken' type='hidden' value='$token' />";
}

function verifyCsrfToken()
{
	$sessionToken = $_SESSION['csrftoken'];
	$formToken = getVariableOrNull('csrftoken');
	if ($sessionToken && $formToken && $formToken == $sessionToken) return true;

	performLogout();
	header('HTTP/1.1 403 Forbidden');
	echo 'Invalid token';
	return false;
}
