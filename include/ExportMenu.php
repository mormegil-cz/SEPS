<?php

function showExportLink($caption, $token, $type, $params = '')
{
	global $sepsLoggedUsername, $sepsBaseUri;
    $link = "export.php/calendar.$type?user=$sepsLoggedUsername&token=$token&req=$type";
	if ($params) $link .= '&' . $params;
	echo '<li><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($caption) . '</a>';
	if ($type == 'ics')
	{
		$url = rawurlencode($sepsBaseUri . $link);
		echo " <a href='http://www.google.com/calendar/render?cid=$url'><img src='http://www.google.com/calendar/images/ext/gc_button1_cs.gif' alt='Přidat do Kalendáře Google'></a>";
	}
	echo '</li>';
}

function showExportMenu()
{
	global $sepsLoggedUser, $sepsDbConnection;

	$query = mysqli_query($sepsDbConnection, "SELECT apitoken FROM users WHERE id=$sepsLoggedUser");
	$row = mysqli_fetch_row($query);
	$token = $row[0];

	echo '<div class="exportmenu">';
	if ($token)
	{
		// zobrazit možnosti
		echo '<h3>Možnosti exportu</h3>';
		echo '<ul>';
		showExportLink('iCalendar (všechny události)', $token, 'ics');
		showExportLink('iCalendar (jen moje)', $token, 'ics', 'mine=1');
		echo '</ul>';

		// nabídnout přegenerování API klíče
		echo '<h4>Přegenerování klíče</h4>';
		echo '<p>Pokud chcete, můžete zneplatit původní exportovací odkazy a nechat si vygenerovat nové:</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat nové" />';
		generateCsrfToken();
		echo '</form>';
	}
	else
	{
		// nabídnout vygenerování API klíče
		echo '<h3>Vygenerování exportního klíče</h3>';
		echo '<p>Zde máte možnost si kalendář vyexportovat, abyste ho mohli použít v jiných aplikacích (např. Google Calendar). Pro používání exportu si však nejprve musíte nechat vygenerovat unikátní klíč. Pokud tedy budete chtít používat export, stiskněte následující tlačítko.</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat klíč" />';
		generateCsrfToken();
		echo '</form>';
	}
	echo '</div>';
}

function generateApiToken()
{
	global $sepsLoggedUser, $sepsDbConnection;

	$token = generateRandomToken(14);
	mysqli_query($sepsDbConnection, "UPDATE users SET apitoken='$token' WHERE id=$sepsLoggedUser");
}
