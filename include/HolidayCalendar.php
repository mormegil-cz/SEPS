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
				$this->m_EasterBasedHolidays = array( 1, -2 );
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
		global $sepsDbConnection;

		$leapYear = (($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0));

		$easterDate = getdate(easter_date($year));
		$easter = $easterDate['yday'];

		$result = array();
		foreach($this->m_EasterBasedHolidays as $day)
		{
			$result[$easter + $day] = true;
		}
		$country = mysqli_real_escape_string($sepsDbConnection, $this->m_Country);
		$query = mysqli_query($sepsDbConnection, "SELECT day FROM holidays WHERE country='$country' AND fromyear<=$year AND toyear>=$year");
		if (!$query)
		{
			report_mysql_error();
			return $result;
		}
		while ($row = mysqli_fetch_row($query))
		{
			$day = $row[0];
			if ($leapYear)
			{
				// Ugly bug workaround: Since we represent holiday dates using year-day ordinal numbers (which was a wrong decision),
				// they are not fixed-calendar-date (e.g. 'May 1st'), but they move around on leap years.
				// So we need to adjust them on leap years (with the special-case support for #366 == February 29th).
				if ($day == 366) $day = 59;
				else if ($day >= 59) ++$day;
			}
			$result[$day] = true;
		}
		return $result;
	}
}
