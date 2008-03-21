<?php

function eventTypesForm()
{
	global $sepsLoggedUser;

	echo '<form action="?" method="post"><input type="hidden" name="action" value="manageeventtypes" />';
	$projectId = getVariableOrNull('project');
	if (!is_numeric($projectId)) $projectId = null;
	$projectName = null;
	if ($projectId)
	{
		$nameQuery = mysql_query("SELECT p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE p.id=$projectId AND up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanEditEventTypes);
		$row = mysql_fetch_assoc($nameQuery);
		if ($row)
		{
			$projectName = $row['title'];
			echo "<input type='hidden' name='project' value='$projectId' />";
		}
		else $projectId = null;
	}

	echo '<div class="bottomform eventtypes">';
	if (!$projectId)
	{
		$projectsFoundCount = 0;
		$projectIdFound = null;
		$projectsQuery = mysql_query("SELECT p.id, p.title FROM projects p INNER JOIN usersprojects up ON up.project=p.id WHERE up.user=$sepsLoggedUser AND up.access & " . sepsAccessFlagsCanEditEventTypes);
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
					echo '<h2>Správa typů událostí</h2>';
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
				echo '<div class="errmsg">Nemáte oprávnění pro editaci typů událostí!</div>';
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
		$addcaption = getVariableOrNull('addcaption');
		$addminusers = getVariableOrNull('addminusers');
		if (!is_numeric($addminusers)) $addminusers = null;
		if ($addminusers < 0) $addminusers = null;
		$addmaxusers = getVariableOrNull('addmaxusers');
		if (!is_numeric($addmaxusers)) $addmaxusers = null;
		if ($addmaxusers <= 0) $addmaxusers = null;
		$editcaption = getVariableOrNull('editcaption');
		$editminusers = getVariableOrNull('editminusers');
		if (!is_numeric($editminusers)) $editminusers = null;
		if ($editminusers < 0) $editminusers = null;
		$editmaxusers = getVariableOrNull('editmaxusers');
		if (!is_numeric($editmaxusers)) $editmaxusers = null;
		if ($editmaxusers <= 0) $editmaxusers = null;
		$eventTypeAction = getVariableOrNull('eventtypeaction');

		$editeventtype = getAndCheckEventType('editeventtype', $projectId);
		$removeeventtype = getAndCheckEventType('removeeventtype', $projectId);

		switch($eventTypeAction)
		{
			case 'add':
				if (!$addcaption || $addminusers < 0 || $addmaxusers <= 0)
				{
					echo '<div class="errmsg">Je potřeba vyplnit všechny tři položky</div>';
					break;
				}
				if ($addminusers > $addmaxusers)
				{
					echo '<div class="errmsg">Minimum nemůže být vyšší než maximum</div>';
					break;
				}
				if (mysql_query("INSERT INTO eventtypes (title, capacity, minpeople, project) VALUES('" . mysql_real_escape_string($addcaption) . "', $addmaxusers, $addminusers, $projectId)"))
				{
					echo '<div class="infomsg">Nový typ vytvořen</div>';
					$addcaption = $addminusers = $addmaxusers = null;
				}
				break;
			case 'edit':
				$changes = 0;
				if ($editcaption)
				{
					if (mysql_query("UPDATE eventtypes SET title='" . mysql_real_escape_string($editcaption) . "' WHERE id=$editeventtype LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
						echo '<div class="errmsg">Chyba při změně názvu</div>';
				}
				if (($editminusers != null) && ($editmaxusers != null))
				{
					if (mysql_query("UPDATE eventtypes SET capacity=$editmaxusers, minpeople=$editminusers WHERE id=$editeventtype LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
						echo '<div class="errmsg">Chyba při změně kapacity</div>';
				}
				else if ($editminusers)
				{
					if (mysql_query("UPDATE eventtypes SET minpeople=$editminusers WHERE id=$editeventtype AND capacity>=$editminusers LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
						echo '<div class="errmsg">Chyba při změně kapacity</div>';
				}
				else if ($editmaxusers)
				{
					if (mysql_query("UPDATE eventtypes SET capacity=$editmaxusers WHERE id=$editeventtype AND minpeople<=$editmaxusers LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
						echo '<div class="errmsg">Chyba při změně kapacity</div>';
				}
				if ($changes)
				{
					echo '<div class="infomsg">Změny provedeny</div>';
					$editcaption = $editminusers = $editmaxusers = null;
				}
				break;
			case 'remove':
				if (!$removeeventtype)
				{
					echo '<div class="errmsg">Není co mazat</div>';
					break;
				}
				if (mysql_query("DELETE FROM eventtypes WHERE eventtypes.id=$removeeventtype AND NOT EXISTS (SELECT e.id FROM events e WHERE e.eventtype=eventtypes.id) LIMIT 1") && (mysql_affected_rows() > 0))
				{
					echo '<div class="infomsg">Typ byl smazán.</div>';
				}
				else
				{
					echo '<div class="errmsg">Nepodařilo se smazat typ. Nepoužívá se někde?</div>';
				}
				break;
			default:
				if (getVariableOrNull('doexecute')) echo '<div class="errmsg">Musíte vybrat operaci, která se má provést.</div>';
		}

		echo '<h2>Správa typů událostí projektu ' . htmlspecialchars($projectName) . '</h2>';
		echo '<div class="formblock">';
		echo '<input class="formblockchooser" type="radio" name="eventtypeaction" value="add" /> Přidat nový typ události<br />';
		echo '<label for="addcaption">Název:</label> <input type="text" name="addcaption" id="addcaption" value="' . htmlspecialchars($addcaption) . '" /><br />';
		echo '<label for="addminusers">Minimálně účastníků:</label> <input type="text" name="addminusers" id="addminusers" value="' . htmlspecialchars($addminusers) . '" /><br />';
		echo '<label for="addmaxusers">Maximálně účastníků:</label> <input type="text" name="addmaxusers" id="addmaxusers" value="' . htmlspecialchars($addmaxusers) . '" /><br />';
		echo '</div>';

		$query = mysql_query("SELECT t.id, t.title FROM eventtypes t WHERE t.project=$projectId");
		if (mysql_num_rows($query))
		{
			echo '<div class="formblock">';
			echo '<input class="formblockchooser" type="radio" name="eventtypeaction" value="edit" /> Editovat existující typ události<br />';
			echo '<label for="editeventtype">Editovaný typ události:</label> <select name="editeventtype" id="editeventtype">';
			while ($row = mysql_fetch_assoc($query))
			{
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
			echo '</select><br />';
			echo '<label for="editcaption">Název:</label> <input type="text" name="editcaption" id="editcaption" value="' . htmlspecialchars($editcaption) . '" /><br />';
			echo '<label for="editminusers">Minimálně účastníků:</label> <input type="text" name="editminusers" id="editminusers" value="' . htmlspecialchars($editminusers) . '" /><br />';
			echo '<label for="editmaxusers">Maximálně účastníků:</label> <input type="text" name="editmaxusers" id="editmaxusers" value="' . htmlspecialchars($editmaxusers) . '" /><br />';
			echo '</div>';
		}

		$query = mysql_query("SELECT t.id, t.title FROM eventtypes t WHERE t.project=$projectId AND NOT EXISTS (SELECT e.id FROM events e WHERE e.eventtype=t.id)");
		if (mysql_num_rows($query))
		{
			echo '<div class="formblock">';
			echo '<input class="formblockchooser" type="radio" name="eventtypeaction" value="remove" /> Smazat typ události<br />';
			echo '<label for="removeeventtype">Mazaný typ události:</label> <select name="removeeventtype" id="removeeventtype">';
			while ($row = mysql_fetch_assoc($query))
			{
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
			echo '</select>';
			echo '</div>';
		}
	}

	echo '<input type="submit" name="doexecute" value="Provést změny" />';

	if ($projectId)
	{
		echo '<div class="eventtypeslist"><table class="eventtypesoverview">';
		echo '<thead><caption>Definované typy</caption></thead>';
		echo '<tbody>';
		echo '<tr><th>Název</th><th>Min</th><th>Max</th></tr>';
		$query = mysql_query("SELECT t.title, t.minpeople, t.capacity FROM eventtypes t WHERE t.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			echo '<tr><td>' . htmlspecialchars($row['title']) . "</td><td>$row[minpeople]</td><td>$row[capacity]</td></tr>";
		}
		echo '</tbody>';
		echo '</table></div>';
	}

	echo '</div>';
	echo '</form>';
}

function getAndCheckEventType($variablename, $projectid)
{
	$id = getVariableOrNull($variablename);
	if (!is_numeric($id)) return null;
	$query = mysql_query("SELECT t.id FROM eventtypes t WHERE t.id=$id AND t.project=$projectid");
	if (!mysql_fetch_row($query)) return null;
	return $id;
}
