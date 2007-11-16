<?php

class NewsItem
{
	var $m_Text;
	var $m_Date;

	function __construct($text, $date)
	{
		$this->m_Text = $text;
		$this->m_Date = $date;
	}

	function getText()
	{
		return $this->m_Text;
	}
}

function getNewsFromDatabase()
{
}

function printNews()
{
}
