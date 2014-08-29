<?php

require_once('./include/HolidayCalendar.php');

class Subscriber
{
	var $m_UserID;
	var $m_UserCaption;
	var $m_UserEmail;
	var $m_UserIcq;
	var $m_UserSkype;
	var $m_UserJabber;
	var $m_Priority;
	var $m_Guests;
	var $m_Comment;

	function __construct($userID, $userCaption, $userEmail, $userIcq, $userSkype, $userJabber, $priority, $guests, $comment)
	{
		$this->m_UserID = $userID;
		$this->m_UserCaption = $userCaption;
		$this->m_UserEmail = $userEmail;
		$this->m_UserIcq = $userIcq;
		$this->m_UserSkype = $userSkype;
		$this->m_UserJabber = $userJabber;
		$this->m_Priority = $priority;
		$this->m_Guests = $guests;
		$this->m_Comment = $comment;
	}

	function getUserLine($withContacts)
	{
		global $sepsLoggedUser, $sepsShowEmail, $sepsShowIcqStatus, $sepsShowSkypeStatus, $sepsShowJabberStatus;

		$result = htmlspecialchars($this->m_UserCaption);
		if ($this->m_UserID == $sepsLoggedUser)
		{
		}
		else
		{
			if ($this->m_Guests)
			{
				$result .= ' + <span class="guests">' . $this->m_Guests . ' ' . plural($this->m_Guests, 'host', 'hosté', 'hostů') . "</span>";
			}
			if ($withContacts)
			{
				if ($sepsShowEmail && $this->m_UserEmail)
				{
					$mail = htmlspecialchars($this->m_UserEmail);
					$result .= " <a class='usermail' href='mailto:$mail'><img src='img/mail.png' width='20' height='15' alt='Poslat e-mail na $mail' /></a>";
				}
				if ($sepsShowJabberStatus && $this->m_UserJabber)
				{
					$jabber = htmlspecialchars($this->m_UserJabber);
					$result .= " <a class='userjabber' href='xmpp:$jabber'><img src='http://netlab.cz/status/?jid=$jabber&amp;ib=bulb' width='16' height='16' alt='Jabber: $jabber' title='$jabber' /></a>";
				}
				if ($sepsShowIcqStatus && $this->m_UserIcq)
				{
					$icq = preg_replace('/[^0-9]/', '', $this->m_UserIcq);
					$alt = htmlspecialchars($this->m_UserIcq);
					$result .= " <span class='usericq'><a href='http://www.icq.com/people/about_me.php?uin=$icq'><img src='http://wwp.icq.com/scripts/online.dll?icq=$icq&amp;img=5' width='18' height='18' alt='ICQ: $alt' title='$alt' /></a></span>";
				}
				if ($sepsShowSkypeStatus && $this->m_UserSkype)
				{
					$skype = htmlspecialchars($this->m_UserSkype);
					$result .= " <a class='userskype' href='skype:$skype'><img src='http://mystatus.skype.com/smallicon/$skype' width='16' height='16' alt='Skype: $skype' title='$skype' /></a>";
				}
			}
		}
		return $result;
	}

	function getUserID()
	{
		return $this->m_UserID;
	}

	function getCaption()
	{
		return $this->m_UserCaption;
	}

	function getEmail()
	{
		return $this->m_UserEmail;
	}

	function getUserUri()
	{
		if ($this->m_UserEmail)
		{
			return 'MAILTO:' . $this->m_UserEmail;
		}
		else if ($this->m_UserJabber)
		{
			return 'XMPP:' . $this->m_UserJabber;
		}
		else if ($this->m_UserSkype)
		{
			return 'SKYPE:' . $this->m_UserSkype;
		}
		else if ($this->m_UserIcq)
		{
			return 'http://www.icq.com/people/about_me.php?uin=' . preg_replace('/[^0-9]/', '', $this->m_UserIcq);
		}
		else
		{
			return 'seps-uid:' . $this->m_UserID;
		}
	}

	function getPriority()
	{
		return $this->m_Priority;
	}

