<?php

require_once('./include/Constants.php');
require_once('./include/GlobalFunctions.php');
require_once('./include/DefaultSettings.php');
require_once('./LocalSettings.php');
require_once('./include/Setup.php');
require_once('./include/Login.php');

echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>$sepsTitle</title>
  <meta name="generator" content="$sepsSoftwareAboutLine" />
  <link rel="stylesheet" href="css/main.css" type="text/css" />
 </head>
 <body>

  <div id='header'>
    <a href='?'><img id='sitelogo' src='$sepsSiteLogo' width='100' height='100' alt='' /></a>
	<h1 id='sitecaption'>$sepsTitle</h1>
  </div>
  <div id='page'>
    <div id='contents'>
EOT;

// $action = 'login';
if ($action == 'login')
{
	performLogin();
}
else if ($action == 'logout')
{
	performLogout();
}

$menu = null;

if (!$sepsLoggedUser)
{
	// nepřihlášený uživatel
	loginScreen();
}
else
{
	include('./include/Events.php');
	include('./include/News.php');

	if ($action == 'createevent')
	{
		createNewEvent();
	}

	printNews();

	$selectedEvent = getVariableOrNull('eid');
	if ($selectedEvent)
	{
		if ($action == 'subscribe')
		{
			subscribeToEvent($selectedEvent);
		}
		else if ($action == 'unsubscribe')
		{
			unsubscribeFromEvent($selectedEvent);
		}

		printEventDetails($selectedEvent);
	}

	printEventsCalendar($selectedEvent || $action);

	if ($action == 'newevent')
	{
		newEventForm(getVariableOrNull('date'));
	}

	echo '<br class="cleaner" />';

	$menu = array();
	$menu[] = array('?', 'Přehled');
	if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanInvite) $menu[] = array('?action=invite', 'Pozvat dalšího');
	if ($sepsLoggedUserMaxAccess & (sepsAccessFlagsCanSendWebMessages | sepsAccessFlagsCanSendMailMessages)) $menu[] = array('?action=messaging', 'Poslat zprávu');
	if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanChangeUserAccess) $menu[] = array('?action=manageusers', 'Spravovat uživatele');
	if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanChangeUserAccess) $menu[] = array('?action=manageeventtypes', 'Spravovat typy událostí');
	$menu[] = array('?action=logout', 'Odhlásit se');
}

echo '    </div>';

if ($menu)
{
	echo '    <div id="menu"><ul>';
	foreach($menu as $item)
	{
		echo "<li><a href='$item[0]'>$item[1]</a></li>";
	}
	echo '    </ul></div>';
}

?>
  </div>

 </body>
</html>
