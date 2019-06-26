<?php

function createProject()
{
	global $sepsLoggedUsername, $sepsLoggedUserGlobalRights, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanCreateProjects)) return;

	$projectName = $_POST['projectname'];
	$initialUser = $_POST['initialuser'];

	if (!$projectName)
	{
		echo '<div class="errmsg">Chybí název projektu.</div>';
		return;
	}

	mysqli_query($sepsDbConnection, 'BEGIN');

    $checkquery = mysqli_query($sepsDbConnection, "SELECT id FROM projects WHERE title='" . mysqli_real_escape_string($sepsDbConnection, $projectName) . "'");
    if (mysqli_fetch_row($checkquery))
	{
		echo '<div class="errmsg">Takto pojmenovaný projekt už existuje.</div>';
		mysqli_query($sepsDbConnection, 'ROLLBACK');
		return;
	}

	$initialUserId = null;
	if ($initialUser)
	{
		$userQuery = mysqli_query($sepsDbConnection, "SELECT id FROM users WHERE username='" . mysqli_real_escape_string($sepsDbConnection, $initialUser) . "'");
		$users = mysqli_fetch_row($userQuery);
		if (!$users)
		{
			echo '<div class="errmsg">Takový uživatel neexistuje.</div>';
			mysqli_query($sepsDbConnection, 'ROLLBACK');
			return;
		}
		$initialUserId = $users[0];
	}

    mysqli_query($sepsDbConnection, "INSERT INTO projects(title) VALUES ('" . mysqli_real_escape_string($sepsDbConnection, $projectName) . "')");
	logMessage("Uživatel $sepsLoggedUsername založil nový projekt '$projectName'");

	if ($initialUserId)
	{
		$fullAccess = sepsAccessMaxValidBit * 2 - 1;
		mysqli_query($sepsDbConnection, "INSERT INTO usersprojects(user, project, access) VALUES ($initialUserId, (SELECT p.id FROM projects p WHERE p.title='" . mysqli_real_escape_string($sepsDbConnection, $projectName) . "'), $fullAccess)");
		logMessage("Do projektu '$projectName' byl přidán počáteční uživatel '$initialUser'");
	}

	mysqli_query($sepsDbConnection, 'COMMIT');
}

function projectCreationForm()
{
	global $sepsLoggedUsername, $sepsLoggedUserGlobalRights;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanCreateProjects)) return;

	echo '<div class="bottomform projectcreation">';
	echo '<h2>Založení nového projektu</h2>';
	echo '<form action="?" method="post"><input type="hidden" name="action" value="createproject" />';
	generateCsrfToken();

	echo '<label for="projectname">Název projektu:</label> <input name="projectname" id="projectname" maxlength="100" /><br />';
	echo '<label for="initialuser">Prvotní uživatel:</label> <input name="initialuser" id="initialuser" maxlength="100" value="' . htmlspecialchars($sepsLoggedUsername, ENT_QUOTES) . '" /><br />';
	echo '<input type="submit" value="Založit projekt" />';
	echo '</form>';
	echo '</div>';
}

function deleteProject()
{
	global $sepsLoggedUsername, $sepsLoggedUserGlobalRights, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanDeleteProjects)) return;

	$project = intval($_POST['project']);
	$safetyCodeExpected = $_POST['safetycodevalue'];
	$safetyCodeForm = $_POST['safetycode'];

	if (!$project)
	{
		echo '<div class="errmsg">Musíte si vybrat projekt.</div>';
		return;
	}

	if (!$safetyCodeForm || $safetyCodeExpected != $safetyCodeForm)
	{
		echo '<div class="errmsg">Chybně vyplněný bezpečnostní kód.</div>';
		return;
	}

	mysqli_query($sepsDbConnection, 'BEGIN');

	$projectRow = mysqli_fetch_array(mysqli_query($sepsDbConnection, "SELECT title FROM projects WHERE id=$project"));
	if (!$projectRow)
	{
		echo '<div class="errmsg">Projekt neexistuje.</div>';
		mysqli_query($sepsDbConnection, 'ROLLBACK');
		return;
	}
	$projectTitle = $projectRow[0];

	if (!mysqli_query($sepsDbConnection, "DELETE FROM emailcodes WHERE forproject=$project")) report_mysql_error();
	$countCodes = mysqli_affected_rows($sepsDbConnection);
	if (!mysqli_query($sepsDbConnection, "DELETE subscriptions FROM subscriptions INNER JOIN events ON events.id=subscriptions.event INNER JOIN eventtypes ON eventtypes.id=events.eventtype WHERE eventtypes.project=$project")) report_mysql_error();
	$countSubscriptions = mysqli_affected_rows($sepsDbConnection);
	if (!mysqli_query($sepsDbConnection, "DELETE events FROM events INNER JOIN eventtypes ON eventtypes.id=events.eventtype WHERE eventtypes.project=$project")) report_mysql_error();
	$countEvents = mysqli_affected_rows($sepsDbConnection);
	if (!mysqli_query($sepsDbConnection, "DELETE FROM eventtypes WHERE project=$project")) report_mysql_error();
	$countEventTypes = mysqli_affected_rows($sepsDbConnection);
	if (!mysqli_query($sepsDbConnection, "DELETE FROM usersprojects WHERE project=$project")) report_mysql_error();
	$countUserProjects = mysqli_affected_rows($sepsDbConnection);
	if (!mysqli_query($sepsDbConnection, "DELETE FROM projects WHERE id=$project")) report_mysql_error();

	logMessage("Uživatel $sepsLoggedUsername smazal projekt '$projectTitle': $countEvents/$countEventTypes/$countSubscriptions/$countUserProjects/$countCodes");
	
	mysqli_query($sepsDbConnection, 'COMMIT');

	echo '<div class="infomsg">Project ' . htmlspecialchars($projectTitle) . ' byl smazán.</div>';
}

