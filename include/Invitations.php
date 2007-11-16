<?php

require_once('./include/Logging.php');

function invitationForm()
{
	global $sepsLoggedUser, $sepsLoggedUserEmail;

	if (!$sepsLoggedUserEmail) return;

	echo '<div class="bottomform invitation">';
	echo '<h2>Poslat pozvánku novému uživateli</h2>';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="sendinvitation" />';

	echo 'Projekt, do kterého chcete uživatele pozvat: <select name="project" />';
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

	echo 'E-mail, na který se má pozvánka zaslat: <input type="text" name="email" /><br />';
	echo '<input type="submit" value="Odeslat pozvánku" />';
	echo '</form>';
	echo '</div>';
}

function sendInvitation()
{
	global $sepsLoggedUser, $sepsLoggedUserCaption, $sepsLoggedUserEmail, $sepsTitle, $sepsAdminMail, $sepsFullBaseUri, $sepsSoftwareVersionLine;

	$email = getVariableOrNull('email');
	$project = getVariableOrNull('project');
	if (!$email || !$project || !$sepsLoggedUserEmail) return;

	$query = mysql_query("SELECT p.title, up.access FROM usersprojects up INNER JOIN projects p ON up.project=p.id WHERE up.user = $sepsLoggedUser AND up.project = $project");
	$row = mysql_fetch_assoc($query);
	if (!$row) return;
	if (!($row['access'] & sepsAccessFlagsCanInvite)) return;
	$projectname = $row['title'];

	$code = '';
	for($i = 0; $i < sepsEmailCodeLength; $i++)
	{
		$code .= chr(ord('A') + mt_rand(0, 25));
	}
	$invitationuri = $sepsFullBaseUri . '?inv=' . $code;

	$date = strftime('%Y-%m-%d %H:%M:%S');
	if (!mysql_query("INSERT INTO emailcodes (email, code, fromuser, createdate) VALUES ('" . mysql_real_escape_string($email) . "', '" . mysql_real_escape_string($code) . "' , $sepsLoggedUser, '$date')"))
	{
		echo '<div class="errmsg">Chyba při odesílání pozvánky. Zkuste to později.</div>';
		return;
	}

	if (mail($email, encodeMailHeader("Pozvánka do projektu $projectname"), <<<EOT
Dobrý den,

uživatel $sepsLoggedUserCaption vás chce pozvat do projektu $projectname
v plánovacím systému $sepsTitle. Pokud chcete tuto pozvánku přijmout,
klikněte na následující odkaz a následujte tam uvedené pokyny:
   $invitationuri

Pokud pozvánku přijmout nechcete, můžete tuto zprávu ignorovat.
EOT
		, "From: $sepsAdminMail\r\nReply-To: $sepsLoggedUserEmail\r\nContent-type: text/plain; charset=utf-8\r\nX-Mailer: $sepsSoftwareVersionLine PHP/" . phpversion()))
	{
		echo '<div class="infomsg">Pozvánka odeslána</div>';
		logMessage("Uživatel $sepsLoggedUserCaption poslal pozvánku do projektu $projectname na $email");
	}
	else
	{
		echo '<div class="errmsg">Nepodařilo se odeslat e-mail.</div>';
	}
}

function receivedInvitation($invitationCode)
{
}