	function getGuests()
	{
		return $this->m_Guests;
	}
}

class Event
{
	var $m_ID;
	var $m_Title;
	var $m_Date;
	var $m_SubscriberCount;
	var $m_MinSubscribers;
	var $m_Capacity;
	var $m_MaxGuests;
	var $m_Description;
	var $m_DescriptionHtml;

	function __construct($id, $title, $date, $subscriberCount, $minSubscribers, $capacity, $maxGuests)
	{
		$this->m_ID = $id;
		$this->m_Title = $title;
		$this->m_Date = $date;
		$this->m_SubscriberCount = $subscriberCount;
		$this->m_MinSubscribers = $minSubscribers;
		$this->m_Capacity = $capacity;
		$this->m_MaxGuests = $maxGuests;
		$this->m_Description = null;
		$this->m_DescriptionHtml = null;
	}

	public static function Load($id)
	{
		if (!is_numeric($id)) return null;
		$query = mysql_query("SELECT e.title, e.date, t.minpeople, t.capacity, t.maxguests FROM events e INNER JOIN eventtypes t ON e.eventtype=t.id WHERE e.id=$id");
		if (!$query) return null;
		$row = mysql_fetch_assoc($query);
		if (!$row) return null;

		$eventTitle = $row['title'];
		$eventDate = strtotime($row['date']);
		$eventMinSubscribers = $row['minpeople'];
		$eventCapacity = $row['capacity'];
		$maxGuests = $row['maxguests'];

		$query = mysql_query("SELECT COUNT(*), SUM(guests) FROM subscriptions WHERE event=$id");
		$subscriptionCountRow = mysql_fetch_row($query);
		$eventSubscriberCount = $subscriptionCountRow[0] + $subscriptionCountRow[1];

		return new Event($id, $eventTitle, $eventDate, $eventSubscriberCount, $eventMinSubscribers, $eventCapacity, $maxGuests);
	}

	function getId()
	{
		return $this->m_ID;
	}

	function getUniqueId()
	{
		return $this->m_ID;
	}

	function getTitle()
	{
		return $this->m_Title;
	}

	function getDate()
	{
		return $this->m_Date;
	}

	function getSubscriberCount()
	{
		return $this->m_SubscriberCount;
	}

	function getCapacity()
	{
		return $this->m_Capacity;
	}

	function getMaxGuests()
	{
		return $this->m_MaxGuests;
	}

	function isFilled()
	{
		return $this->m_SubscriberCount >= $this->m_MinSubscribers;
	}

	function isFull()
	{
		return $this->m_SubscriberCount >= $this->m_Capacity;
	}

	function isOverfilled()
	{
		return $this->m_SubscriberCount > $this->m_Capacity;
	}

	function getCssClass()
	{
		if ($this->isOverfilled()) return 'event-overfilled';
		elseif ($this->isFull()) return 'event-full';
		elseif ($this->isFilled()) return 'event-filled';
		else return 'event-empty';
	}

