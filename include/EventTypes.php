<?php

function eventTypesForm()
{
	global $sepsLoggedUser, $sepsLoggedUsername;

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
		}
		else $projectId = null;
	}

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
                    echo '<form action="?" method="post"><input type="hidden" name="action" value="manageeventtypes" />';
                    generateCsrfToken();
                    beginDialog('Správa typů událostí');
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
				alert('Nemáte oprávnění pro editaci typů událostí!', 'danger');
				return;
			case 1:
				$projectId = $projectIdFound;
				$projectName = $projectTitleFound;
				break;
			default:
				echo '</select></div>';
                echo '<p class="text-right"><input type="submit" name="chooseproject" class="btn btn-primary btn-lg" value="Vybrat" /> <a href="?" class="btn btn-default btn-lg">Zavřít</a></p>';
                echo '</form>';
                endDialogBody();
                endDialog();
                return;
		}
	}
	if ($projectId)
	{
        beginDialog('Správa typů událostí projektu ' . htmlspecialchars($projectName));
        beginDialogBody();

		$addcaption = getVariableOrNull('addcaption');
		$addminusers = getVariableOrNull('addminusers');
		if (!is_numeric($addminusers)) $addminusers = null;
		if ($addminusers < 0) $addminusers = null;
		$addmaxusers = getVariableOrNull('addmaxusers');
		if (!is_numeric($addmaxusers)) $addmaxusers = null;
		if ($addmaxusers <= 0) $addmaxusers = null;
		$addmaxguests = getVariableOrNull('addmaxguests');
		if (!is_numeric($addmaxguests)) $addmaxguests = null;
		if ($addmaxguests <= 0) $addmaxguests = null;
		$editcaption = getVariableOrNull('editcaption');
		$editminusers = getVariableOrNull('editminusers');
		if (!is_numeric($editminusers)) $editminusers = null;
		if ($editminusers < 0) $editminusers = null;
		$editmaxusers = getVariableOrNull('editmaxusers');
		if (!is_numeric($editmaxusers)) $editmaxusers = null;
		if ($editmaxusers <= 0) $editmaxusers = null;
		$editmaxguests = getVariableOrNull('editmaxguests');
		if (!is_numeric($editmaxguests)) $editmaxguests = null;
		if ($editmaxguests <= 0) $editmaxguests = null;
		$eventTypeAction = getVariableOrNull('eventtypeaction');

		$editeventtype = getAndCheckEventType('editeventtype', $projectId);
		$removeeventtype = getAndCheckEventType('removeeventtype', $projectId);

		switch($eventTypeAction)
		{
			case 'add':
				if (!$addcaption || $addminusers < 0 || $addmaxusers <= 0)
				{
					alert('Je potřeba vyplnit název a limity na počet účastníků', 'danger');
					break;
				}
				if ($addminusers > $addmaxusers)
				{
					alert('Minimum nemůže být vyšší než maximum', 'danger');
					break;
				}
				if (mysql_query("INSERT INTO eventtypes (title, capacity, minpeople, project, maxguests) VALUES('" . mysql_real_escape_string($addcaption) . "', $addmaxusers, $addminusers, $projectId, " . intval($addmaxguests) . ")"))
				{
					logMessage("Uživatel $sepsLoggedUsername založil nový typ události '$addcaption' v projektu $projectName");
					alert('Nový typ vytvořen', 'success');
					$addcaption = $addminusers = $addmaxusers = $addmaxguests = null;
				}
				break;
			case 'edit':
				$changes = 0;
				if ($editcaption)
				{
					if (mysql_query("UPDATE eventtypes SET title='" . mysql_real_escape_string($editcaption) . "' WHERE id=$editeventtype LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
                        alert('Chyba při změně názvu', 'danger');
				}
				if (($editminusers != null) && ($editmaxusers != null))
				{
					if (mysql_query("UPDATE eventtypes SET capacity=$editmaxusers, minpeople=$editminusers WHERE id=$editeventtype LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
                        alert('Chyba při změně kapacity', 'danger');
				}
				else if ($editminusers)
				{
					if (mysql_query("UPDATE eventtypes SET minpeople=$editminusers WHERE id=$editeventtype AND capacity>=$editminusers LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
                        alert('Chyba při změně kapacity', 'danger');
				}
				else if ($editmaxusers)
				{
					if (mysql_query("UPDATE eventtypes SET capacity=$editmaxusers WHERE id=$editeventtype AND minpeople<=$editmaxusers LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
                        alert('Chyba při změně kapacity', 'danger');
				}
				if ($editmaxguests)
				{
					if (mysql_query("UPDATE eventtypes SET maxguests=$editmaxguests WHERE id=$editeventtype LIMIT 1") && (mysql_affected_rows() > 0))
						$changes++;
					else
                        alert('Chyba při změně dovoleného počtu hostů', 'danger');
				}
				if ($changes)
				{
					logMessage("Uživatel $sepsLoggedUsername upravil typ události #$editeventtype v projektu $projectName");
					alert('Změny provedeny', 'success');
					$editcaption = $editminusers = $editmaxusers = $editmaxguests = null;
				}
				break;
			case 'remove':
				if (!$removeeventtype)
				{
					alert('Není co mazat', 'danger');
					break;
				}
				if (mysql_query("DELETE FROM eventtypes WHERE eventtypes.id=$removeeventtype AND NOT EXISTS (SELECT e.id FROM events e WHERE e.eventtype=eventtypes.id) LIMIT 1") && (mysql_affected_rows() > 0))
				{
					logMessage("Uživatel $sepsLoggedUsername smazal typ události #$removeeventtype v projektu $projectName");
					alert('Typ byl smazán', 'success');
				}
				else
				{
					alert('Nepodařilo se smazat typ. Nepoužívá se někde?', 'danger');
				}
				break;
			default:
				if (getVariableOrNull('doexecute')) alert('Musíte vybrat operaci, která se má provést.', 'danger');
		}

        echo '<ul class="nav nav-tabs" role="tablist">';
        echo '<li class="active"><a href="#addeventtype" role="tab" data-toggle="tab">Přidat nový typ</a></li>';
        echo '<li><a href="#editeventtype" role="tab" data-toggle="tab">Editovat existující typ</a></li>';
        echo '<li><a href="#removeeventtype" role="tab" data-toggle="tab">Smazat existující typ</a></li>';
        echo '</ul>';

        echo '<div class="tab-content">';

        echo '<div class="tab-pane active" id="addeventtype">';
        echo '<form action="?" method="post"><input type="hidden" name="action" value="manageeventtypes" />';
        generateCsrfToken();
        echo "<input type='hidden' name='project' value='$projectId' />";
        echo "<input type='hidden' name='eventtypeaction' value='add' />";
		echo '<div class="form-group"><label for="addcaption">Název:</label> <input type="text" name="addcaption" id="addcaption" class="form-control" value="' . htmlspecialchars($addcaption) . '" /></div>';
		echo '<div class="form-group"><label for="addminusers">Minimálně účastníků:</label> <input type="number" name="addminusers" id="addminusers" class="form-control" value="' . htmlspecialchars($addminusers) . '" min="0" step="1" /></div>';
		echo '<div class="form-group"><label for="addmaxusers">Maximálně účastníků:</label> <input type="number" name="addmaxusers" id="addmaxusers" class="form-control" value="' . htmlspecialchars($addmaxusers) . '" min="1" step="1" /></div>';
		echo '<div class="form-group"><label for="addmaxguests">Max. hostů na účastníka:</label> <input type="number" name="addmaxguests" id="addmaxguests" class="form-control" value="' . htmlspecialchars($addmaxguests) . '" min="0" step="1" /></div>';
        echo '<p class="text-right"><input type="submit" name="doexecute" class="btn btn-primary btn-lg" value="Provést změny" /> <a href="?" class="btn btn-default btn-lg">Zavřít</a></p>';
        echo '</form>';
		echo '</div>';

        echo '<div class="tab-pane" id="editeventtype">';
        echo '<form action="?" method="post"><input type="hidden" name="action" value="manageeventtypes" />';
        generateCsrfToken();
        echo "<input type='hidden' name='project' value='$projectId' />";
        echo "<input type='hidden' name='eventtypeaction' value='edit' />";
		$query = mysql_query("SELECT t.id, t.title FROM eventtypes t WHERE t.project=$projectId");
		if (mysql_num_rows($query))
		{
			echo '<div class="form-group"><label for="editeventtype">Editovaný typ události:</label> <select name="editeventtype" id="editeventtype" class="form-control">';
			while ($row = mysql_fetch_assoc($query))
			{
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
			echo '</select></div>';
			echo '<div class="form-group"><label for="editcaption">Název:</label> <input type="text" name="editcaption" id="editcaption" class="form-control" value="' . htmlspecialchars($editcaption) . '" /></div>';
			echo '<div class="form-group"><label for="editminusers">Minimálně účastníků:</label> <input type="number" name="editminusers" id="editminusers" class="form-control" value="' . htmlspecialchars($editminusers) . '" min="0" step="1" /></div>';
			echo '<div class="form-group"><label for="editmaxusers">Maximálně účastníků:</label> <input type="number" name="editmaxusers" id="editmaxusers" class="form-control" value="' . htmlspecialchars($editmaxusers) . '" min="1" step="1" /></div>';
			echo '<div class="form-group"><label for="editmaxguests">Max. hostů na účastníka:</label> <input type="number" name="editmaxguests" id="editmaxguests" class="form-control" value="' . htmlspecialchars($editmaxguests) . '" min="0" step="1" /></div>';
            echo '<p class="text-right"><input type="submit" name="doexecute" class="btn btn-primary btn-lg" value="Provést změny" /> <a href="?" class="btn btn-default btn-lg">Zavřít</a></p>';
		}
        else
        {
            alert('V tomto projektu dosud nejsou definovány žádné typy událostí', 'danger');
        }
        echo '</form>';
        echo '</div>';

        echo '<div class="tab-pane" id="removeeventtype">';
        echo '<form action="?" method="post"><input type="hidden" name="action" value="manageeventtypes" />';
        generateCsrfToken();
        echo "<input type='hidden' name='project' value='$projectId' />";
        echo "<input type='hidden' name='eventtypeaction' value='remove' />";
		$query = mysql_query("SELECT t.id, t.title FROM eventtypes t WHERE t.project=$projectId AND NOT EXISTS (SELECT e.id FROM events e WHERE e.eventtype=t.id)");
		if (mysql_num_rows($query))
		{
			echo '<div class="form-group"><label for="removeeventtype">Mazaný typ události:</label> <select name="removeeventtype" id="removeeventtype" class="form-control">';
			while ($row = mysql_fetch_assoc($query))
			{
				echo "<option value='$row[id]'>" . htmlspecialchars($row['title']) . "</option>";
			}
			echo '</select></div>';
            echo '<p class="text-right"><input type="submit" name="doexecute" class="btn btn-primary btn-lg" value="Provést změny" /> <a href="?" class="btn btn-default btn-lg">Zavřít</a></p>';
		}
        else
        {
            alert('V tomto projektu neexistuje žádný typ událostí, který by bylo možno smazat', 'danger');
        }
        echo '</form>';
        echo '</div>';

        echo '</div>';

        endDialogBody();

        beginDialogFooter();
        echo '<div style="text-align: left">'; // záplata na .dialogfooter { text-align: right } v Bootstrapu
        beginPanel('Definované typy');
		echo '<table class="table">';
		echo '<tr><th>Název</th><th>Min</th><th>Max</th><th>Hostů</th></tr>';
		$query = mysql_query("SELECT t.title, t.minpeople, t.capacity, t.maxguests FROM eventtypes t WHERE t.project=$projectId");
		while ($row = mysql_fetch_assoc($query))
		{
			echo '<tr><td>' . htmlspecialchars($row['title']) . "</td><td>$row[minpeople]</td><td>$row[capacity]</td><td>$row[maxguests]</td></tr>";
		}
		echo '</table>';
        endPanel();
        endDialogFooter();
        endDialog();
    }
}

function getAndCheckEventType($variablename, $projectid)
{
	$id = getVariableOrNull($variablename);
	if (!is_numeric($id)) return null;
	$query = mysql_query("SELECT t.id FROM eventtypes t WHERE t.id=$id AND t.project=$projectid");
	if (!mysql_fetch_row($query)) return null;
	return $id;
}
