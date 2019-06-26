<?php

function logMessage($message)
{
	global $sepsDbConnection;

	mysqli_query($sepsDbConnection, "INSERT INTO logs(timestamp, entry) VALUES(NOW(), '" . mysqli_real_escape_string($sepsDbConnection, $message) . "')");
}

function display_log()
{
	global $sepsLoggedUserGlobalRights, $sepsDbConnection;

	if (!($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanViewLog)) return;
	echo '<div class="bottomform">';
	echo '<h2>Log</h2>';
	echo '<table class="logview">';
	echo '<tr><th>Čas</th><th>Zpráva</th></tr>';
	$from  = getVariableOrNull('from');
	if ($from)
		$query = mysqli_query($sepsDbConnection, "SELECT id, timestamp, entry FROM logs WHERE id <= '" . mysqli_real_escape_string($sepsDbConnection, $from) . "' ORDER BY id DESC LIMIT 20");
	else
		$query = mysqli_query($sepsDbConnection, "SELECT id, timestamp, entry FROM logs ORDER BY id DESC LIMIT 20");
	$lastid = null;
	$count = 0;
	while ($row = mysqli_fetch_assoc($query))
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
