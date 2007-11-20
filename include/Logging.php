<?php

function logMessage($message)
{
	mysql_query("INSERT INTO logs(timestamp, entry) VALUES(NOW(), '" . mysql_real_escape_string($message) . "')");
}