	function getListOfSubscribers()
	{
		$eid = $this->m_ID;
		$query = mysql_query(
			"SELECT u.id, u.caption, u.email, u.icq, u.skype, u.jabber, (up.priority+s.priority) AS priority, s.guests, s.comment
			FROM subscriptions s
			INNER JOIN users u ON s.user=u.id
			INNER JOIN events e ON s.event=e.id
			INNER JOIN eventtypes t ON e.eventtype=t.id
			INNER JOIN usersprojects up ON up.user=u.id AND up.project=t.project
			WHERE s.event=$eid
			ORDER BY up.priority+s.priority DESC, s.timestamp ASC");
		if (!$query) return null;
		$result = array();
		while ($row = mysql_fetch_assoc($query))
		{
			$result[] = new Subscriber($row['id'], $row['caption'], $row['email'], $row['icq'], $row['skype'], $row['jabber'], $row['priority'], $row['guests'], $row['comment']);
		}
		return $result;
	}

	function getListOfRejects()
	{
		$eid = $this->m_ID;
		$query = mysql_query(
			"SELECT u.id, u.caption, u.email, u.icq, u.skype, u.jabber, r.comment
			FROM rejections r
			INNER JOIN users u ON r.user=u.id
			WHERE r.event=$eid
			ORDER BY r.timestamp ASC");
		if (!$query) return null;
		$result = array();
		while ($row = mysql_fetch_assoc($query))
		{
			$result[] = new Subscriber($row['id'], $row['caption'], $row['email'], $row['icq'], $row['skype'], $row['jabber'], 0, 0, $row['comment']);
		}
		return $result;
	}
	
	function getUserAccessAndPriority($userid)
	{
		$eventid = $this->m_ID;
		$query = mysql_query(
			"SELECT access, priority
			FROM usersprojects up
			INNER JOIN eventtypes t ON t.project=up.project
			INNER JOIN events e ON e.eventtype=t.id
			WHERE e.id=$eventid AND up.user=$userid");
		$row = mysql_fetch_assoc($query);
		if (!$row) return array(0, 0);
		return array($row['access'], $row['priority']);
	}

	function getUserAccess($userid)
	{
		$accessAndPriority = $this->getUserAccessAndPriority($userid);
		return $accessAndPriority[0];
	}

	function getDescription()
	{
		if ($this->m_Description == null)
		{
			$id = $this->m_ID;
			$query = mysql_query("SELECT description FROM events WHERE id=$id");
			if (!$query) return null;
			$result = mysql_fetch_array($query);
			if (!$result) return null;
			$this->m_Description = $result[0];
		}
		return $this->m_Description;
	}

	function getDescriptionHtml()
	{
		if ($this->m_DescriptionHtml == null)
		{
			$id = $this->m_ID;
			$query = mysql_query("SELECT descriptionhtml FROM events WHERE id=$id");
			if (!$query) return null;
			$result = mysql_fetch_array($query);
			if (!$result) return null;
			$this->m_DescriptionHtml = $result[0];
		}
		return $this->m_DescriptionHtml;
	}
}

function findEvents($date)
{
	global $sepsLoggedUser;

	$query = mysql_query(
		"SELECT e.id, e.title, e.date, t.minpeople, t.capacity, t.maxguests, up.access,
				(SELECT COUNT(*) + SUM(guests) FROM subscriptions s WHERE s.event=e.id) AS subscribercount
		FROM events e
		INNER JOIN eventtypes t ON e.eventtype=t.id
		INNER JOIN projects p ON t.project=p.id
		INNER JOIN usersprojects up ON up.project=p.id
		WHERE up.user=$sepsLoggedUser AND e.date=" . strftime('%Y%m%d', $date));
	if (!$query) return null;

	$result = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$result[] = new Event($row['id'], $row['title'], $row['date'], intval($row['subscribercount']), $row['minpeople'], $row['capacity'], $row['maxguests']);
	}
	return $result;
}

function getEventList($fromdate, $limit)
{
	global $sepsLoggedUser;

	$query = mysql_query(
		"SELECT e.id, e.title, e.date, t.minpeople, t.capacity, t.maxguests, up.access,
				(SELECT COUNT(*) + SUM(guests) FROM subscriptions s WHERE s.event=e.id) AS subscribercount
		FROM events e
		INNER JOIN eventtypes t ON e.eventtype=t.id
		INNER JOIN projects p ON t.project=p.id
		INNER JOIN usersprojects up ON up.project=p.id
		WHERE up.user=$sepsLoggedUser AND e.date>=" . strftime('%Y%m%d', $fromdate) . " ORDER BY e.date LIMIT $limit");
	if (!$query) return null;

	$result = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$result[] = new Event($row['id'], $row['title'], $row['date'], intval($row['subscribercount']), $row['minpeople'], $row['capacity'], $row['maxguests']);
	}
	return $result;
}

function getMyEventList($fromdate, $limit)
{
	global $sepsLoggedUser;

	$query = mysql_query(
		"SELECT e.id, e.title, e.date, t.minpeople, t.capacity, t.maxguests,
				(SELECT COUNT(*) + SUM(guests) FROM subscriptions sc WHERE sc.event=e.id) AS subscribercount
		FROM events e
		INNER JOIN eventtypes t ON e.eventtype=t.id
		INNER JOIN subscriptions s ON s.event=e.id AND s.user=$sepsLoggedUser
		WHERE e.date>=" . strftime('%Y%m%d', $fromdate) . " ORDER BY e.date LIMIT $limit");
	if (!$query) return null;

	$result = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$result[] = new Event($row['id'], $row['title'], $row['date'], intval($row['subscribercount']), $row['minpeople'], $row['capacity'], $row['maxguests']);
	}
	return $result;
}

function printEventsCalendar($showSelectedDate)
{
	global $sepsCalendarWeeks, $sepsLoggedUserMaxAccess, $sepsCountry;

	echo '<div class="calendar"><table class="calendar"><caption>Kalendář plánovaných akcí</caption>';
	echo '<tr><th>Po</th><th>Út</th><th>St</th><th>Čt</th><th>Pá</th><th>So</th><th>Ne</th></tr>';

	$today = mktime();
	$todayStr = strftime('%d.&nbsp;%m.', $today);
	$startDateArg = getVariableOrNull('date');
	$startDate = $startDateArg ? $startDateArg : mktime();
	$startDate = 86400 * floor($startDate / 86400);
	$selectedStr = strftime('%d.&nbsp;%m.', $startDate);
	$startDateArray = getdate($startDate);
	$startMonday = 86400 * floor($startDate / 86400 - (7 + $startDateArray['wday'] - 1) % 7);

	$holidays = new HolidayCalendar($sepsCountry);
	
	$date = $startMonday;
	for ($week = 0; $week < $sepsCalendarWeeks; $week++)
	{
		echo '<tr>';
		for ($weekDay = 0; $weekDay < 7; $weekDay++, $date += 86400)
		{
			$events = findEvents($date);

			$dateStr = strftime('%d.&nbsp;%m.', $date);
			$dayClass = $holidays->isHoliday($date, $weekDay) ? 'holiday' : 'day';
			$dayClass .= $date < $today ? ' past' : ' future';
			if ($dateStr == $todayStr) $dayClass .= ' today';
			if ($showSelectedDate && $dateStr == $selectedStr) $dayClass .= ' selected';
			echo "<td class='$dayClass'>";
			if ($date >= $today && ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanCreateEvents))
			{
				echo "<div class='addevent'><a href='?action=newevent&amp;date=$date' title='Přidat novou událost'>+</a></div>";
			}
			echo "<span class='date'>$dateStr</span>";

			foreach(findEvents($date) as $event)
			{
				$cssClass = $event->getCssClass();
				$eid = $event->getId();
				$eventTitle = htmlspecialchars($event->getTitle());
				$subscriberCount = $event->getSubscriberCount();
				echo "<div class='$cssClass'><a class='event-detail' href='?eid=$eid&amp;date=$date'>$eventTitle</a> ($subscriberCount)</div>";
			}

			echo '</td>';
		}
		echo '</tr>';
	}

	echo '</table>';
	$prevdate = strftime(floor($startDate/86400 - 7) * 86400);
	$nextdate = strftime(floor($startDate/86400 + 7) * 86400);
	echo "<div class='linkprev'><a href='?date=$prevdate'>&uarr; Předchozí</a></div><div class='linknext'><a href='?date=$nextdate'>Následující &darr;</a></div>";
	echo '<br class="cleaner" />';
	echo '</div>';
}

function printEventDetails($eid)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	$accessAndPriority = $event->getUserAccessAndPriority($sepsLoggedUser);

	$access = $accessAndPriority[0];
	$userPriority = $accessAndPriority[1];
	$eventdate = $event->getDate();
	if (!($access & sepsAccessFlagsCanSee)) return;

	$isSubscribed = false;

	echo '<div class="eventdetail">';
	echo '<h2>' . htmlspecialchars($event->getTitle()) . ' ' . strftime('%d.&nbsp;%m.&nbsp;%Y', $eventdate) . '</h2>';

	if (getVariableOrNull('action') == 'editdescription' && $access & sepsAccessFlagsCanEditEventDescription)
	{
		global $sepsDescriptionParserHelp;
		$description = $event->getDescription();
		echo '<div class="eventdescription">';
		echo '<form action="?" method="POST"><input type="hidden" name="action" value="savedescription" />';
		generateCsrfToken();
		echo "<input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' />";
		echo '<textarea rows="5" cols="25" name="description">';
		echo htmlspecialchars($description);
		echo '</textarea>';
		if ($sepsDescriptionParserHelp)
		{
			echo "<div class='parserhelpinfo'>$sepsDescriptionParserHelp</div>";
		}
		echo '<input type="submit" value="Uložit" />';
		echo '</form>';
		echo '</div>';
	}
	else
	{
		$description = $event->getDescriptionHtml();
		$descriptionEditLink = ($access & sepsAccessFlagsCanEditEventDescription) ? "<div class='editlink'><a href='?action=editdescription&amp;eid=$eid&amp;date=$eventdate' title='Upravit popis události'>[E]</a></div>" : '';
		if ($description)
		{
			echo '<div class="eventdescription">';
			echo $descriptionEditLink;
			echo $description;
			echo '</div>';
		}
		else
		{
			echo $descriptionEditLink;
		}
	}
	$eventCapacity = $event->getCapacity();
	$eventSubscriberCount = $event->getSubscriberCount();
	$eventSubscriberWithPriorityCount = 0;
	if ($eventSubscriberCount > 0)
	{
		$maxguests = $event->getMaxGuests();

		$subscriberIdx = 0;
		$subscribedWithGuests = 0;
		echo '<ul class="subscribers">';
		foreach($event->getListOfSubscribers() as $subscriber)
		{
			$priority = $subscriber->getPriority();
			$guests = $subscriber->getGuests();
			$subscribedWithGuests += 1 + $guests;
			$subscriberIdx++;
			$subscriberClass = $subscribedWithGuests > $eventCapacity ? "subscriber-over" : "subscriber-ok";
			$thisIsCurrentUser = false;
			if (!$isSubscribed && $subscriber->getUserID() == $sepsLoggedUser)
			{
				$thisIsCurrentUser = true;
				$isSubscribed = true;
			}
			elseif ($priority >= $userPriority) $eventSubscriberWithPriorityCount++;
			echo "<li class='priority-$priority $subscriberClass'>";

			if ($thisIsCurrentUser && $maxguests)
			{
				echo "<form action='?' method='post'><input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' /><input type='hidden' name='action' value='changeguests' />";
				generateCsrfToken();
			}

			echo $subscriber->getUserLine($access & sepsAccessFlagsCanSeeContacts);

			if ($thisIsCurrentUser && $maxguests)
			{
				echo " +<input class='guests' name='guestcount' id='guestcount' value='$guests' onchange='javascript:this.form.submit()' /> " . plural($guests, 'host', 'hosté', 'hostů');
				echo " <input type='submit' value='Uložit' id='guestcountsubmit' />";
				echo '<script type="text/javascript">document.getElementById("guestcountsubmit").style.display = "none";</script></form>';
			}

			echo '</li>';
		}
		echo '</ul>';
	}
	else
	{
		echo '<p class="nosubscribers">Na tuto událost se dosud nikdo nepřihlásil.</p>';
	}

	$hasRejected = false;
	$rejects = $event->getListOfRejects();
	if (count($rejects))
	{
		echo '<div class="rejecters"><p class="rejecterscaption">Tito uživatelé se akce nezúčastní:</p>';
		foreach($rejects as $subscriber)
		{
			if (!$isSubscribed && !$hasRejected && $subscriber->getUserID() == $sepsLoggedUser)
			{
				$thisIsCurrentUser = true;
				$hasRejected = true;
			}

			echo '<li class="rejecter">';
			echo $subscriber->getUserLine($access & sepsAccessFlagsCanSeeContacts);
			echo '</li>';
		}
		echo '</div>';
	}
	
	if ($access & sepsAccessFlagsHasAccess)
	{
		echo "<form action='?' method='post'><input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' />";
		generateCsrfToken();
		if ($isSubscribed)
			echo '<button name="action" value="unsubscribe">Odvolat účast</button>';
		else if ($hasRejected)
			echo '<button name="action" value="unsubscribe">Odvolat neúčast</button>';
		else
		{
			echo '<button name="action" value="subscribe" ';
			if ($eventSubscriberWithPriorityCount >= $eventCapacity) echo 'onclick="return confirm(\'Událost je již zaplněna. Chcete se přesto přihlásit?\')" ';
			elseif ($eventSubscriberCount >= $eventCapacity) echo 'onclick="return confirm(\'Svým přihlášením vyřadíte uživatele s nižší prioritou. Chcete se přesto přihlásit?\')" ';
			echo '/>Zúčastním se</button><button name="action" value="rejectevent">Nezúčastním se</button>';
		}
		echo '</form>';
	}
	if ($access & sepsAccessFlagsCanDeleteEvents)
	{
		echo "<form action='?' method='post'><input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' />";
		generateCsrfToken();
		$text = $eventSubscriberCount > 0
					? 'Pozor! Na akci už je někdo přihlášen! Určitě chcete odhlásit všechny účastníky a zrušit tuto akci?'
					: 'Určitě chcete zrušit tuto akci?';
		echo '<input type="hidden" name="action" value="deleteevent" /><input type="submit" onclick="return confirm(\'' . $text . '\')" value="Zrušit akci" />';
		echo '</form>';
	}

	echo '</div>';
}

function insertToSubscriptionsOrRejections($eid, $tablename)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsHasAccess)) return;

	$query = mysql_query("SELECT COUNT(*) FROM events e LEFT JOIN subscriptions s ON s.event=e.id AND s.user=$sepsLoggedUser LEFT JOIN rejections r ON r.event=e.id AND r.user=$sepsLoggedUser WHERE e.id=$eid AND (s.event IS NOT NULL OR r.event IS NOT NULL)");
	$result = mysql_fetch_row($query);
	if ($result[0] == 0)
	{
		$currdate = strftime('%Y-%m-%d %H:%M:%S');
		mysql_query("INSERT INTO $tablename(user, event, timestamp) VALUES ($sepsLoggedUser, $eid, '$currdate')");
	}
}

