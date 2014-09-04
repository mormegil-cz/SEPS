<?php

require_once('./include/Dialogs.php');
require_once('./include/Events.php');

function showEventList()
{
	$startDateArg = getVariableOrNull('date');
	$startDate = $startDateArg ? $startDateArg : mktime();
	$startDate = 86400 * floor($startDate / 86400);
	$limit = getVariableOrNull('limit');
	if ($limit < 1) $limit = null;
	$limit = $limit ? $limit : 20;
	if ($limit > 200) $limit = 200;

	$events = getEventList($startDate, $limit);

    beginDialog('Seznam nejbližších událostí');
    beginDialogBody();
	echo '<ul>';
	foreach(getEventList($startDate, $limit) as $event)
	{
		$cssClass = $event->getCssClass();
		$eid = $event->getId();
		$eventTitle = htmlspecialchars($event->getTitle());
		$subscriberCount = $event->getSubscriberCount();
		$date = strtotime($event->getDate());
		$datestr = strftime('%d.&nbsp;%m.', $date);
		echo "<li><div class='$cssClass'><a class='date' href='?date=$date'>$datestr</a>: <a class='event-detail' href='?eid=$eid&amp;date=$date'>$eventTitle</a></div></li>";
	}
	echo '</ul>';
    endDialogBody();
    beginDialogFooter();
    echo '<a href="?" class="btn btn-default" data-dismiss="modal">Zavřít</button>';
    endDialogFooter();
    endDialog();
}
