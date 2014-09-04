<?php

require_once('./include/Logging.php');
require_once('./include/Dialogs.php');

function manageUsersForm()
{
	global $sepsLoggedUser, $sepsAccessFlagNames, $sepsLoggedUserCaption;

	echo '<form action="?" method="post"><input type="hidden" name="action" value="manageusers" />';
	generateCsrfToken();
	$projectId = getVariableOrNull('project');
	if (!is_numeric($projectId)) $projectId = null;
	$projectName = null;
	if ($projectId)
	{
		$nameQuery = mysql_query("SELECT p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE p.id=$projectId AND up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanChangeUserAccess);
		$row = mysql_fetch_assoc($nameQuery);
		if ($row)
		{
			$projectName = $row['title'];
			echo "<input type='hidden' name='project' value='$projectId' />";
		}
		else $projectId = null;
	}

    $doChangeProjectId = false;
	if ($projectId)
	{
        $doChangeProjectId = $projectId;
        $doChangeProjectName = $projectName;
	}

	if (!$projectId)
	{
		$projectsFoundCount = 0;
		$projectIdFound = null;
		$projectsQuery = mysql_query("SELECT p.id, p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanChangeUserAccess);
		while ($row = mysql_fetch_assoc($projectsQuery))
		{
			$projectsFoundCount++;
			switch ($projectsFoundCount)
			{
				case 1:
					$projectIdFound = $row['id'];
					$projectTitleFound = $row['title'];
					break;
				case 2:
                    beginDialog('Správa uživatelů');
                    beginDialogBody();
					echo '<div class="form-group"><label for="project">Projekt:</label> <select name="project" id="project" class="form-control" onchange="javascript:this.form.submit()" />';
					echo "<option value='$projectIdFound'>" . htmlspecialchars($projectTitleFound) . "</option>";
					// fall-through!
				default:
					echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
		}
		switch($projectsFoundCount)
		{
			case 0:
                alert('Nemáte oprávnění pro správu uživatelů!', 'danger');
				return;
			case 1:
				$projectId = $projectIdFound;
				$projectName = $projectTitleFound;
				echo "<input type='hidden' name='project' value='$projectId' />";
				break;
			default:
				echo '</select></div>';
		}
	}

	if ($projectId)
	{
        beginDialog('Správa uživatelů projektu ' . htmlspecialchars($projectName));
        beginDialogBody();
        if ($doChangeProjectId)
        {
            modifyUser($doChangeProjectId, $doChangeProjectName);
        }
		echo '<div class="form-group"><label for="user">Uživatel:</label> <select name="user" id="user" class="form-control">';
		$query = mysql_query("SELECT u.id, u.caption, u.username FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			$title = $row['caption'];
			if (!$title) $title = $row['username'];
			echo "<option value='$row[id]'>" . htmlspecialchars($title) . "</option>";
		}
		echo '</select></div>';
		echo '<div class="form-group"><label for="priority">Priorita:</label> <select name="priority" id="priority" class="form-control">';
		echo '<option value="N">[Ponechat stávající]</option>';
		echo '<option value="20">' . userPriorityToString(20) . '</option>';
		echo '<option value="10">' . userPriorityToString(10) . '</option>';
		echo '<option value="0">' . userPriorityToString(0) . '</option>';
		echo '<option value="-10">' . userPriorityToString(-10) . '</option>';
		echo '<option value="-20">' . userPriorityToString(-20) . '</option>';
		echo '</select></div>';

        beginPanel('Oprávnění');
        echo '<table class="table"><tr><th>#</th><th>Oprávnění</th><th>Neměnit</th><th>Přidělit</th><th>Odebrat</th></tr>';
		for ($accessBit = 1, $idx = 0; $accessBit <= sepsAccessMaxValidBit; $accessBit <<= 1, $idx++)
		{
			$counter = $idx + 1;
			echo "<tr><td class='number'>$counter</td><td>${sepsAccessFlagNames[$idx]}</td><td><input type='radio' name='access_$accessBit' value='keep' checked='checked'></td>";
			// TODO: check access?
			echo "<td><input type='radio' name='access_$accessBit' value='add'></td><td><input type='radio' name='access_$accessBit' value='remove'></td></tr>";
		}
		echo '</table>';
        endPanel();

		echo '<div class="checkbox"><label><input type="checkbox" name="kickuser" id="kickuser" onclick="if (this.checked) return confirm(\'Určitě vyhodit uživatele?\')" /> Vyhodit uživatele z projektu</label></div>';
	}

	echo '<p class="text-right"><input type="submit" class="btn btn-primary btn-lg" value="Provést změny" /> <a href="?" class="btn btn-default btn-lg">Zavřít</a></p>';
    endDialogBody();

	if ($projectId)
	{
        beginDialogFooter();
        echo '<div style="text-align: left">'; // záplata na .dialogfooter { text-align: right } v Bootstrapu
        beginPanel('Seznam uživatelů');
		echo '<table class="table">';
		echo '<tr><th>Uživatel</th><th>Priorita</th><th>Oprávnění</th></tr>';
		$query = mysql_query("SELECT u.caption, u.username, up.priority, up.access FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			$title = $row['caption'];
			if (!$title) $title = $row['username'];
			echo '<tr><td>' . $title . '</td><td class="number">' . userPriorityToString($row['priority']) . '</td><td><tt>';
			$access = $row['access'];
			for ($mask = 1, $idx = 1; $mask <= sepsAccessMaxValidBit; $mask <<= 1, $idx++)
			{
				$mod = $idx % 10;
				if ($mod == 0) echo ' ';
				echo ($access & $mask) ? $mod : '-';
			}
			echo '</tt></td>';
		}
		echo '</table>';
        endPanel();
        echo '</div>'; // záplatový div
        endDialogFooter();
	}

	endDialog();
	echo '</form>';
}

function userPriorityToStringWithBase($priority, $base, $baseTitle)
{
	if ($priority == $base) return $baseTitle;
	else if ($priority > $base) return $baseTitle . '+' . ($priority - $base);
	else return $baseTitle . '-' . ($base - $priority);
}

function userPriorityToString($priority)
{
	if ($priority > 15) return userPriorityToStringWithBase($priority, 20, 'Vysoká');
	if ($priority > 5) return userPriorityToStringWithBase($priority, 10, 'Zvýšená');
	if ($priority > -5) return userPriorityToStringWithBase($priority, 0, 'Běžná');
	if ($priority > -15) return userPriorityToStringWithBase($priority, -10, 'Snížená');
	if ($priority > -25) return userPriorityToStringWithBase($priority, -20, 'Nízká');
	return $priority . '';
}

function modifyUser($projectId, $projectName)
{
	global $sepsLoggedUser, $sepsLoggedUserCaption,  $sepsLoggedUsername;

	$user = getVariableOrNull('user');
	if (!is_numeric($user))
	{
		return;
	}

	$userQuery = mysql_query("SELECT u.id, u.caption, u.username, up.access FROM users u INNER JOIN usersprojects up ON up.user=u.id WHERE u.id=$user AND up.project=$projectId");
	$userRow = mysql_fetch_assoc($userQuery);
	if (!$userRow) return;
	$userTitle = $userRow['caption'];
	$username = $userRow['username'];

	$kickuser = getVariableOrNull('kickuser');
	if ($kickuser == 1 || $kickuser == 'on')
	{
		if (mysql_query("DELETE FROM usersprojects WHERE user=$user AND project=$projectId LIMIT 1") && (mysql_affected_rows() > 0))
		{
			logMessage("Uživatel $sepsLoggedUsername vyřadil uživatele $username z projektu $projectName");
            alert('Uživatel byl vyřazen z projektu', 'success');
		}
		else
		{
            alert('Nepodařilo se vyřadit uživatele z projektu', 'danger');
			//echo mysql_error();
		}
		return;
	}

	$newpriority = getVariableOrNull('priority');
	if (!is_numeric($newpriority)) $newpriority = null;
	$removeAccess = 0;
	$addAccess = 0;
	for ($accessBit = 1; $accessBit <= sepsAccessMaxValidBit; $accessBit <<= 1)
	{
		$field = getVariableOrNull("access_$accessBit");
		switch($field)
		{
			case 'add':
				$addAccess |= $accessBit;
				break;
			case 'remove':
				$removeAccess |= $accessBit;
				break;
		}
	}
	if (($addAccess & $removeAccess) != 0) $addAccess = $removeAccess = 0;

	$query = '';
	if ($newpriority != null) $query .= ", priority=$newpriority";
	if ($removeAccess || $addAccess)
	{
		$removeMask = ~$removeAccess;
		if ($removeAccess && $addAccess) $query .= ", access=((access & $removeMask) | $addAccess)";
		else if ($removeAccess) $query .= ", access=access & $removeMask";
		else if ($addAccess) $query .= ", access=access | $addAccess";
	}
	if ($query)
	{
		$query = 'UPDATE usersprojects SET ' . substr($query, 2) . " WHERE user=$user AND project=$projectId LIMIT 1";
        $success = mysql_query($query);
        if (!$success)
        {
			alert('Nepodařilo se aktualizovat uživatele', 'danger');
        }
		else if (mysql_affected_rows() > 0)
		{
			logMessage("Uživatel $sepsLoggedUsername upravil práva uživatele $username v projektu $projectName");
			alert('Uživatel byl upraven', 'success');
		}
		else
		{
			alert('Nebyly požadovány žádné změny', 'warning');
		}
	}
    else
    {
		alert('Nebyly požadovány žádné změny', 'warning');
    }
}

function accountCreationForm($username = '', $password = '', $password2 = '', $errormessage = null)
{
	global $sepsLoggedUser;

	echo '<form action="?" method="post"><input type="hidden" name="action" value="createuser" />';
	generateCsrfToken();
	$projectId = getVariableOrNull('project');
	if (!is_numeric($projectId)) $projectId = null;
	$projectName = null;
	if ($projectId)
	{
		$nameQuery = mysql_query("SELECT p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE p.id=$projectId AND up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanCreateAccount);
		$row = mysql_fetch_assoc($nameQuery);
		if ($row)
		{
			$projectName = $row['title'];
			echo "<input type='hidden' name='project' value='$projectId' />";
		}
		else $projectId = null;
	}

	$projectsFoundCount = 0;
	$projectIdFound = null;
	$projectsQuery = mysql_query("SELECT p.id, p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanCreateAccount);
	while ($row = mysql_fetch_assoc($projectsQuery))
	{
		$projectsFoundCount++;
		switch ($projectsFoundCount)
		{
			case 1:
				$projectIdFound = $row['id'];
				$projectTitleFound = $row['title'];
				break;
			case 2:
                beginDialog('Založení nového uživatele');
                beginDialogBody();
                if ($errormessage)
                {
                    alert($errormessage, 'danger');
                }
				echo '<div class="form-group"><label for="project">Projekt:</label> <select name="project" id="project" class="form-control">';
				echo "<option value='$projectIdFound'>" . htmlspecialchars($projectTitleFound) . "</option>";
				// fall-through!
			default:
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
		}
	}
	switch($projectsFoundCount)
	{
		case 0:
            alert('Nemáte oprávnění k založení nového uživatele!', 'danger');
			return;
		case 1:
			$projectId = $projectIdFound;
			$projectName = $projectTitleFound;
            beginDialog('Založení nového uživatele projektu ' . htmlspecialchars($projectTitleFound));
            beginDialogBody();
            if ($errormessage)
            {
                alert($errormessage, 'danger');
            }
			echo "<input type='hidden' name='project' value='$projectId' />";
			break;
		default:
			echo '</select></div>';
	}

	echo '<div class="form-group"><label for="username">Uživatelské jméno:</label> <input name="username" id="username" class="form-control" maxlength="100" value="' . htmlspecialchars($username) . '" /></div>';
	echo '<div class="form-group"><label for="password">Počáteční heslo:</label> <input type="password" name="password" id="password" class="form-control" value="' . htmlspecialchars($password) . '" /></div>';
	echo '<div class="form-group"><label for="password2">Zopakovat heslo:</label> <input type="password" name="password2" id="password2" class="form-control" value="' . htmlspecialchars($password2) . '" /></div>';

    endDialogBody();
    beginDialogFooter();
	echo '<input type="submit" value="Provést změny" class="btn btn-primary btn-lg" />';
    endDialogFooter();
    endDialog();
	echo '</form>';
}

function createNewUser()
{
	global $sepsLoggedUser, $sepsLoggedUsername, $sepsDefaultInvitationAccess;

	$project = intval(getVariableOrNull('project'));
	$username = mb_strtolower(trim(getVariableOrNull('username')));
	$password = getVariableOrNull('password');
	$password2 = getVariableOrNull('password2');

	if (!$project) return;
	if (!$username)
	{
		accountCreationForm($username, $password, $password2, 'Musíte zadat uživatelské jméno');
		return;
	}

	if ($password != $password2)
	{
		accountCreationForm($username, '', '', 'Zadaná hesla se navzájem liší');
		return;
	}

	mysql_query('BEGIN');

	$projectQuery = mysql_query("SELECT p.title, p.invitationaccessmask, up.access FROM projects p INNER JOIN usersprojects up ON up.project=p.id AND up.user=$sepsLoggedUser WHERE p.id=$project");
	$projectRow = mysql_fetch_assoc($projectQuery);

	if (!$projectRow || !($projectRow['access'] & sepsAccessFlagsCanCreateAccount))
	{
		echo '<div class="errmsg">V tomto projektu nemáte oprávnění zakládat uživatele</div>';
		return;
	}
	$projectAccess = $projectRow['access'];
	$projectName = $projectRow['title'];
	$givenaccess = $projectAccess & $sepsDefaultInvitationAccess & ~intval($projectRow['invitationaccessmask']);

	$existingUser = mysql_fetch_assoc(mysql_query("SELECT u.id, up.access FROM users u LEFT JOIN usersprojects up ON up.user=u.id AND up.project=$project WHERE username='" . mysql_real_escape_string($username) . "'"));
	if ($existingUser)
	{
		mysql_query('ROLLBACK');
		if (($existingUser['access'] & $givenaccess) == $givenaccess)
		{
			$errmsg = 'Zadané uživatelské jméno se již používá a tento uživatel již je členem tohoto projektu. Pokud chcete založit jiného uživatele, musíte zvolit jiné jméno.';
		}
		else
		{
			$errmsg = 'Zadané uživatelské jméno se již používá, zkuste zvolit jiné';
			if ($projectAccess & sepsAccessFlagsCanInvite) $errmsg .= '; pokud chcete existujícího uživatele přidat do projektu, odešlete na jeho e-mailovou adresu <a href="?action=invite">pozvánku</a>';
		}
		accountCreationForm($username, $password, $password2, $errmsg);
		return;
	}

	$username_sql = mysql_real_escape_string($username);
	if (!mysql_query("INSERT INTO users(username, caption, firstname, lastname, password, email, jabber, skype, icq, emailvalidated) VALUES ('"
			. $username_sql . "', '', '', '', '"
			. mysql_real_escape_string(hashPassword($password)) . "', '', '', '', '', 0)") || mysql_affected_rows() != 1)
	{
		mysql_query('ROLLBACK');
		accountCreationForm($username, $password, $password2, 'Nepodařilo se založit uživatele, zkuste to později');
		return;
	}

	$createdIdRow = mysql_fetch_row(mysql_query('SELECT id FROM users WHERE username="' . $username_sql . '"'));
	if (!$createdIdRow) return;
	$createdId = $createdIdRow[0];

	if (!mysql_query("INSERT INTO usersprojects(user, project, access) VALUES($createdId, $project, $givenaccess)") || mysql_affected_rows() != 1)
	{
		mysql_query('ROLLBACK');
		accountCreationForm($username, $password, $password2, 'Nepodařilo se uživateli přidat přístup do projektu, zkuste to později');
		return;
	}

	logMessage("Uživatel $sepsLoggedUsername založil nového uživatele $username a přidal jej do projektu $projectName");

	mysql_query('COMMIT');
    alert('Uživatel úspěšně založen', 'success');
}
