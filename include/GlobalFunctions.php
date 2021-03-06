<?php

function getSessionOrNull($varName)
{
	if (isset($_SESSION[$varName])) return $_SESSION[$varName];
	return null;
}

function getVariableOrNull($varName)
{
	if (isset($_POST[$varName])) return $_POST[$varName];
	if (isset($_GET[$varName])) return $_GET[$varName];
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

function validateEmail($email)
{
	return preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+$/', $email);
}

function validateEmailList($emails)
{
	return preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+(,\s*[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+)*$/', $emails);
}

function validateIcq($icq)
{
	return preg_match('/^[0-9 -]+$/', $icq);
}

function validateJabber($jabber)
{
	return preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+$/', $jabber);
}

function generateRandomToken($len)
{
	$code = '';
	for($i = 0; $i < $len; $i++)
	{
		$code .= chr(ord('A') + mt_rand(0, 25));
	}
	return $code;
}

function echoWrapped($text, $wrapColumn, $wrappedPrefix = ' ')
{
	// TODO: echoWrapped
	echo $text;
	// echo trim(chunk_split($text, $wrapColumn, "\r\n$wrappedPrefix"));
}

function escapeQuotedString($text)
{
	// TODO: escapeQuotedString
	return $text;
}

/*
// Debugging dump
function dumpStr($str)
{
	echo "<pre>\n";
	$len = strlen($str);
	$line = '';
	for ($i = 0; $i < $len; ++$i)
	{
		$ch = $str[$i];

		$code = ord($ch);

		$line .= ($code >= 32 && $code <= 126) ? htmlspecialchars($ch) : '.';
		echo dechex($code);
		echo ' ';
		if (($i % 16) == 15)
		{
			echo '    ';
			echo $line;
			echo "\n";
			$line = '';
		}
	}
	if ($line)
	{
		// TODO: Padding
		echo '    ';
		echo $line;
		echo "\n";
	}
	echo "</pre>\n";
}
*/

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
	  $string = str_replace("\r\n", "?=\r\n =?".$encoding."?Q?", $string);

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

if (function_exists('mb_strlen()'))
{
	function strlen_utf8($string)
	{
		return mb_strlen($string, 'utf-8');
	}
}
else
{
	function strlen_utf8($string)
	{
		return preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $string, $dummy);
	}
}

if (!function_exists('mb_strtolower'))
{
	function mb_strtolower($string)
	{
		return strtolower($string);
	}
}
