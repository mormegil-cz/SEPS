<?php

function logMessage($message)
{
	mysql_query("INSERT INTO logs(timestamp, entry) VALUES(NOW(), '" . mysql_real_escape_string($message) . "')");
}

function display_log()
{
	echo '<h2>Protokolovací záznamy</h2>';
	echo '<table class="logview">';
	echo '<tr><th>Čas</th><th>Zpráva</th></tr>';
	$query = mysql_query("SELECT timestamp, entry FROM logs ORDER BY timestamp DESC LIMIT 20");
	while ($row = mysql_fetch_assoc($query))
	{
		echo '<tr><td>' . $row['timestamp'] . '</td><td>' . htmlspecialchars($row['entry']) . '</td></tr>';
	}
	echo '</table>';
}
