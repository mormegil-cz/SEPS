<?php

require_once('./include/Logging.php');

function sendInvitationTo($email, $project, $projectname)
{
    global $sepsFullBaseUri, $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUsername, $sepsTitle, $sepsAdminMail, $sepsSoftwareVersionLine, $sepsLoggedUserEmail;

	if (!$email || !$sepsAdminMail) return false;

	$code = '';
	for($i = 0; $i < sepsEmailCodeLength; $i++)
	{
		$code .= chr(ord('A') + mt_rand(0, 25));
	}
	$invitationuri = $sepsFullBaseUri . '?inv=' . $code;

	$projectOrNull = $project ? $project : "NULL";
	$date = strftime('%Y-%m-%d %H:%M:%S');
	if (!mysql_query("INSERT INTO emailcodes (email, code, fromuser, createdate, forproject) VALUES ('" . mysql_real_escape_string($email) . "', '" . mysql_real_escape_string($code) . "' , $sepsLoggedUser, '$date', $projectOrNull)") || !mysql_affected_rows())
	{
		echo '<div class="errmsg">MySQL error: ' . mysql_error() . '</div>';
		return false;
	}

	if ($project)
	{
		$subj = encodeMailHeader("Pozvánka do projektu $projectname");
		$replyTo = $sepsLoggedUserEmail;
		$mailtext = <<<EOT
Dobrý den,

uživatel $sepsLoggedUserCaption vás chce pozvat do projektu $projectname
v plánovacím systému $sepsTitle. Pokud chcete tuto pozvánku přijmout,
klikněte na následující odkaz a pokračujte podle tam uvedených pokynů:
   $invitationuri

Pokud pozvánku přijmout nechcete, můžete tuto zprávu ignorovat.

Hezký den!
EOT;
	}
	else
	{
		$subj = encodeMailHeader("Ověření e-mailové adresy v systému $sepsTitle");
		$replyTo = $sepsAdminMail;
		$mailtext = <<<EOT
Dobrý den,

v systému $sepsTitle někdo nastavil uživatelskému účtu $sepsLoggedUsername
tuto e-mailovou adresu. Pokud jste tímto uživatelem vy a chcete tuto změnu
schválit, klikněte na následující odkaz:
   $invitationuri

Pokud tímto uživatelem nejste, na výše uvedený odkaz neklikejte, tuto
zprávu můžete ignorovat.

Hezký den!
EOT;
	}

	if (!mail($email, $subj, $mailtext, "From: $sepsAdminMail\r\nReply-To: $replyTo\r\nContent-type: text/plain; charset=utf-8\r\nX-Mailer: $sepsSoftwareVersionLine PHP/" . phpversion())) return false;

	return $code;
}

