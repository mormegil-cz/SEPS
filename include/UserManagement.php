<?php

require_once('./include/Logging.php');

function manageUsersForm()
{
	global $sepsLoggedUser, $sepsAccessFlagNames, $sepsLoggedUserCaption;

	echo '<form action="?" method="post"><input type="hidden" name="action" value="manageusers" />';
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

	if ($projectId)
	{
		modifyUser($projectId, $projectName);
	}

	echo '<div class="bottomform usermanagement">';
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
				break;
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
		$query = mysql_query("SELECT u.id, u.caption FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			echo "<option value='$row[id]'>" . htmlspecialchars($row['caption']) . "</option>";
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

		echo '<input type="checkbox" name="kickuser" id="kickuser"><label for="kickuser" onclick="if (this.checked) return confirm(\'Určitě vyhodit uživatele?\')">Vyhodit uživatele z projektu</label></input><br />';
	}

	echo '<input type="submit" value="Provést změny" />';

	if ($projectId)
	{
		echo '<div class="userslist"><table class="usersoverview">';
		echo '<thead><caption>Seznam uživatelů</caption></thead>';
		echo '<tbody>';
		echo '<tr><th>Uživatel</th><th>Priorita</th><th>Oprávnění</th></tr>';
		$query = mysql_query("SELECT u.caption, up.priority, up.access FROM users u INNER JOIN usersprojects up ON up.user=u.id AND up.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			echo '<tr><td>' . htmlspecialchars($row['caption']) . '</td><td class="number">' . userPriorityToString($row['priority']) . '</td><td><tt>';
			$access = $row['access'];
			for ($mask = 1, $idx = 1; $mask <= 1024; $mask <<= 1, $idx++)
			{
				echo ($access & $mask) ? ($idx % 10) : '-';
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
	global $sepsLoggedUser, $sepsLoggedUserCaption;

	$user = getVariableOrNull('user');
	if (!is_numeric($user)) return;

	$userQuery = mysql_query("SELECT u.id, u.caption, up.access FROM users u INNER JOIN usersprojects up ON up.user=u.id WHERE u.id=$user");
	$userRow = mysql_fetch_assoc($userQuery);
	if (!$userRow) return;
	$userTitle = $userRow['caption'];

	if (getVariableOrNull('kickuser') == 1)
	{
		if (mysql_query("DELETE FROM useraccess WHERE id=$user LIMIT 1") && (mysql_affected_rows() > 0))
		{
			logMessage("Uživatel $sepsLoggedUserCaption vyřadil uživatele $userTitle z projektu $projectName");
			echo '<div class="infomsg">Uživatel byl vyřazen z projektu</div>';
		}
		else
		{
			echo '<div class="errmsg">Nepodařilo se vyřadit uživatele z projektu</div>';
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
		if (mysql_query($query) && (mysql_affected_rows() > 0))
		{
			logMessage("Uživatel $sepsLoggedUserCaption upravil práva uživatele $userTitle v projektu $projectName");
			echo '<div class="infomsg">Uživatel byl upraven</div>';
		}
		else
		{
			echo '<div class="errmsg">Nepodařilo se aktualizovat uživatele</div>';
		}
	}
}
