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
	global $sepsLoggedUser;
	$query = mysql_query("SELECT apitoken FROM users WHERE id=$sepsLoggedUser");
	$row = mysql_fetch_row($query);
	$token = $row[0];

    beginDialog('Export kalendáře');
    beginDialogBody();
	if ($token)
	{
		// zobrazit možnosti
        beginPanel('Možnosti exportu');
        beginPanelBody();
		echo '<ul>';
		showExportLink('iCalendar (všechny události)', $token, 'ics');
		showExportLink('iCalendar (jen moje)', $token, 'ics', 'mine=1');
		echo '</ul>';
        endPanelBody();
        endPanel();

		// nabídnout přegenerování API klíče
        beginPanel('Přegenerování klíče');
        beginPanelBody();
		echo '<p>Pokud chcete, můžete zneplatit původní exportovací odkazy a nechat si vygenerovat nové:</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat nové" class="btn btn-default" />';
		generateCsrfToken();
		echo '</form>';
        endPanelBody();
        endPanel();
	}
	else
	{
		// nabídnout vygenerování API klíče
        beginPanel('Vygenerování exportního klíče');
        beginPanelBody();
		echo '<p>Zde máte možnost si kalendář vyexportovat, abyste ho mohli použít v jiných aplikacích (např. Google Calendar). Pro používání exportu si však nejprve musíte nechat vygenerovat unikátní klíč. Pokud tedy budete chtít používat export, stiskněte následující tlačítko.</p>';
		echo '<form action="?" method="post"><input type="hidden" name="action" value="genapitoken" /><input type="submit" value="Vygenerovat klíč" class="btn btn-primary" />';
		generateCsrfToken();
		echo '</form>';
        endPanelBody();
        endPanel();
	}
    endDialogBody();
    beginDialogFooter();
    echo '<a href="?" class="btn btn-default">Zavřít</a>';
    endDialogFooter();
	endDialog();
}

function generateApiToken()
{
	global $sepsLoggedUser;

	$token = generateRandomToken(14);
	mysql_query("UPDATE users SET apitoken='$token' WHERE id=$sepsLoggedUser");
}
