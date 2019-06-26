<?php

function report_mysql_error()
{
	global $sepsDbConnection;
	echo '<div class="errmsg">' . htmlspecialchars(mysqli_error($sepsDbConnection)) . '</div>';
}

function initDatabase()
{
	global $sepsDbServer, $sepsDbUser, $sepsDbPassword, $sepsDbDatabase, $sepsDbConnection;

	$sepsDbConnection = mysqli_connect($sepsDbServer,  $sepsDbUser,  $sepsDbPassword);
	if (!$sepsDbConnection)
	{
		report_mysql_error();
		die();
	}
	$seldb = mysqli_select_db($sepsDbConnection, $sepsDbDatabase);
	if (!$seldb)
	{
		report_mysql_error();
		die();
	}
	((bool)mysqli_set_charset($sepsDbConnection, "utf8"));
}
