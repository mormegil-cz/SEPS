<?php

function report_mysql_error()
{
	echo '<div class="errmsg">' . htmlspecialchars(mysql_error()) . '</div>';
}

function initDatabase()
{
	global $sepsDbServer, $sepsDbUser, $sepsDbPassword, $sepsDbDatabase;

	$db = mysql_connect($sepsDbServer, $sepsDbUser, $sepsDbPassword);
	if (!$db)
	{
		report_mysql_error();
		die();
	}
	$seldb = mysql_select_db($sepsDbDatabase);
	if (!$seldb)
	{
		report_mysql_error();
		die();
	}
	mysql_set_charset('utf8');
}
