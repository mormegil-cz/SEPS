<?php

function getSessionOrNull($varName)
{
	if (isset($_SESSION[$varName])) return $_SESSION[$varName];
	return null;
}

function getVariableOrNull($varName)
{
	if (isset($_GET[$varName])) return $_GET[$varName];
	if (isset($_POST[$varName])) return $_POST[$varName];
	return null;
}
