<?php

require_once('./include/Logging.php');

function formatInvitationText($head, $commentIntro, $comment, $tail)
{
	if ($comment)
	{
		return wordwrap($head . ' ' . $commentIntro) . "\n\n> " . str_replace("\n", "\n> ", wordwrap($comment)) . "\n\n" . wordwrap($tail);
	}
	else
	{
		return wordwrap($head . ' ' . $tail);
	}
}

function sendInvitationTo($userid, $username, $email, $project, $projectname, $invitationtype, $comment = null)
{
    global $sepsFullBaseUri, $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUsername, $sepsTitle, $sepsAdminMail, $sepsSoftwareVersionLine, $sepsLoggedUserEmail, $sepsDbConnection;

	if (!$email || !$sepsAdminMail || !$userid) return false;

	$code = generateRandomToken(sepsEmailCodeLength);
	$invitationuri = $sepsFullBaseUri . '?inv=' . $code;

	$projectOrNull = $project ? $project : "NULL";
	$date = strftime('%Y-%m-%d %H:%M:%S');
	$sql = "INSERT INTO emailcodes (email, code, fromuser, createdate, forproject, type) VALUES ('" . mysqli_real_escape_string($sepsDbConnection, strtolower(trim($email))) . "', '" . mysqli_real_escape_string($sepsDbConnection, $code) . "' , $userid, '$date', $projectOrNull, $invitationtype)";
	if (!mysqli_query($sepsDbConnection, $sql) || !mysqli_affected_rows($sepsDbConnection))
	{
		report_mysql_error();
		return false;
	}

	switch($invitationtype)
	{
		case sepsEmailCodeProjectInvitation:
			$subj = encodeMailHeader("Pozvánka do projektu $projectname");
			$replyTo = $sepsLoggedUserEmail;
			$mailtext = formatInvitationText("
Dobrý den,

uživatel $sepsLoggedUserCaption vás chce pozvat do projektu $projectname v plánovacím systému $sepsTitle.",
"Uživatel k tomu dodává následující komentář:", $comment,
"Pokud chcete tuto pozvánku přijmout, klikněte na následující odkaz a pokračujte podle tam uvedených pokynů:
   $invitationuri

Pokud pozvánku přijmout nechcete, můžete tuto zprávu ignorovat.

Hezký den!");
			break;

		case sepsEmailCodeEmailConfirmation:
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
			break;

		case sepsEmailCodePasswordReset:
			$subj = encodeMailHeader("Nové heslo do systému $sepsTitle");
			$replyTo = $sepsAdminMail;
			$mailtext = <<<EOT
Dobrý den,

v systému $sepsTitle někdo požádal o vygenerování nového hesla
k uživatelskému účtu $username. Pokud jste tímto uživatelem vy a chcete
opravdu získat nové heslo, klikněte na následující odkaz:
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
	global $sepsLoggedUser, $sepsLoggedUserEmail, $sepsDbConnection;

	if (!$sepsLoggedUserEmail) return;

	echo '<div class="bottomform invitation">';
	echo '<h2>Poslat pozvánku novému uživateli</h2>';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="sendinvitation" />';
	generateCsrfToken();

	echo '<label for="project">Projekt, do kterého chcete uživatele pozvat:</label> <select name="project" id="project" />';
	$query = mysqli_query($sepsDbConnection, 
			"SELECT p.id, p.title FROM projects p
			INNER JOIN usersprojects up ON up.project=p.id
			WHERE up.user = $sepsLoggedUser AND up.access & " . sepsAccessFlagsCanInvite);
	while ($row = mysqli_fetch_assoc($query))
	{
		$projectid = $row['id'];
		$projecttitle = htmlspecialchars($row['title']);
		echo "<option value='$projectid'>$projecttitle</option>";
	}
	echo '</select><br />';

	echo '<label for="email">E-mail, na který se má pozvánka zaslat:</label> <input type="text" name="email" id="email" maxlength="100" /><br />';
	echo '<small class="formhelp">Nepovinně také můžete uživateli k pozvánce připojit komentář:</small><br />';
	echo '<textarea name="comment" rows="10" cols="50"></textarea><br />';
	echo '<input type="submit" value="Odeslat pozvánku" />';
	echo '</form>';
	echo '</div>';
}

function inviteUserInternal($email, $project, $projectname, $comment, $givenaccess)
{
	global $sepsLoggedUsername, $sepsDbConnection;

	$userquery = mysqli_query($sepsDbConnection, "SELECT u.id, u.username, u.caption FROM users u INNER JOIN usersprojects up ON up.user=u.id WHERE u.email = '" . mysqli_real_escape_string($sepsDbConnection, $email) .  "'");
	$founduser = mysqli_fetch_assoc($userquery);
	if ($founduser)
	{
		$founduserid = $founduser['id'];
		$foundusername = $founduser['username'];
		$foundusercaption = $founduser['caption'];
		$currentaccessquery = mysqli_query($sepsDbConnection, "SELECT up.access FROM usersprojects up WHERE up.user=$founduserid AND up.project=$project LIMIT 1");
		$currentaccessrow = mysqli_fetch_row($currentaccessquery);
		if ($currentaccessrow)
		{
			// the user is already a member of this project
			$currentaccess = $currentaccessrow[0];

			if (($currentaccess & $givenaccess) == $givenaccess)
			{
				// ...and has all access he would get
				return array(false, "Uživatel $foundusercaption již je členem tohoto projektu");
			}

			// ...but he will receive more access rights now
			if (mysqli_query($sepsDbConnection, "UPDATE usersprojects SET access=(access | $givenaccess) WHERE user=$founduserid AND project=$project LIMIT 1") && mysqli_affected_rows($sepsDbConnection) > 0)
			{
				logMessage("Uživatel $sepsLoggedUsername navýšil uživateli $foundusername přístupová oprávnění do projektu $projectname");
				return array(true, "Uživatel $foundusercaption již byl členem tohoto projektu, avšak nyní mu byla zvýšena přístupová oprávnění");
			}
			else
			{
				report_mysql_error();
				return array(false, "Nepodařilo se navýšit oprávnění uživateli $foundusercaption");
			}
		}
		else
		{
			// this user is not yet a member of this project
			if (mysqli_query($sepsDbConnection, "INSERT INTO usersprojects(user, project, access) VALUES($founduserid, $project, $givenaccess)") && mysqli_affected_rows($sepsDbConnection) > 0)
			{
				logMessage("Uživatel $sepsLoggedUsername přidal uživatele $foundusername do projektu $projectname");
				return array(true, "Uživateli $foundusercaption byl přidělen přístup do tohoto projektu");
			}
			else
			{
				return array(false, "Nepodařilo se přidat uživatele $foundusercaption do tohoto projektu");
			}
		}
	}
	else
	{
		global $sepsLoggedUser;
		$code = sendInvitationTo($sepsLoggedUser, null, $email, $project, $projectname, sepsEmailCodeProjectInvitation, $comment);
		if ($code)
		{
			logMessage("Uživatel $sepsLoggedUsername poslal pozvánku do projektu $projectname na $email; kód: $code");
			return array(true, "Pozvánka odeslána");
		}
		else
		{
			return array(false, "Chyba při odesílání pozvánky. Zkuste to později.");
		}
	}
}

function sendInvitation()
{
	global $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUserEmail, $sepsDefaultInvitationAccess, $sepsDbConnection;

	$emailstr = getVariableOrNull('email');
	$project = getVariableOrNull('project');
	$comment = getVariableOrNull('comment');
	if (strlen_utf8($comment) > 2500)
	{
		echo '<div class="errmsg">Váš komentář je příliš dlouhý.</div>';
		return;
	}
	if (!$emailstr || !$project || !$sepsLoggedUserEmail) return;

	if (!validateEmailList($emailstr))
	{
		echo '<div class="errmsg">Zadaný text nevypadá jako e-mailové adresy.</div>';
		return;
	}

	$emails = explode(',', $emailstr);
	if (count($emails) > 100)
	{
		echo '<div class="errmsg">Příliš mnoho e-mailových adres.</div>';
		return;
	}

	$query = mysqli_query($sepsDbConnection, "SELECT p.title, up.access, p.invitationaccessmask FROM usersprojects up INNER JOIN projects p ON up.project=p.id WHERE up.user = $sepsLoggedUser AND up.project = $project");
	$row = mysqli_fetch_assoc($query);
	if (!$row)
	{
		echo '<div class="errmsg">Do tohoto projektu nemáte přístup.</div>';
		return;
	}
	if (!($row['access'] & sepsAccessFlagsCanInvite))
	{
		echo '<div class="errmsg">V tomto projektu nemůžete odesílat pozvánky.</div>';
		return;
	}
	$projectname = $row['title'];
	$givenaccess = $row['access'] & $sepsDefaultInvitationAccess & ~intval($row['invitationaccessmask']);

	foreach($emails as $email)
	{
		$email = trim($email);
		$result = inviteUserInternal($email, $project, $projectname, $comment, $givenaccess);
		if ($result[0])
		{
			echo "<div class='infomsg'>" . htmlspecialchars($email) .  ": $result[1]</div>";
		}
		else
		{
			echo "<div class='errmsg'>" . htmlspecialchars($email) .  ": $result[1]</div>";
		}
	}
}

function receivedInvitation($invitationCode, $errormessage = null)
{
	global $sepsDbConnection;

	$query = mysqli_query($sepsDbConnection, 
		"SELECT c.createdate, c.`type`, u.username
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		WHERE c.code='" . mysqli_real_escape_string($sepsDbConnection, $invitationCode) . "' AND c.accepted=0");

	$basicInvitationData = mysqli_fetch_assoc($query);
	if (!$basicInvitationData)
	{
		receivedProjectInvitation($invitationCode, $errormessage);
		return false;
	}

	switch($basicInvitationData['type'])
	{
		case sepsEmailCodeProjectInvitation:
			receivedProjectInvitation($invitationCode, $errormessage);
			return false;

		case sepsEmailCodeEmailConfirmation:
			receivedEmailConfirmation($invitationCode);
			return true;

		case sepsEmailCodePasswordReset:
			receivedPasswordReset($invitationCode, $basicInvitationData['username'], $errormessage);
			return false;
	}
}

function receivedProjectInvitation($invitationCode, $errormessage)
{
    global $sepsFullBaseUri, $sepsDbConnection;

	$query = mysqli_query($sepsDbConnection, 
		"SELECT c.createdate, c.forproject, u.caption AS user, p.title AS project
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		INNER JOIN projects p ON c.forproject=p.id
		INNER JOIN usersprojects up ON c.forproject=up.project AND c.fromuser=up.user
		WHERE c.code='" . mysqli_real_escape_string($sepsDbConnection, $invitationCode) . "' AND c.accepted=0 AND up.access & " . sepsAccessFlagsCanInvite);
	$data = mysqli_fetch_assoc($query);
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
	generateCsrfToken();
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
	echo '<label for="jabber">Jabber:</label> <input type="text" name="jabber" id="jabber" maxlength="100" /><br />';
	echo '<label for="icq">ICQ:</label> <input type="text" name="icq" id="icq" maxlength="12" /><br />';
	echo '<label for="skype">Skype:</label> <input type="text" name="skype" id="skype" maxlength="100" /><br />';
	echo '</div>';

	echo '<input type="submit" value="Odeslat" />';
	echo '</div>';
}

function receivedEmailConfirmation($invitationCode)
{
	global $sepsDbConnection;

	$query = mysqli_query($sepsDbConnection, 
		"SELECT c.createdate, u.id, u.emailvalidated
		FROM emailcodes c
		INNER JOIN users u ON c.fromuser=u.id
		WHERE c.code='" . mysqli_real_escape_string($sepsDbConnection, $invitationCode) . "' AND c.accepted=0 AND c.type=" . sepsEmailCodeEmailConfirmation);
	$data = mysqli_fetch_assoc($query);
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
		if (mysqli_query($sepsDbConnection, "UPDATE users SET emailvalidated=1 WHERE id=$userid") && (mysqli_affected_rows($sepsDbConnection) > 0))
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

function receivedPasswordReset($invitationCode, $username, $errormessage)
{
	echo '<div class="bigform invitation">';
	if ($errormessage)
	{
		echo "<div class='errmsg'>$errormessage</div>";
	}
	else
	{
		echo 'Nyní můžete uživateli <i>' . htmlspecialchars($username) . '</i> nastavit nové heslo.';
	}
	echo '<form action="?" method="post"><input type="hidden" name="action" value="dopasswordreset" /><input type="hidden" name="invitation" value="' . $invitationCode . '" /><input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />';
	generateCsrfToken();
	echo '<label for="password">Nové heslo:</label> <input class="required" type="password" name="password" id="password" /><br />';
	echo '<label for="password2">Heslo znovu:</label> <input class="required" type="password" name="password2" id="password2" /><br />';

	echo '<input type="submit" value="Změnit heslo" />';
	echo '</div>';
}

function doPasswordReset()
{
	global $sepsDbConnection;

	require_once('./include/Login.php');
	$invitationCode = getVariableOrNull('invitation');
	$username = getVariableOrNull('username');
	$password = getVariableOrNull('password');
	$password2 = getVariableOrNull('password2');

	if ($password != $password2)
	{
		receivedInvitation($invitationCode, 'Zadaná hesla se navzájem liší.');
		return false;
	}

	$query = mysqli_query($sepsDbConnection, 
		"SELECT c.createdate, c.fromuser
		FROM emailcodes c
		WHERE c.code='" . mysqli_real_escape_string($sepsDbConnection, $invitationCode) . "' AND c.accepted=0 AND c.type=" . sepsEmailCodePasswordReset);
	$data = mysqli_fetch_assoc($query);
	if (!$data)
	{
		echo '<h2>Neplatný kód</h2>';
		echo '<div class="errmsg">Tento odkaz již není platný.</div>';
		echo '<div>Pokud se chcete přihlásit, pokračujte na <a href="' . htmlspecialchars($sepsFullBaseUri) . '">hlavní stranu</a>.</div>';
		return false;
	}

	$userid = $data['fromuser'];
	$passhash = hashPassword($password);

	$sql = "UPDATE users SET password='$passhash' WHERE id=$userid AND username='" . mysqli_real_escape_string($sepsDbConnection, $username) . "' LIMIT 1";
	if (!mysqli_query($sepsDbConnection, $sql) || (mysqli_affected_rows($sepsDbConnection) != 1))
	{
		report_mysql_error();
		return false;
	}

	echo '<div class="infomsg">Heslo bylo úspěšně nastaveno</div>';
	return true;
}

function invalidateAllEmailConfirmationsForUser($user)
{
	global $sepsDbConnection;

	return mysqli_query($sepsDbConnection, 
		"UPDATE emailcodes c
			SET c.accepted=1
			WHERE c.fromuser=$user AND c.accepted=0 AND c.forproject IS NULL");
}

function acceptedInvitation()
{
	global $sepsDefaultInvitationAccess, $sepsDbConnection;

	$invitationCode = getVariableOrNull('invitation');
	$username = mb_strtolower(trim(getVariableOrNull('username')));
	$password = getVariableOrNull('password');
	$password2 = getVariableOrNull('password2');
	$firstname = getVariableOrNull('firstname');
	$lastname = getVariableOrNull('lastname');
	$caption = getVariableOrNull('caption');
	$jabber = getVariableOrNull('jabber');
	$icq = getVariableOrNull('icq');
	$skype = getVariableOrNull('skype');

	if (!$username)
	{
		receivedInvitation($invitationCode, 'Musíte zadat uživatelské jméno');
		return FALSE;
	}

	$invitationQuery = mysqli_query($sepsDbConnection, 
		"SELECT c.id, c.fromuser, c.email, up.access, c.forproject, p.invitationaccessmask
		FROM emailcodes c
		INNER JOIN usersprojects up ON c.forproject=up.project AND c.fromuser=up.user
		INNER JOIN projects p ON c.forproject=p.id
		WHERE c.code='" . mysqli_real_escape_string($sepsDbConnection, $invitationCode) . "' AND c.accepted=0 AND up.access & " . sepsAccessFlagsCanInvite . " AND c.type=" . sepsEmailCodeProjectInvitation);
	$invitation = mysqli_fetch_assoc($invitationQuery);
	if (!$invitation)
	{
		receivedInvitation($invitationCode, 'Tato pozvánka není platná.');
		return FALSE;
	}

	$projectAccessMask = ~intval($invitation['invitationaccessmask']);

	$userquery = mysqli_query($sepsDbConnection, "SELECT u.id FROM users u WHERE u.username = '" . mysqli_real_escape_string($sepsDbConnection, $username) . "'");
	$founduser = mysqli_fetch_assoc($userquery);
	if ($founduser)
	{
		if (!tryLogin($username, $password))
		{
			receivedInvitation($invitationCode, 'Vámi zadané uživatelské jméno se již používá (a zadané heslo k němu nepatří).');
			return FALSE;
		}

	    mysqli_query($sepsDbConnection, "BEGIN");

		// přidat uživatele do projektu
		$project = $invitation['forproject'];
		$userid = $founduser['id'];
		$access = $invitation['access'] & $sepsDefaultInvitationAccess & $projectAccessMask;

		if (!mysqli_query($sepsDbConnection, "INSERT INTO usersprojects(user, project, access) VALUES($userid, $project, $access)") || mysqli_affected_rows($sepsDbConnection) != 1)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Váš požadavek se nepodařilo zpracovat. Kontaktujte správce serveru.');
			return FALSE;
		}

		// nastavit příznak ověření e-mailu
		if (!mysqli_query($sepsDbConnection, "UPDATE users SET emailvalidated=1 WHERE id=$userid LIMIT 1") || mysqli_affected_rows($sepsDbConnection) != 1)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Váš požadavek se nepodařilo zpracovat. Kontaktujte správce serveru.');
			return FALSE;
		}

		// označit pozvánku jako použitou
		$invitationId = $invitation['id'];
		if (!mysqli_query($sepsDbConnection, "UPDATE emailcodes SET accepted=1 WHERE id=$invitationId LIMIT 1") || mysqli_affected_rows($sepsDbConnection) != 1)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Váš požadavek se nepodařilo zpracovat. Kontaktujte správce serveru.');
			return FALSE;
		}

		mysqli_query($sepsDbConnection, "COMMIT");

		logMessage("Uživatel $username přijal pozvánku číslo $invitationCode");

		return TRUE;
	}
	else
	{
		if (!$caption) $caption = $username;
		$email = $invitation['email'];

		if ($password != $password2)
		{
			receivedInvitation($invitationCode, 'Zadaná hesla se navzájem liší');
			return FALSE;
		}

		if ($email && !validateEmail($email))
		{
			receivedInvitation($invitationCode, 'Vámi zadaný řetězec nevypadá jako e-mailová adresa');
			return FALSE;
		}
		if ($icq && !validateIcq($icq))
		{
			receivedInvitation($invitationCode, 'Vámi zadaný řetězec nevypadá jako ICQ číslo');
			return FALSE;
		}
		if ($jabber && !validateJabber($jabber))
		{
			receivedInvitation($invitationCode, 'Vámi zadaný řetězec nevypadá jako Jabber ID');
			return FALSE;
		}

	    mysqli_query($sepsDbConnection, "BEGIN");

		// založit nového uživatele
		if (!mysqli_query($sepsDbConnection, "INSERT INTO users(username, caption, firstname, lastname, password, email, jabber, skype, icq, emailvalidated) VALUES ('"
			. mysqli_real_escape_string($sepsDbConnection, $username) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $caption) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $firstname) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $lastname) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, hashPassword($password)) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $email) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $jabber) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $skype) . "', '"
			. mysqli_real_escape_string($sepsDbConnection, $icq) . "', 1)")
				|| mysqli_affected_rows($sepsDbConnection) != 1)
		{
			report_mysql_error();
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Nepodařilo se založit uživatele, zkuste to znovu.');
			return FALSE;
		}

		$createduserQuery = mysqli_query($sepsDbConnection, "SELECT u.id FROM users u WHERE u.username = '" . mysqli_real_escape_string($sepsDbConnection, $username) . "'");
		if (!$createduserQuery)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Nepodařilo se zpracovat uživatele, zkuste to znovu.');
			return FALSE;
		}
		$createduser = mysqli_fetch_assoc($createduserQuery);
		if (!$createduser)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Nepodařilo se dohledat uživatele, zkuste to znovu.');
			return FALSE;
		}
		$userid = $createduser['id'];

		// přidat uživatele do projektu
		$project = $invitation['forproject'];
		$access = $invitation['access'] & $sepsDefaultInvitationAccess & $projectAccessMask;
		if (!mysqli_query($sepsDbConnection, "INSERT INTO usersprojects(user, project, access) VALUES($userid, $project, $access)") || mysqli_affected_rows($sepsDbConnection) != 1)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Uživatele se nepodařilo přidat do projektu, zkuste to znovu.');
			return FALSE;
		}

		// označit pozvánku jako použitou
		$invitationId = $invitation['id'];
		if (!mysqli_query($sepsDbConnection, "UPDATE emailcodes SET accepted=1 WHERE id=$invitationId LIMIT 1") || mysqli_affected_rows($sepsDbConnection) != 1)
		{
			mysqli_query($sepsDbConnection, "ROLLBACK");
			receivedInvitation($invitationCode, 'Nepodařilo se zpracovat, zkuste to znovu.');
			return FALSE;
		}

		mysqli_query($sepsDbConnection, "COMMIT");

		logMessage("Založen uživatel $username na základě pozvánky číslo $invitationCode");

		return TRUE;
	}
}