function subscribeToEvent($eid)
{
	insertToSubscriptionsOrRejections($eid, 'subscriptions');
}

function rejectEvent($eid)
{
	insertToSubscriptionsOrRejections($eid, 'rejections');
}

function unsubscribeFromEvent($eid)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsHasAccess)) return;

	mysql_query("DELETE FROM subscriptions WHERE user=$sepsLoggedUser AND event=$eid LIMIT 1");
	mysql_query("DELETE FROM rejections WHERE user=$sepsLoggedUser AND event=$eid LIMIT 1");
}

function newEventForm($date)
{
	global $sepsLoggedUser;

	$availableTypes = array();
	$dateStr = strftime('%Y%m%d', $date);

	$query = mysql_query(
			"SELECT t.id, t.title
			FROM usersprojects up
			INNER JOIN projects p ON up.project=p.id
			INNER JOIN eventtypes t ON t.project=p.id
			WHERE up.user=$sepsLoggedUser AND (up.access & " . sepsAccessFlagsCanCreateEvents . ") != 0
			ORDER BY t.title");
	while ($row = mysql_fetch_assoc($query))
	{
		$availableTypes[$row['id']] = htmlspecialchars($row['title']);
	}

	echo '<div class="bottomform newevent">';
	if ($availableTypes)
	{
		echo "<h2>Nová událost na " . strftime('%d.&nbsp;%m.&nbsp;%Y', $date) . "</h2>";
		echo "<form action='?' method='post'><input type='hidden' name='action' value='createevent' /><input type='hidden' name='date' value='$date' />";
		generateCsrfToken();
		echo "Nadpis: <input type='text' name='title' /><br />";

		echo 'Typ události: <select name="eventtype">';
		foreach($availableTypes as $typeid => $typename)
		{
			echo "<option value='$typeid'>$typename</option>";
		}
		echo '</select><br />';
		echo '<input type="submit" value="Vytvořit událost" />';
		echo "</form>";
	}
	else
	{
		echo '<div class="errmsg">Nelze založit novou událost, jsou definovány nějaké typy?</div>';
	}
	echo '</div>';
}

function createNewEvent()
{
	global $sepsLoggedUser, $sepsLoggedUsername;

	$atDate = getVariableOrNull('date');
	$eventTitle = getVariableOrNull('title');
	$eventType = getVariableOrNull('eventtype');
	if (!$atDate || !$eventTitle || !$eventType) return;

	// TODO: check permissions
	// TODO: check time

	mysql_query(
		"INSERT INTO events (title, date, eventtype)
			VALUES ('" . mysql_real_escape_string($eventTitle) . "', '" . strftime('%Y%m%d', $atDate) . "', " . $eventType . ")");
	if (mysql_affected_rows() != 1)
	{
		report_mysql_error();
		return;
	}

	logMessage("Uživatel $sepsLoggedUsername založil na " . strftime('%Y%m%d', $atDate) . " událost '$eventTitle' typu #$eventType");
}

function deleteEvent($eid)
{
	global $sepsLoggedUser, $sepsLoggedUsername;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsCanDeleteEvents)) return;

	mysql_query('BEGIN');
	mysql_query("DELETE FROM subscriptions WHERE event=$eid");
	mysql_query("DELETE FROM rejections WHERE event=$eid");
	$query = mysql_query("DELETE FROM events WHERE events.id=$eid LIMIT 1");
	if ($query && (mysql_affected_rows() > 0))
	{
	    logMessage("Uživatel $sepsLoggedUsername smazal událost #$eid");
		mysql_query('COMMIT');
		echo '<div class="infomsg">Událost smazána</div>';
	}
	else
	{
		mysql_query('ROLLBACK');
		echo '<div class="errmsg">Nelze smazat událost</div>';
	}
}

