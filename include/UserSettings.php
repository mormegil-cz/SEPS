<?php

function displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype)
{
	echo '<div class="bottomform usersettings">';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="savesettings" />';
	echo '<input type="hidden" name="currusername" value="' . htmlspecialchars($currUsername) . '" />';
	echo '<input type="hidden" name="currusercaption" value="' . htmlspecialchars($currUsercaption) . '" />';
	echo '<input type="hidden" name="currfirstname" value="' . htmlspecialchars($currFirstname) . '" />';
	echo '<input type="hidden" name="currlastname" value="' . htmlspecialchars($currLastname) . '" />';
	echo '<input type="hidden" name="curremail" value="' . htmlspecialchars($currEmail) . '" />';
	echo '<input type="hidden" name="currjabber" value="' . htmlspecialchars($currJabber) . '" />';
	echo '<input type="hidden" name="curricq" value="' . htmlspecialchars($currIcq) . '" />';
	echo '<input type="hidden" name="currskype" value="' . htmlspecialchars($currSkype) . '" />';
	echo '<input type="hidden" name="curremailvalidated" value="' . $emailvalidated . '" />';
	echo '<label for="username">Uživatelské jméno:</label> <input type="text" id="username" name="username" value="' . htmlspecialchars($username) . '" maxlength="100" /><br />';
	echo '<label for="usercaption">Jak uvádět moje jméno:</label> <input type="text" id="usercaption" name="usercaption" value="' . htmlspecialchars($usercaption) . '" maxlength="100" /><br />';
	echo '<label for="firstname">Jméno:</label> <input type="text" id="firstname" name="firstname" value="' . htmlspecialchars($firstname) . '" maxlength="50" /><br />';
	echo '<label for="lastname">Příjmení:</label> <input type="text" id="lastname" name="lastname" value="' . htmlspecialchars($lastname) . '" maxlength="50" /><br />';
	echo '<label for="email">E-mail:</label> <input type="text" id="email" name="email" value="' . htmlspecialchars($email) . '" maxlength="100" /><br />';
	echo '<label for="jabber">Jabber:</label> <input type="text" id="jabber" name="jabber" value="' . htmlspecialchars($jabber) . '" maxlength="100" /><br />';
	echo '<label for="icq">ICQ:</label> <input type="text" id="icq" name="icq" value="' . htmlspecialchars($icq) . '" maxlength="12" /><br />';
	echo '<label for="skype">Skype:</label> <input type="text" id="skype" name="skype" value="' . htmlspecialchars($skype) . '" maxlength="100" /><br />';

	echo '<div class="formblock">';
	echo '<h3>Změna hesla</h3>';
	echo '<p><small class="formhelp">Pokud nechcete heslo měnit, ponechte následující pole prázdná, jinak zadejte současné (staré) heslo, nově zvolené heslo a nové heslo ještě jednou pro kontrolu</small></p>';
	echo '<label for="oldpassword">Stávající heslo:</label> <input type="password" id="oldpassword" name="oldpassword" /><br />';
	echo '<label for="newpassword">Nové heslo:</label> <input type="password" id="newpassword" name="newpassword" /><br />';
	echo '<label for="newpassword2">Zopakovat nové heslo:</label> <input type="password" id="newpassword2" name="newpassword2" /><br />';
	echo '</div>';

	echo '<input type="submit" value="Uložit změny" />';
	echo '</form>';

	if ($currEmail && !$emailvalidated)
	{
		echo '<div class="formblock">';
		echo '<h3>Ověření e-mailové adresy</h3>';
		echo '<p><small class="formhelp">Vámi zadaná e-mailová adresa dosud nebyla ověřena. Podívejte se do své schránky, zda vám přišel potvrzovací e-mail a pokračujte podle pokynů v něm. Případně si můžete nechat zaslat nové potvrzení:</small></p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="sendemailconfirmation" />';
		echo '<input type="submit" value="Poslat nový potvrzovací e-mail" />';
		echo '</form>';
	}

	echo '</div>';
}

function userSettingsForm()
{
	global $sepsLoggedUser;

	$query = mysql_query("SELECT username, caption, firstname, lastname, email, emailvalidated, icq, jabber, skype FROM users WHERE id=$sepsLoggedUser");
	$userData = mysql_fetch_assoc($query);
	if (!$userData) return;

	$username = $userData['username'];
	$usercaption = $userData['caption'];
	$firstname = $userData['firstname'];
	$lastname = $userData['lastname'];
	$email = $userData['email'];
	$emailvalidated = $userData['emailvalidated'];
	$icq = $userData['icq'];
	$jabber = $userData['jabber'];
	$skype = $userData['skype'];

	displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $username, $usercaption, $firstname, $lastname, $email, $emailvalidated, $icq, $jabber, $skype);
}

