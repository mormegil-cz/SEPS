<?php

function parsedownParser($text)
{
  require_once(__DIR__ . '/Parsedown.php');
  $Parsedown = new Parsedown();
  return $Parsedown->text($text);
}

function setupParsedownParser()
{
  global $sepsDescriptionParser, $sepsDescriptionParserHelp;
  $sepsDescriptionParser = 'parsedownParser';
  $sepsDescriptionParserHelp = '<a href="https://help.github.com/articles/basic-writing-and-formatting-syntax/"><img src="extensions/Parsedown/markdown.png" width="40" height="25" alt="Markdown" title="Je možno používat syntaxi Markdown" /></a>';
}
