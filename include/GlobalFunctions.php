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

function plural($value, $singular, $plural234, $plural)
{
	switch($value)
	{
		case 1:
			return $singular;
		case 2:
		case 3:
		case 4:
			return $plural234;
		default:
			return $plural;
	}
}

if (function_exists('imap_8bit'))
{
	function encodeMailHeader($string, $encoding='UTF-8')
	{
	  $string = str_replace(" ", "_", trim($string));
	  // We need to delete "=\r\n" produced by imap_8bit() and replace '?'
	  $string = str_replace("?", "=3F", str_replace("=\r\n", "", imap_8bit($string)));
	  // Now we split by \r\n - i'm not sure about how many chars (header name counts or not?)
	  $string = chunk_split($string, 73);
	  // We also have to remove last unneeded \r\n :
	  $string = substr($string, 0, strlen($string)-2);
	  // replace newlines with encoding text "=?UTF ..."
	  $string = str_replace("\r\n", "?=".HEAD_CRLF." =?".$encoding."?Q?", $string);

	  return '=?'.$encoding.'?Q?'.$string.'?=';
	}
}
else
{
	function encodeMailHeader($string, $encoding='UTF-8')
	{
	  return "=?$encoding?B?" . base64_encode($string) . '?=';
	}
}
