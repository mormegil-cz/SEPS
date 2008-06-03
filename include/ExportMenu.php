<?php

function showExportLink($caption, $token, $type, $params = '')
{
	global $sepsLoggedUsername;
    $link = "export.php/calendar.$type?user=$sepsLoggedUsername&token=$token&req=$type&$params";
	echo '<li><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($caption) . '</a></li>';
}

function showExportMenu()
{
	global $sepsLoggedUser;
	$query = mysql_query("SELECT apitoken FROM users WHERE id=$sepsLoggedUser");
	$row = mysql_fetch_row($query);
	$token = $row[0];

	echo '<div class="exportmenu">';
	if ($token)
	{
		// zobrazit možnosti
		echo '<h3>Možnosti exportu</h3>';
		echo '<ul>';
		showExportLink('iCalendar', $token, 'ics');
		echo '</ul>';

		// nabídnout přegenerování API klíče
		echo '<h4>Přegenerování klíče</h4>';
		echo '<p>Pokud chcete, můžete zneplatit původní exportovací odkazy a nechat si vygenerovat nové:</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat nové" /></form>';
	}
	else
	{
		// nabídnout vygenerování API klíče
		echo '<h3>Vygenerování exportního klíče</h3>';
		echo '<p>Zde máte možnost si kalendář vyexportovat, abyste ho mohli použít v jiných aplikacích (např. Google Calendar). Pro používání exportu si však nejprve musíte nechat vygenerovat unikátní klíč. Pokud tedy budete chtít používat export, stiskněte následující tlačítko.</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat klíč" /></form>';
	}
	echo '</div>';
}

function generateApiToken()
{
	global $sepsLoggedUser;

	$token = generateRandomToken(14);
	mysql_query("UPDATE users SET apitoken='$token' WHERE id=$sepsLoggedUser");
}