function projectDeletionForm()
{
	global $sepsLoggedUsername, $sepsLoggedUserGlobalRights, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanDeleteProjects)) return;

	echo '<div class="bottomform projectdeletion">';
	echo '<h2>Smazání projektu</h2>';

	echo '<p class="warnmsg">POZOR! Tímto se smaže projekt, zruší všechny jeho události a uživatelské přístupy. Toto je velmi nebezpečná operace, nelze ji nijak vrátit zpět!</p>';

	echo '<form action="?" method="post"><input type="hidden" name="action" value="removeproject" />';
	generateCsrfToken();

	echo '<label for="project">Projekt:</label> <select name="project" id="project"><option value="" selected="1">[Vyberte projekt]</option>';
	$query = mysqli_query($sepsDbConnection, 'SELECT id, title FROM projects');
	while ($project = mysqli_fetch_assoc($query))
	{
		$projectId = $project['id'];
		$projectName = $project['title'];
		echo "<option value='$projectId'>" . htmlspecialchars($projectName) . "</option>";
	}
	echo '</select><br />';
	$safetyToken = generateRandomToken(5);
	echo "<input type='hidden' name='safetycodevalue' value='$safetyToken' />";
	echo "<label for='safetycode'>Do následujícího políčka opište text <tt>$safetyToken</tt>:</label> <input name='safetycode' id='safetycode' maxlength='5' size='5' /><br />";
	echo '<input type="submit" value="Smazat projekt" onclick="return confirm(\'Poslední varování: Opravdu chcete smazat tento projekt?\')" />';
	echo '</form>';
	echo '</div>';
}

function changeGlobalPermissions()
{
	global $sepsLoggedUsername, $sepsLoggedUserGlobalRights, $sepsGlobalAccessFlagNames, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanManageGlobalPermissions)) return;

	$managedUser = intval($_POST['manageduser']);
	if (!$managedUser)
	{
		echo '<div class="errmsg">Chybí uživatel.</div>';
		return;
	}

	$finalAccess = 0;
	for ($accessBit = 1, $idx = 0; $accessBit <= sepsGlobalAccessMaxValidBit; $accessBit <<= 1, $idx++)
	{
		$name = "access_$accessBit";
		if (isset($_POST[$name]) && $_POST[$name] == '1') $finalAccess |= $accessBit;
	}

	mysqli_query($sepsDbConnection, "UPDATE users SET globalrights=$finalAccess WHERE id=$managedUser LIMIT 1");
	if (mysqli_affected_rows($sepsDbConnection) == 1)
	{
		logMessage("Uživatel $sepsLoggedUsername nastavil globální práva uživatele #$managedUser na $finalAccess");
		echo '<div class="infomsg">Globální práva nastavena.</div>';
	}
	else
	{
		echo '<div class="errmsg">Nepodařilo se nastavit práva.</div>';
		return;
	}
}

function manageGlobalPermissionsForm()
{
	global $sepsLoggedUserGlobalRights, $sepsGlobalAccessFlagNames, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanManageGlobalPermissions)) return;

	echo '<div class="bottomform globalpermissions">';

	$managedUsername = getVariableOrNull('manageduser');
	if ($managedUsername)
	{
		$userQuery = mysqli_query($sepsDbConnection, "SELECT id, globalrights FROM users WHERE username='" . mysqli_real_escape_string($sepsDbConnection, $managedUsername) . "'");
		$users = mysqli_fetch_assoc($userQuery);
		if (!$users)
		{
			echo '<div class="errmsg">Takový uživatel neexistuje.</div>';
			$managedUsername = null;
		}
		else
		{
			echo '<h2>Správa globálních práv uživatele ' . htmlspecialchars($managedUsername) . '</h2>';
			$managedUser = intval($users['id']);
			$currentRights = $users['globalrights'];
			echo '<form action="?" method="post"><input type="hidden" name="action" value="changeglobalpermissions" />';
			generateCsrfToken();
			echo "<input type='hidden' name='manageduser' value='$managedUser' />";

			echo '<div class="formblock">';
			for ($accessBit = 1, $idx = 0; $accessBit <= sepsGlobalAccessMaxValidBit; $accessBit <<= 1, $idx++)
			{
				echo "<input type='checkbox' name='access_$accessBit' value='1' " . (($currentRights & $accessBit) ? 'checked="checked" ' : '') . ">${sepsGlobalAccessFlagNames[$idx]}</input><br />";
			}
			echo '</div>';
			echo '<input type="submit" value="Provést změny" />';
			echo '</form>';
		}
	}

	if (!$managedUsername)
	{
		echo '<h2>Správa globálních práv</h2>';
		echo '<form action="?" method="get"><input type="hidden" name="action" value="manageglobalpermissions" />';
		echo '<label for="username">Uživatel:</label> <input name="manageduser" id="manageduser" maxlength="100" /><br />';
		echo '<input type="submit" value="Zvolit uživatele" />';
		echo '</form>';
	}
	echo '</div>';
}