function saveUserSettings()
{
	require_once('./include/Login.php');
	require_once('./include/Invitations.php');

	global $sepsLoggedUser;

	$username = getVariableOrNull('username');
	$usercaption = getVariableOrNull('usercaption');
	$firstname = getVariableOrNull('firstname');
	$lastname = getVariableOrNull('lastname');
	$email = getVariableOrNull('email');
	$icq = getVariableOrNull('icq');
	$jabber = getVariableOrNull('jabber');
	$skype = getVariableOrNull('skype');
	$currUsername = getVariableOrNull('currusername');
	$currUsercaption = getVariableOrNull('currusercaption');
	$currFirstname = getVariableOrNull('currfirstname');
	$currLastname = getVariableOrNull('currlastname');
	$currEmail = getVariableOrNull('curremail');
	$currIcq = getVariableOrNull('curricq');
	$currJabber = getVariableOrNull('currjabber');
	$currSkype = getVariableOrNull('currskype');
	$emailvalidated = getVariableOrNull('curremailvalidated');
	$oldpassword = getVariableOrNull('oldpassword');
	$newpassword = getVariableOrNull('newpassword');
	$newpassword2 = getVariableOrNull('newpassword2');

	if (!$username)
	{
		echo '<div class="errmsg">Uživatelské jméno nemůže být prázdné</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}
	if (!$usercaption)
	{
		echo '<div class="errmsg">Musíte vyplnit, jak máte být uváděni</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}
	if ($email && !validateEmail($email))
	{
		echo '<div class="errmsg">Vámi zadaný řetězec nevypadá jako e-mail</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}
	if ($icq && !validateIcq($icq))
	{
		echo '<div class="errmsg">Vámi zadaný řetězec nevypadá jako ICQ</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}
	if ($jabber && !validateJabber($jabber))
	{
		echo '<div class="errmsg">Vámi zadaný řetězec nevypadá jako Jabber ID</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}

	$queryCheck = mysql_query("SELECT id FROM users WHERE username='" . mysql_real_escape_string($username) . "' AND id!=$sepsLoggedUser LIMIT 1");
	if (mysql_fetch_row($queryCheck))
	{
		echo '<div class="errmsg">Vaše nové uživatelské jméno už se používá, zkuste jiné</div>';
		displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
		return;
	}

	if ($oldpassword || $newpassword || $newpassword2)
	{
		if ($newpassword != $newpassword2)
		{
			echo '<div class="errmsg">Opakované nové heslo nesouhlasí</div>';
			displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
			return;
		}

		if ($oldpassword == $newpassword)
		{
			echo '<div class="errmsg">Nové heslo je stejné jako to stávající</div>';
			displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
			return;
		}

		if (!tryLogin($currUsername, $oldpassword))
		{
			echo '<div class="errmsg">Stávající heslo nesouhlasí</div>';
			displayUserSettingsForm($username, $usercaption, $firstname, $lastname, $email, $icq, $jabber, $skype, $currUsername, $currUsercaption, $currFirstname, $currLastname, $currEmail, $emailvalidated, $currIcq, $currJabber, $currSkype);
			return;
		}
	}

	$emailNeedsRevalidation = false;
	$items = array();
	if ($username != $currUsername) $items['username'] = mb_strtolower(trim($username));
	if ($usercaption != $currUsercaption) $items['caption'] = $usercaption;
	if ($firstname != $currFirstname) $items['firstname'] = $firstname;
	if ($lastname != $currLastname) $items['lastname'] = $lastname;
	if ($icq != $currIcq) $items['icq'] = $icq;
	if ($jabber != $currJabber) $items['jabber'] = $jabber;
	if ($skype != $currSkype) $items['skype'] = $skype;
	if ($email != $currEmail)
	{
		$items['email'] = $email;
		$items['emailvalidated'] = 0;
		$emailNeedsRevalidation = $email != '';

		// zrušit platnost všech předchozích ověřovacích e-mailů tohoto uživatele
		invalidateAllEmailConfirmationsForUser($sepsLoggedUser);
	}
	if ($newpassword) $items['password'] = hashPassword($newpassword);

	if (count($items) == 0)
	{
		echo '<div class="errmsg">Žádné změny nastavení nebyly požadovány</div>';
		return;
	}

	$sql = '';
	foreach($items as $column => $value)
	{
		if ($sql) $sql .= ', ';
		$sql .= "$column='" . mysql_real_escape_string($value) . "'";
	}
	$sql = "UPDATE users SET $sql WHERE id=$sepsLoggedUser AND username='" . mysql_real_escape_string($currUsername) . "' LIMIT 1";

	if (mysql_query($sql) && (mysql_affected_rows() > 0))
		echo '<div class="infomsg">Změny nastavení provedeny</div>';
	else
	{
		echo '<div class="errmsg">Chyba při ukládání nastavení</div>';
		return;
	}

	loadLoggedUserInformation();

	if ($emailNeedsRevalidation)
	{
		if (sendInvitationTo($sepsLoggedUser, $username, $email, 0, null, sepsEmailCodeEmailConfirmation))
			echo '<div class="infomsg">Byla změněna e-mailová adresa; na novou adresu byl odeslán potvrzovací e-mail, postupujte podle v něm uvedených instrukcí.</div>';
		else
			echo '<div class="errmsg">Byla změněna e-mailová adresa, nepodařilo se ale odeslat potvrzovací e-mail.</div>';
	}
}

function sendVerificationEmail()
{
	global $sepsLoggedUser;

	$query = mysql_query("SELECT username, email FROM users WHERE id=$sepsLoggedUser");
	$userData = mysql_fetch_assoc($query);

	if ($userData && sendInvitationTo($userData['username'], $userData['email'], 0, null, sepsEmailCodeEmailConfirmation))
		echo '<div class="infomsg">Na vaši adresu (<tt>' . htmlspecialchars($userData[0]) . '</tt>) byl odeslán potvrzovací e-mail, postupujte podle v něm uvedených instrukcí.</div>';
	else
		echo '<div class="infomsg">Nepodařilo se odeslat potvrzovací e-mail.</div>';
}