function changeGuestCount($eid, $guestcount)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsHasAccess))
	{
		return;
	}

	$query = mysql_query("SELECT user, event FROM subscriptions WHERE user=$sepsLoggedUser AND event=$eid LIMIT 1");
	if (!mysql_fetch_row($query))
	{
		return;
	}

	$guestcount = intval($guestcount);
	$maxguests = $event->getMaxGuests();
	if ($guestcount > $maxguests) $guestcount = $maxguests;
	if ($guestcount < 0) $guestcount = 0;

	mysql_query("UPDATE subscriptions SET guests=$guestcount WHERE user=$sepsLoggedUser AND event=$eid LIMIT 1");
}

function changeDescription($eid, $description)
{
	global $sepsLoggedUser, $sepsLoggedUsername, $sepsDescriptionParser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsCanEditEventDescription)) return;

	$descriptionhtml = $sepsDescriptionParser($description);
	if ($descriptionhtml === false)
	{
		echo '<div class="errmsg">Syntaktická chyba v popisu</div>';
		return;
	}

	$query = mysql_query("UPDATE events SET description='" . mysql_real_escape_string($description) .
							"', descriptionhtml='" . mysql_real_escape_string($descriptionhtml) ."' WHERE events.id=$eid LIMIT 1");

	if ($query && (mysql_affected_rows() > 0))
	{
	    logMessage("Uživatel $sepsLoggedUsername upravil popis události #$eid");
		echo '<div class="infomsg">Upravený popis uložen</div>';
	}
	else
	{
		echo '<div class="errmsg">Nelze uložit upravený popis</div>';
	}
}
