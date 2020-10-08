<?php

require_once('./include/Logging.php');

function manageUsersForm()
{
	global $sepsLoggedUser, $sepsAccessFlagNames, $sepsLoggedUserCaption, $sepsDbConnection;

	echo '<form action="?" method="post"><input type="hidden" name="action" value="manageusers" />';
	generateCsrfToken();
	$projectId = getVariableOrNull('project');
	if (!is_numeric($projectId)) $projectId = null;
	$projectName = null;
	if ($projectId)
	{
		$nameQuery = mysqli_query($sepsDbConnection, "SELECT p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE p.id=$projectId AND up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanChangeUserAccess);
		$row = mysqli_fetch_assoc($nameQuery);
		if ($row)
		{
			$projectName = $row['title'];
			echo "<input type='hidden' name='project' value='$projectId' />";
		}
		else $projectId = null;
	}

	if ($projectId)
	{
		modifyUser($projectId, $projectName);
	}

	echo '<div class="bottomform usermanagement">';
	if (!$projectId)
	{
		$projectsFoundCount = 0;
		$projectIdFound = null;
		$projectsQuery = mysqli_query($sepsDbConnection, "SELECT p.id, p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanChangeUserAccess);
		while ($row = mysqli_fetch_assoc($projectsQuery))
		{
			$projectsFoundCount++;
			switch ($projectsFoundCount)
			{
				case 1:
					$projectIdFound = $row['id'];
					$projectTitleFound = $row['title'];
					break;
				case 2:
					echo '<h2>Správa uživatelů</h2>';
					echo '<label for="project">Projekt:</label> <select name="project" id="project" onchange="javascript:this.form.submit()" />';
					echo "<option value='$projectIdFound'>" . htmlspecialchars($projectTitleFound) . "</option>";
					// fall-through!
				default:
					echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
		}
		switch($projectsFoundCount)
		{
			case 0:
				echo '<div class="errmsg">Nemáte oprávnění pro správu uživatelů!</div>';
				return;
			case 1:
				$projectId = $projectIdFound;
				$projectName = $projectTitleFound;
				echo "<input type='hidden' name='project' value='$projectId' />";
				break;
			default:
				echo '</select>';
		}
	}

	if ($projectId)
	{
		echo '<h2>Správa uživatelů projektu ' . htmlspecialchars($projectName) . '</h2>';
		echo '<label for="user">Uživatel:</label> <select name="user" id="user">';
		$query = mysqli_query($sepsDbConnection, "SELECT u.id, u.caption, u.username, u.email FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysqli_fetch_assoc($query))
		{
			$title = $row['caption'];
			if (!$title) $title = $row['username'];
			if ($row['email']) $title .= ' <' . $row['email'] . '>';
			echo "<option value='$row[id]'>" . htmlspecialchars($title) . "</option>";
		}
		echo '</select><br />';
		echo '<label for="priority">Priorita:</label> <select name="priority" id="priority">';
		echo '<option value="N">[Ponechat stávající]</option>';
		echo '<option value="20">' . userPriorityToString(20) . '</option>';
		echo '<option value="10">' . userPriorityToString(10) . '</option>';
		echo '<option value="0">' . userPriorityToString(0) . '</option>';
		echo '<option value="-10">' . userPriorityToString(-10) . '</option>';
		echo '<option value="-20">' . userPriorityToString(-20) . '</option>';
		echo '</select><br />';

		echo '<div class="formblock">';
		echo '<table><thead><caption>Oprávnění</caption></thead><tbody>';
		echo '<tr><th>#</th><th>Oprávnění</th><th>Neměnit</th><th>Přidělit</th><th>Odebrat</th></tr>';
		for ($accessBit = 1, $idx = 0; $accessBit <= sepsAccessMaxValidBit; $accessBit <<= 1, $idx++)
		{
			$counter = $idx + 1;
			echo "<tr><td class='number'>$counter</td><td>${sepsAccessFlagNames[$idx]}</td><td><input type='radio' name='access_$accessBit' value='keep' checked='checked'></td>";
			// TODO: check access?
			echo "<td><input type='radio' name='access_$accessBit' value='add'></td><td><input type='radio' name='access_$accessBit' value='remove'></td></tr>";
		}
		echo '</tbody></table>';
		echo '</div>';

		echo '<input type="checkbox" name="kickuser" id="kickuser" onclick="if (this.checked) return confirm(\'Určitě vyhodit uživatele?\')"><label for="kickuser">Vyhodit uživatele z projektu</label></input><br />';
	}

	echo '<input type="submit" value="Provést změny" />';

	if ($projectId)
	{
		echo '<div class="userslist"><table class="usersoverview">';
		echo '<thead><caption>Seznam uživatelů</caption></thead>';
		echo '<tbody>';
		echo '<tr><th>Uživatel</th><th>Priorita</th><th>Oprávnění</th></tr>';
		$query = mysqli_query($sepsDbConnection, "SELECT u.caption, u.username, up.priority, up.access, u.email FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysqli_fetch_assoc($query))
		{
			$title = $row['caption'];
			if (!$title) $title = $row['username'];
			$email = $row['email'];
			echo '<tr><td>';
			if ($email) echo '<span title="' . htmlspecialchars($email, ENT_QUOTES) . '">';
			echo htmlspecialchars($title);
			if ($email) echo '</span>';
			echo '</td><td class="number">' . userPriorityToString($row['priority']) . '</td><td><tt>';
			$access = $row['access'];
			for ($mask = 1, $idx = 1; $mask <= sepsAccessMaxValidBit; $mask <<= 1, $idx++)
			{
				$mod = $idx % 10;
				if ($mod == 0) echo ' ';
				echo ($access & $mask) ? $mod : '-';
			}
			echo '</tt></td>';
		}
		echo '</tbody>';
		echo '</table></div>';
	}

	echo '</div>';
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
	global $sepsLoggedUser, $sepsLoggedUserCaption,  $sepsLoggedUsername, $sepsDbConnection;

	$user = getVariableOrNull('user');
	if (!is_numeric($user))
	{
		return;
	}

	$userQuery = mysqli_query($sepsDbConnection, "SELECT u.id, u.caption, u.username, up.access FROM users u INNER JOIN usersprojects up ON up.user=u.id WHERE u.id=$user AND up.project=$projectId");
	$userRow = mysqli_fetch_assoc($userQuery);
	if (!$userRow) return;
	$userTitle = $userRow['caption'];
	$username = $userRow['username'];

	$kickuser = getVariableOrNull('kickuser');
	if ($kickuser == 1 || $kickuser == 'on')
	{
		if (mysqli_query($sepsDbConnection, "DELETE FROM usersprojects WHERE user=$user AND project=$projectId LIMIT 1") && (mysqli_affected_rows($sepsDbConnection) > 0))
		{
			logMessage("Uživatel $sepsLoggedUsername vyřadil uživatele $username z projektu $projectName");
			echo '<div class="infomsg">Uživatel byl vyřazen z projektu</div>';
		}
		else
		{
			echo '<div class="errmsg">Nepodařilo se vyřadit uživatele z projektu</div>';
			report_mysql_error();
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
		if (mysqli_query($sepsDbConnection, $query) && (mysqli_affected_rows($sepsDbConnection) > 0))
		{
			logMessage("Uživatel $sepsLoggedUsername upravil práva uživatele $username v projektu $projectName");
			echo '<div class="infomsg">Uživatel byl upraven</div>';
		}
		else
		{
			echo '<div class="errmsg">Nepodařilo se aktualizovat uživatele</div>';
		}
	}
}

function accountCreationForm($username = '', $password = '', $password2 = '', $errormessage = null)
{
	global $sepsLoggedUser, $sepsDbConnection;

	if ($errormessage)
	{
		echo "<div class='errmsg'>$errormessage</div>";
	}
	echo '<form action="?" method="post"><input type="hidden" name="action" value="createuser" />';
	generateCsrfToken();
	$projectId = getVariableOrNull('project');
	if (!is_numeric($projectId)) $projectId = null;
	$projectName = null;
	if ($projectId)
	{
		$nameQuery = mysqli_query($sepsDbConnection, "SELECT p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE p.id=$projectId AND up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanCreateAccount);
		$row = mysqli_fetch_assoc($nameQuery);
		if ($row)
		{
			$projectName = $row['title'];
			echo "<input type='hidden' name='project' value='$projectId' />";
		}
		else $projectId = null;
	}

	echo '<div class="bottomform usercreation">';
	$projectsFoundCount = 0;
	$projectIdFound = null;
	$projectsQuery = mysqli_query($sepsDbConnection, "SELECT p.id, p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanCreateAccount);
	while ($row = mysqli_fetch_assoc($projectsQuery))
	{
		$projectsFoundCount++;
		switch ($projectsFoundCount)
		{
			case 1:
				$projectIdFound = $row['id'];
				$projectTitleFound = $row['title'];
				break;
			case 2:
				echo '<h2>Založení nového uživatele</h2>';
				echo '<label for="project">Projekt:</label> <select name="project" id="project" />';
				echo "<option value='$projectIdFound'>" . htmlspecialchars($projectTitleFound) . "</option>";
				// fall-through!
			default:
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
		}
	}
	switch($projectsFoundCount)
	{
		case 0:
			echo '<div class="errmsg">Nemáte oprávnění k založení nového uživatele!</div>';
			return;
		case 1:
			$projectId = $projectIdFound;
			$projectName = $projectTitleFound;
			echo '<h2>Založení nového uživatele projektu ' . htmlspecialchars($projectTitleFound) . '</h2>';
			echo "<input type='hidden' name='project' value='$projectId' />";
			break;
		default:
			echo '</select><br />';
	}

	echo '<label for="username">Uživatelské jméno:</label> <input name="username" id="username" maxlength="100" value="' . htmlspecialchars($username) . '"><br />';
	echo '<label for="password">Počáteční heslo:</label> <input type="password" name="password" id="password" value="' . htmlspecialchars($password) . '"><br />';
	echo '<label for="password2">Zopakovat heslo:</label> <input type="password" name="password2" id="password2" value="' . htmlspecialchars($password2) . '"><br />';
	echo '<input type="submit" value="Provést změny" />';

	echo '</div>';
	echo '</form>';
}

function createNewUser()
{
	global $sepsLoggedUser, $sepsLoggedUsername, $sepsDefaultInvitationAccess, $sepsDbConnection;

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

	mysqli_query($sepsDbConnection, 'BEGIN');

	$projectQuery = mysqli_query($sepsDbConnection, "SELECT p.title, p.invitationaccessmask, up.access FROM projects p INNER JOIN usersprojects up ON up.project=p.id AND up.user=$sepsLoggedUser WHERE p.id=$project");
	$projectRow = mysqli_fetch_assoc($projectQuery);

	if (!$projectRow || !($projectRow['access'] & sepsAccessFlagsCanCreateAccount))
	{
		echo '<div class="errmsg">V tomto projektu nemáte oprávnění zakládat uživatele</div>';
		return;
	}
	$projectAccess = $projectRow['access'];
	$projectName = $projectRow['title'];
	$givenaccess = $projectAccess & $sepsDefaultInvitationAccess & ~intval($projectRow['invitationaccessmask']);

	$existingUser = mysqli_fetch_assoc(mysqli_query($sepsDbConnection, "SELECT u.id, up.access FROM users u LEFT JOIN usersprojects up ON up.user=u.id AND up.project=$project WHERE username='" . mysqli_real_escape_string($sepsDbConnection, $username) . "'"));
	if ($existingUser)
	{
		mysqli_query($sepsDbConnection, 'ROLLBACK');
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

	$username_sql = mysqli_real_escape_string($sepsDbConnection, $username);
	if (!mysqli_query($sepsDbConnection, "INSERT INTO users(username, caption, firstname, lastname, password, email, jabber, skype, icq, emailvalidated) VALUES ('"
			. $username_sql . "', '', '', '', '"
			. mysqli_real_escape_string($sepsDbConnection, hashPassword($password)) . "', '', '', '', '', 0)") || mysqli_affected_rows($sepsDbConnection) != 1)
	{
		mysqli_query($sepsDbConnection, 'ROLLBACK');
		accountCreationForm($username, $password, $password2, 'Nepodařilo se založit uživatele, zkuste to později');
		return;
	}

	$createdIdRow = mysqli_fetch_row(mysqli_query($sepsDbConnection, 'SELECT id FROM users WHERE username="' . $username_sql . '"'));
	if (!$createdIdRow) return;
	$createdId = $createdIdRow[0];

	if (!mysqli_query($sepsDbConnection, "INSERT INTO usersprojects(user, project, access) VALUES($createdId, $project, $givenaccess)") || mysqli_affected_rows($sepsDbConnection) != 1)
	{
		mysqli_query($sepsDbConnection, 'ROLLBACK');
		accountCreationForm($username, $password, $password2, 'Nepodařilo se uživateli přidat přístup do projektu, zkuste to později');
		return;
	}

	logMessage("Uživatel $sepsLoggedUsername založil nového uživatele $username a přidal jej do projektu $projectName");

	mysqli_query($sepsDbConnection, 'COMMIT');
	echo '<div class="infomsg">Uživatel úspěšně založen</div>';
}
