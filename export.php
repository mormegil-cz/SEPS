<?php

require_once('./include/Constants.php');
require_once('./include/GlobalFunctions.php');

$request = getVariableOrNull('req');
if (!$request)
{
	header('HTTP/1.1 400 Bad Request');
	echo 'Request type missing';
	return;
}

$username = getVariableOrNull('user');
$token = getVariableOrNull('token');
if (!$username || !$token)
{
	header('HTTP/1.1 403 Forbidden');
	echo 'Authentication information missing';
	return;
}

require_once('./include/DefaultSettings.php');
require_once('./LocalSettings.php');
require_once('./include/Database.php');
require_once('./include/Login.php');
initDatabase();

$sepsLoggedUser = tryApiLogin($username, $token);
if (!$sepsLoggedUser)
{
	header('HTTP/1.1 403 Forbidden');
	echo 'Wrong authentication';
	return;
}

switch($request)
{
	case 'ics':
		getICalendar();
		break;
	default:
		header('HTTP/1.1 404 Not Found');
		echo 'Unsupported request type';
		return;
}

function getICalendar()
{
	global $sepsBaseUniqueAddress, $sepsSoftwareVersionFpi;
	require_once('./include/Events.php');

	header('Content-Type: text/calendar; charset=utf-8');
	header('Content-disposition: attachment; filename=calendar.ics');

	echo "BEGIN:VCALENDAR\r\n";
	echo "PRODID:$sepsSoftwareVersionFpi\r\n";
	echo "VERSION:2.0\r\n";
	echoWrapped("X-WR-CALNAME:$sepsTitle\r\n", 75);
	echoWrapped("X-WR-CALDESC:$sepsTitle\r\n", 75);

	require_once('./include/Events.php');

	$startDate = mktime()-86400;
	$limit = 500;

	foreach(getEventList($startDate, $limit) as $event)
	{
		$eventId = $event->getUniqueId();
		$eventDate = strtotime($event->getDate());
		$eventStart = strftime('%Y%m%dT170000', $eventDate);
		$eventEnd = strftime('%Y%m%dT180000', $eventDate);
		$eventTitle = $event->getTitle();
		$eventDesc = trim(strtr(strip_tags($event->getDescriptionHtml()), "\r\n", '  '));

		echo "BEGIN:VEVENT\r\n";
		echo "DTSTART:$eventStart\r\n";
		echo "DTEND:$eventEnd\r\n";
		echoWrapped("UID:eid=$eventId@$sepsBaseUniqueAddress\r\n", 75);
		echoWrapped("SUMMARY:$eventTitle\r\n", 75);
		if ($eventDesc) echoWrapped("DESCRIPTION:$eventDesc\r\n", 75);
		echo "TRANSP:OPAQUE\r\n";

		foreach($event->getListOfSubscribers() as $subscriber)
		{
			$guests = $subscriber->getGuests();
			$subscriberName = $subscriber->getCaption();
			$subscriberUri = $subscriber->getUserUri();
			echo "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=OPT-PARTICIPANT;PARTSTAT=ACCEPTED;X-NUM-GUESTS=$guests;CN=$subscriberName:$subscriberUri\r\n";
		}

		// CATEGORIES
		// DESCRIPTION
		// SUMMARY
		// DTSTART
		// TRANSP:OPAQUE ??
		// ORGANIZER ?
		// ATTENDEE

		echo "END:VEVENT\r\n";
	}

	echo "END:VCALENDAR\r\n";
}