function invitationForm()
{
	global $sepsLoggedUser, $sepsLoggedUserEmail;

	if (!$sepsLoggedUserEmail) return;

	echo '<div class="bottomform invitation">';
	echo '<h2>Poslat pozvánku novému uživateli</h2>';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="sendinvitation" />';

	echo '<label for="project">Projekt, do kterého chcete uživatele pozvat:</label> <select name="project" id="project" />';
	$query = mysql_query(
			"SELECT p.id, p.title FROM projects p
			INNER JOIN usersprojects up ON up.project=p.id
			WHERE up.user = $sepsLoggedUser AND up.access & " . sepsAccessFlagsCanInvite);
	while ($row = mysql_fetch_assoc($query))
	{
		$projectid = $row['id'];
		$projecttitle = htmlspecialchars($row['title']);
		echo "<option value='$projectid'>$projecttitle</option>";
	}
	echo '</select><br />';

	echo '<label for="email">E-mail, na který se má pozvánka zaslat:</label> <input type="text" name="email" id="email" /><br />';
	echo '<input type="submit" value="Odeslat pozvánku" />';
	echo '</form>';
	echo '</div>';
}

function sendInvitation()
{
	global $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUserEmail, $sepsDefaultInvitationAccess;

	$email = getVariableOrNull('email');
	$project = getVariableOrNull('project');
	if (!$email || !$project || !$sepsLoggedUserEmail) return;

	$query = mysql_query("SELECT p.title, up.access FROM usersprojects up INNER JOIN projects p ON up.project=p.id WHERE up.user = $sepsLoggedUser AND up.project = $project");
	$row = mysql_fetch_assoc($query);
	if (!$row) return;
	if (!($row['access'] & sepsAccessFlagsCanInvite)) return;
	$projectname = $row['title'];
	$givenaccess = $row['access'] & $sepsDefaultInvitationAccess;

	$checkquery = mysql_query("SELECT u.id FROM users u INNER JOIN usersprojects up ON up.user=u.id WHERE u.email = '" . mysql_real_escape_string($email) .  "' AND up.project = $project AND (up.access & $givenaccess) = $givenaccess LIMIT 1");
	if (mysql_fetch_row($checkquery))
	{
		echo '<div class="errmsg">Uživatel s tímto e-mailem již je členem tohoto projektu.</div>';
		return;
	}

	$code = sendInvitationTo($email, $project, $projectname);
	if ($code)
	{
		echo '<div class="infomsg">Pozvánka odeslána</div>';
		logMessage("Uživatel $sepsLoggedUserCaption poslal pozvánku do projektu $projectname na $email; kód: $code");
	}
	else
	{
		echo '<div class="errmsg">Chyba při odesílání pozvánky. Zkuste to později.</div>';
	}
}

function receivedInvitation($invitationCode, $errormessage = null)
{
	$query = mysql_query(
		"SELECT c.createdate, c.forproject, u.caption AS user
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		WHERE c.code='" . mysql_real_escape_string($invitationCode) . "' AND c.accepted=0");

	$basicInvitationData = mysql_fetch_assoc($query);
	if (!$basicInvitationData || $basicInvitationData['forproject'])
	{
		receivedProjectInvitation($invitationCode, $errormessage);
		return false;
	}
	else
	{
		receivedEmailConfirmation($invitationCode);
		return true;
	}
}

function receivedProjectInvitation($invitationCode, $errormessage)
{
    global $sepsFullBaseUri;

	$query = mysql_query(
		"SELECT c.createdate, c.forproject, u.caption AS user, p.title AS project
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		INNER JOIN projects p ON c.forproject=p.id
		INNER JOIN usersprojects up ON c.forproject=up.project AND c.fromuser=up.user
		WHERE c.code='" . mysql_real_escape_string($invitationCode) . "' AND c.accepted=0 AND up.access & " . sepsAccessFlagsCanInvite);
	$data = mysql_fetch_assoc($query);
	if (!$data)
	{
		echo '<h2>Neplatný kód</h2>';
		echo '<div class="errmsg">Tento odkaz již není platný. Možná již byla pozvánka použita nebo její platnost vypršela.</div>';
		echo '<div>Pokud se chcete přihlásit, pokračujte na <a href="' . htmlspecialchars($sepsFullBaseUri) . '">hlavní stranu</a>.</div>';
		return;
	}

	if ($errormessage)
	{
		echo "<div class='errmsg'>$errormessage</div>";
	}
	else
	{
		echo '<h2>Vítejte</h2>';
	}

	echo '<div class="bigform invitation">';
	if (!$errormessage)
	{
		echo 'Získali jste pozvánku do projektu <i>' . htmlspecialchars($data['project']) . '</i> od uživatele <i>' . htmlspecialchars($data['user']) . '</i>. Pokud ji chcete přijmout a připojit se k tomuto projektu, vyplňte následující formulář:';
	}
	echo '<form action="?" method="post"><input type="hidden" name="action" value="acceptedinvitation" /><input type="hidden" name="invitation" value="' . $invitationCode . '" />';
	echo '<h3>Základní údaje</h3>';
	echo '<small class="formhelp">Pokud zde dosud nemáte založen účet, zvolte si přihlašovací jméno a heslo. Pokud již na tomto serveru účet máte, zadejte své existující jméno a heslo (stačí jednou).</small>';
	echo '<div class="formblock">';
	echo '<label for="username">Jméno:</label> <input class="required" type="text" name="username" id="username" maxlength="100" /><br />';
	echo '<label for="password">Heslo:</label> <input class="required" type="password" name="password" id="password" /><br />';
	echo '<label for="password2">Heslo znovu:</label> <input type="password" name="password2" id="password2" /><br />';
	echo '</div>';

	echo '<h3>Další informace</h3>';
	echo '<small class="formhelp">Tyto údaje jsou nepovinné, pouze tím ostatním umožníte dovědět se o vás něco víc.</small>';
	echo '<div class="formblock">';
	echo '<label for="firstname">Křestní jméno:</label> <input type="text" name="firstname" id="firstname" maxlength="50" /><br />';
	echo '<label for="lastname">Příjmení:</label> <input type="text" name="lastname" id="lastname" maxlength="50" /><br />';
	echo '<label for="caption">Jak uvádět vaše jméno:</label> <input type="text" name="caption" id="caption" maxlength="100" /><br />';
	echo '<label for="icq">ICQ:</label> <input type="text" name="icq" id="icq" maxlength="12" /><br />';
	echo '</div>';

	echo '<input type="submit" value="Odeslat" />';
	echo '</div>';
}

function receivedEmailConfirmation($invitationCode)
{
	$query = mysql_query(
		"SELECT c.createdate, u.caption, u.email, u.id, u.emailvalidated
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		WHERE c.code='" . mysql_real_escape_string($invitationCode) . "' AND c.accepted=0");
	$data = mysql_fetch_assoc($query);
	if (!$data)
	{
		echo '<h2>Neplatný kód</h2>';
		echo '<div class="errmsg">Tento odkaz již není platný.</div>';
		echo '<div>Pokud se chcete přihlásit, pokračujte na <a href="' . htmlspecialchars($sepsFullBaseUri) . '">hlavní stranu</a>.</div>';
		return;
	}

	$userid = $data['id'];

	if (!$data['emailvalidated'])
	{
		// zvalidovat e-mail
		if (mysql_query("UPDATE users SET emailvalidated=1 WHERE id=$userid") && (mysql_affected_rows() > 0))
		{
			echo '<div class="infomsg">Váš e-mail byl úspěšně ověřen.</div>';
		}
		else
		{
			echo '<div class="errmsg">Chyba při ověřování e-mailu, zkuste to později</div>';
			return;
		}
	}

	invalidateAllEmailConfirmationsForUser($userid);
}

function invalidateAllEmailConfirmationsForUser($user)
{
	return mysql_query(
		"UPDATE emailcodes c
			SET c.accepted=1
			WHERE c.fromuser=$user AND c.accepted=0 AND c.forproject IS NULL");
}

function acceptedInvitation()
{
	global $sepsDefaultInvitationAccess;

	$invitationCode = getVariableOrNull('invitation');
	$username = getVariableOrNull('username');
	$password = getVariableOrNull('password');
	$password2 = getVariableOrNull('password2');
	$firstname = getVariableOrNull('firstname');
	$lastname = getVariableOrNull('lastname');
	$caption = getVariableOrNull('caption');
	$icq = getVariableOrNull('icq');

	if (!$username)
	{
		receivedInvitation($invitationCode, 'Musíte zadat uživatelské jméno');
		return FALSE;
	}

	$invitationQuery = mysql_query(
		"SELECT c.id, c.fromuser, c.email, up.access, c.forproject
		FROM emailcodes c
		INNER JOIN usersprojects up ON c.forproject=up.project AND c.fromuser=up.user
		WHERE c.code='" . mysql_real_escape_string($invitationCode) . "' AND c.accepted=0 AND up.access & " . sepsAccessFlagsCanInvite);
	$invitation = mysql_fetch_assoc($invitationQuery);
	if (!$invitation)
	{
		receivedInvitation($invitationCode, 'Tato pozvánka není platná.');
		return FALSE;
	}

	$userquery = mysql_query("SELECT u.id FROM users u WHERE u.username = '" . mysql_real_escape_string($username) . "'");
	$founduser = mysql_fetch_assoc($userquery);
	if ($founduser)
	{
		if (!tryLogin($username, $password))
		{
			receivedInvitation($invitationCode, 'Vámi zadané uživatelské jméno se již používá (a zadané heslo k němu nepatří).');
			return FALSE;
		}

	    mysql_query("BEGIN");

		// přidat uživatele do projektu
		$project = $invitation['forproject'];
		$userid = $founduser['id'];
		$access = $invitation['access'] & $sepsDefaultInvitationAccess;
		mysql_query("INSERT INTO usersprojects(user, project, access) VALUES($userid, $project, $access)");

		// nastavit příznak ověření e-mailu
		mysql_query("UPDATE users SET emailvalidated=1 WHERE id=$userid LIMIT 1");

		// označit pozvánku jako použitou
		$invitationId = $invitation['id'];
		mysql_query("UPDATE emailcodes SET accepted=1 WHERE id=$invitationId LIMIT 1");

		mysql_query("COMMIT");

		logMessage("Uživatel $username přijal pozvánku číslo $invitationCode");
		return TRUE;
	}
	else
	{
		if ($password != $password2)
		{
			receivedInvitation($invitationCode, 'Zadaná hesla se navzájem liší');
			return FALSE;
		}

		if (!$caption) $caption = $username;
		$email = $invitation['email'];

	    mysql_query("BEGIN");

		// založit nového uživatele
		if (!mysql_query("INSERT INTO users(username, caption, firstname, lastname, password, email, icq, emailvalidated) VALUES ('"
			. mysql_real_escape_string($username) . "', '"
			. mysql_real_escape_string($caption) . "', '"
			. mysql_real_escape_string($firstname) . "', '"
			. mysql_real_escape_string($lastname) . "', '"
			. mysql_real_escape_string(hashPassword($password)) . "', '"
			. mysql_real_escape_string($email) . "', '"
			. mysql_real_escape_string($icq) . "', 1)"))
		{
			mysql_query("ROLLBACK");
			receivedInvitation($invitationCode, 'Nepodařilo se založit uživatele, zkuste to znovu.');
			return FALSE;
		}

		$createduserQuery = mysql_query("SELECT u.id FROM users u WHERE u.username = '" . mysql_real_escape_string($username) . "'");
		$createduser = mysql_fetch_assoc($createduserQuery);
		$userid = $createduser['id'];

		// přidat uživatele do projektu
		$project = $invitation['forproject'];
		$access = $invitation['access'] & $sepsDefaultInvitationAccess;
		mysql_query("INSERT INTO usersprojects(user, project, access) VALUES($userid, $project, $access)");

		// označit pozvánku jako použitou
		$invitationId = $invitation['id'];
		mysql_query("UPDATE emailcodes SET accepted=1 WHERE id=$invitationId LIMIT 1");

		mysql_query("COMMIT");

		logMessage("Založen uživatel $username na základě pozvánky číslo $invitationCode");

		return TRUE;
	}
}
