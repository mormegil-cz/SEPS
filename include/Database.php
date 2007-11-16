<?php

function initDatabase()
{
	global $sepsDbServer, $sepsDbUser, $sepsDbPassword, $sepsDbDatabase;

	$db = mysql_connect($sepsDbServer,$sepsDbUser,$sepsDbPassword);
	$seldb=mysql_select_db($sepsDbDatabase);
	if( !$db )
	{
		$chyba = mysql_errno().": ".mysql_error();
		echo( "<font align='center' color='red'><b>$chyba</b></font><br>" );
	}
	mysql_query("SET NAMES 'utf8'");
}
