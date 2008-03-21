<?php

class Subscriber
{
	var $m_UserID;
	var $m_UserCaption;
	var $m_UserEmail;
	var $m_UserIcq;
	var $m_Priority;

	function __construct($userID, $userCaption, $userEmail, $userIcq, $priority)
	{
		$this->m_UserID = $userID;
		$this->m_UserCaption = $userCaption;
		$this->m_UserEmail = $userEmail;
		$this->m_UserIcq = $userIcq;
		$this->m_Priority = $priority;
	}

	function getUserLine($withContacts)
	{
		global $sepsShowIcqStatus;

		$result = htmlspecialchars($this->m_UserCaption);
		if ($withContacts)
		{
			if ($this->m_UserEmail) $result .= ' <a class="usermail" href="mailto:' . htmlspecialchars($this->m_UserEmail) . '"><img src="img/mail.png" width="20" height="15" alt="Poslat e-mail" /></a>';
			if ($sepsShowIcqStatus && $this->m_UserIcq)
			{
				$icq = ereg_replace('[^0-9]', '', $this->m_UserIcq);
				$alt = htmlspecialchars($this->m_UserIcq);
				$result .= ' <span class="usericq"><img src="http://wwp.icq.com/scripts/online.dll?icq=' . htmlspecialchars($icq) . '&amp;img=5" width="18" height="18" alt="' . $alt . '" title = "' . $alt . '" /></span>';
			}
		}
		return $result;
	}

	function getUserID()
	{
		return $this->m_UserID;
	}

	function getPriority()
	{
		return $this->m_Priority;
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

	function __construct($id, $title, $date, $subscriberCount, $minSubscribers, $capacity)
	{
		$this->m_ID = $id;
		$this->m_Title = $title;
		$this->m_Date = $date;
		$this->m_SubscriberCount = $subscriberCount;
		$this->m_MinSubscribers = $minSubscribers;
		$this->m_Capacity = $capacity;
	}

	public static function Load($id)
	{
		if (!is_numeric($id)) return null;
		$query = mysql_query("SELECT e.title, e.date, t.minpeople, t.capacity FROM events e INNER JOIN eventtypes t ON e.eventtype=t.id WHERE e.id=$id");
		if (!$query) return null;
		$row = mysql_fetch_assoc($query);
		if (!$row) return null;

		$eventTitle = $row['title'];
		$eventDate = strtotime($row['date']);
		$eventMinSubscribers = $row['minpeople'];
		$eventCapacity = $row['capacity'];

		$query = mysql_query("SELECT COUNT(*) FROM subscriptions WHERE event=$id");
		$subscriptionCountRow = mysql_fetch_row($query);
		$eventSubscriberCount = $subscriptionCountRow[0];

		return new Event($id, $eventTitle, $eventDate, $eventSubscriberCount, $eventMinSubscribers, $eventCapacity);
	}

	function getId()
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

	function getListOfSubscribers()
	{
		$eid = $this->m_ID;
		$query = mysql_query(
			"SELECT u.id, u.caption, u.email, u.icq, (up.priority+s.priority) AS priority
			FROM subscriptions s
			INNER JOIN users u ON s.user=u.id
			INNER JOIN events e ON s.event=e.id
			INNER JOIN eventtypes t ON e.eventtype=t.id
			INNER JOIN usersprojects up ON up.user=u.id AND up.project=t.project
			WHERE s.event=$eid
			ORDER BY up.priority+s.priority DESC, s.subscribed ASC");
		if (!$query) return null;
		$result = array();
		while ($row = mysql_fetch_assoc($query))
		{
			$result[] = new Subscriber($row['id'], $row['caption'], $row['email'], $row['icq'], $row['priority']);
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
}

function findEvents($date)
{
	global $sepsLoggedUser;

	$query = mysql_query(
		"SELECT e.id, e.title, e.date, t.minpeople, t.capacity, up.access,
				(SELECT COUNT(*) FROM subscriptions s WHERE s.event=e.id) AS subscribercount
		FROM events e
		INNER JOIN eventtypes t ON e.eventtype=t.id
		INNER JOIN projects p ON t.project=p.id
		INNER JOIN usersprojects up ON up.project=p.id
		WHERE up.user=$sepsLoggedUser AND e.date=" . strftime('%Y%m%d', $date));
	if (!$query) return null;

	$result = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$result[] = new Event($row['id'], $row['title'], $row['date'], $row['subscribercount'], $row['minpeople'], $row['capacity']);
	}
	return $result;
}

function printEventsCalendar($showSelectedDate)
{
	global $sepsCalendarWeeks, $sepsLoggedUserMaxAccess;

	echo '<div class="calendar"><table class="calendar"><thead><caption>Plánované akce</caption></thead><tbody>';
	echo '<tr><th>Po</th><th>Út</th><th>St</th><th>Čt</th><th>Pá</th><th>So</th><th>Ne</th></tr>';

	$today = mktime();
	$todayStr = strftime('%d.&nbsp;%m.', $today);
	$startDateArg = getVariableOrNull('date');
	$startDate = $startDateArg ? $startDateArg : mktime();
	$selectedStr = strftime('%d.&nbsp;%m.', $startDate);
	$startDateArray = getdate($startDate);
	$startMonday = $startDate - 86400 * ((7 + $startDateArray['wday'] - 1) % 7);

	$date = $startMonday;
	for ($week = 0; $week < $sepsCalendarWeeks; $week++)
	{
		echo '<tr>';
		for ($weekDay = 0; $weekDay < 7; $weekDay++, $date += 86400)
		{
			$events = findEvents($date);

			$dateStr = strftime('%d.&nbsp;%m.', $date);
			$dayClass = $weekDay >= 5 ? 'holiday' : 'day';
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
				$cssClass = '';
				if ($event->isOverfilled()) $cssClass = 'event-overfilled';
				elseif ($event->isFull()) $cssClass = 'event-full';
				elseif ($event->isFilled()) $cssClass = 'event-filled';
				else $cssClass = 'event-empty';
				$eid = $event->getId();
				$eventTitle = htmlspecialchars($event->getTitle());
				$subscriberCount = $event->getSubscriberCount();
				echo "<div class='$cssClass'><a class='event-detail' href='?eid=$eid&amp;date=$date'>$eventTitle</a> ($subscriberCount)</div>";
			}

			echo '</td>';
		}
		echo '</tr>';
	}

	echo '</tbody></table>';
	$prevdate = strftime($startDate - 7 * 86400);
	$nextdate = strftime($startDate + 7 * 86400);
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
	$eventCapacity = $event->getCapacity();
	$eventSubscriberCount = $event->getSubscriberCount();
	$eventSubscriberWithPriorityCount = 0;
	if ($eventSubscriberCount > 0)
	{
		$subscriberIdx = 0;
		echo '<ul class="subscribers">';
		foreach($event->getListOfSubscribers() as $subscriber)
		{
			$priority = $subscriber->getPriority();
			$subscriberIdx++;
			$subscriberClass = $subscriberIdx > $eventCapacity ? "subscriber-over" : "subscriber-ok";
			if (!$isSubscribed && $subscriber->getUserID() == $sepsLoggedUser) $isSubscribed = true;
			elseif ($priority >= $userPriority) $eventSubscriberWithPriorityCount++;
			echo "<li class='priority-$priority $subscriberClass'>" . $subscriber->getUserLine($access & sepsAccessFlagsCanSeeContacts) . '</li>';
		}
		echo '</ul>';
	}
	else
	{
		echo '<p class="nosubscribers">Na tuto událost se dosud nikdo nepřihlásil.</p>';
	}

	if ($access & sepsAccessFlagsHasAccess)
	{
		echo "<form action='?' method='post'><input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' />";
		if ($isSubscribed)
			echo '<input type="hidden" name="action" value="unsubscribe" /><input type="submit" value="Odhlásit se" />';
		else
		{
			echo '<input type="hidden" name="action" value="subscribe" /><input type="submit" value="Přihlásit se" ';
			if ($eventSubscriberWithPriorityCount >= $eventCapacity) echo 'onclick="return confirm(\'Událost je již zaplněna. Chcete se přesto přihlásit?\')" ';
			elseif ($eventSubscriberCount >= $eventCapacity) echo 'onclick="return confirm(\'Svým přihlášením vyřadíte uživatele s nižší prioritou. Chcete se přesto přihlásit?\')" ';
			echo '/>';
		}
		echo '</form>';
	}
	if (($access & sepsAccessFlagsCanDeleteEvents) && ($eventSubscriberCount == 0))
	{
		echo "<form action='?' method='post'><input type='hidden' name='eid' value='$eid' /><input type='hidden' name='date' value='$eventdate' />";
		echo '<input type="hidden" name="action" value="deleteevent" /><input type="submit" onclick="return confirm(\'Určitě chcete zrušit tuto akci?\')" value="Zrušit akci" />';
		echo '</form>';
	}

	echo '</div>';
}

function subscribeToEvent($eid)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsHasAccess)) return;

