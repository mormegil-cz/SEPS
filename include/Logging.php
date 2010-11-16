<?php

function logMessage($message)
{
	mysql_query("INSERT INTO logs(timestamp, entry) VALUES(NOW(), '" . mysql_real_escape_string($message) . "')");
}

function display_log()
{
	global $sepsLoggedUserGlobalRights;
	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanViewLog)) return;
	echo '<div class="bottomform">';
	echo '<h2>Log</h2>';
	echo '<table class="logview">';
	echo '<tr><th>Čas</th><th>Zpráva</th></tr>';
	$from  = getVariableOrNull('from');
	if ($from)
		$query = mysql_query("SELECT id, timestamp, entry FROM logs WHERE id <= '" . mysql_real_escape_string($from) . "' ORDER BY id DESC LIMIT 20");
	else
		$query = mysql_query("SELECT id, timestamp, entry FROM logs ORDER BY id DESC LIMIT 20");
	$lastid = null;
	$count = 0;
	while ($row = mysql_fetch_assoc($query))
	{
		echo '<tr><td>' . htmlspecialchars($row['timestamp']) . '</td><td>' . htmlspecialchars($row['entry']) . '</td></tr>';
		$lastid = $row['id'];
		$count++;
	}
	echo '</table>';
	if ($count == 20)
	{
		echo '<div><a href="?action=viewlog&amp;from=' . rawurlencode($lastid) . '">starší</a></div>';
	}
	echo '</div>';
}
