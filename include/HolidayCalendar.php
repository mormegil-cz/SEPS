<?php

class HolidayCalendar
{
	var $m_Country;
	var $m_WeekendDays;
	var $m_EasterBasedHolidays;
	var $m_CachedYear;
	var $m_Cache;

	function __construct($country)
	{
		$this->m_Country = $country;
		switch ($this->m_Country)
		{
			case 'CZ':
				$this->m_WeekendDays = array( 5 => true, 6 => true );
				$this->m_EasterBasedHolidays = array( 1 );
				break;
		}
	}

	function isHoliday($date, $weekday)
	{
		if (array_key_exists($weekday, $this->m_WeekendDays)) return true;

		$dateParts = getdate($date);
		$holidays = $this->getHolidays($dateParts['year']);
		return array_key_exists($dateParts['yday'], $holidays);
	}

	function getHolidays($year)
	{
		if ($this->m_CachedYear == $year) return $this->m_Cache;

		$this->m_Cache = $this->computeHolidays(intval($year));
		$this->m_CachedYear = $year;

		return $this->m_Cache;
	}
	
	function computeHolidays($year)
	{
		$easterDate = getdate(easter_date($year));
		$easter = $easterDate['yday'];

		$result = array();
		foreach($this->m_EasterBasedHolidays as $day)
		{
			$result[$easter + $day] = true;
		}
		$country = mysql_real_escape_string($this->m_Country);
		$query = mysql_query("SELECT day FROM holidays WHERE country='$country' AND fromyear<=$year AND toyear>=$year");
		if (!$query)
		{
			report_mysql_error();
			return $result;
		}
		while ($row = mysql_fetch_row($query))
		{
			$result[$row[0]] = true;
		}
		return $result;
	}
}
