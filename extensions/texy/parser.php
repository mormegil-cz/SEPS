<?php

require_once(__DIR__ . '/texy.compact.php');

function texyParser($text)
{
	$texy = new Texy();
	$texy->encoding = 'utf-8';
	$texy->allowed['image'] = FALSE;
	return $texy->process($text);
}

function setupTexyParser()
{
  global $sepsDescriptionParser, $sepsDescriptionParserHelp;
  $sepsDescriptionParser = 'texyParser';
  $sepsDescriptionParserHelp = '<a href="http://texy.info/cs/syntax"><img src="extensions/texy/texy-powered.png" width="80" height="15" alt="Texy!" title="Je možno používat syntaxi Texy!" /></a>';
}