	$query = mysql_query("SELECT COUNT(*) FROM subscriptions WHERE user=$sepsLoggedUser AND event=$eid");
	$result = mysql_fetch_row($query);
	if ($result[0] == 0)
	{
		$currdate = strftime('%Y-%m-%d %H:%M:%S');
		mysql_query("INSERT INTO subscriptions(user, event, subscribed) VALUES ($sepsLoggedUser, $eid, '$currdate')");
	}
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
			WHERE up.user=$sepsLoggedUser AND access>0
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
		echo '<div class="errmsg">Nemáte oprávnění pro zakládání nových událostí</div>';
	}
	echo '</div>';
}

function createNewEvent()
{
	global $sepsLoggedUser;

	$atDate = getVariableOrNull('date');
	$eventTitle = getVariableOrNull('title');
	$eventType = getVariableOrNull('eventtype');
	if (!$atDate || !$eventTitle || !$eventType) return;

	// TODO: check permissions
	// TODO: check time

	mysql_query(
		"INSERT INTO events (title, date, eventtype)
			VALUES ('" . mysql_real_escape_string($eventTitle) . "', '" . strftime('%Y%m%d', $atDate) . "', " . $eventType . ")");
}

function deleteEvent($eid)
{
	global $sepsLoggedUser;

	$event = Event::Load($eid);
	if (!$event) return;

	// TODO: check date
	// if ($event->getDate();

	$access = $event->getUserAccess($sepsLoggedUser);
	if (!($access & sepsAccessFlagsCanDeleteEvents)) return;

	if ($event->getSubscriberCount() > 0)
	{
		echo '<div class="errmsg">Tuto událost nelze smazat, neboť jsou na ni stále přihlášeni uživatelé</div>';
		return;
	}

	$query = mysql_query("DELETE FROM events WHERE events.id=$eid AND NOT EXISTS (SELECT * FROM subscriptions s WHERE s.event=$eid) LIMIT 1");
	if ($query && (mysql_affected_rows() > 0))
	{
		echo '<div class="infomsg">Událost smazána</div>';
	}
	else
	{
		echo '<div class="errmsg">Nelze smazat událost</div>';
	}
}
