<?php

require_once('./include/Constants.php');
require_once('./include/GlobalFunctions.php');
require_once('./include/DefaultSettings.php');
require_once('./LocalSettings.php');
require_once('./include/Setup.php');
require_once('./include/Login.php');
require_once('./include/Invitations.php');

function mainPageContents()
{
	global $action, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess, $sepsLoggedUserEmail, $sepsPageMessage, $sepsLoggedUser, $sepsadminEnable;

	$invitation = getVariableOrNull('inv');
	if ($invitation)
	{
		if (!receivedInvitation($invitation)) return;
	}

	if ($action == 'acceptedinvitation')
	{
		performLogout();
		if (acceptedInvitation()) performLogin();
		else return;
	}
	else if ($action == 'dopasswordreset')
	{
		if (doPasswordReset()) performLogin();
		else return;
	}

	// $action = 'login';
	if ($action == 'login')
	{
		performLogin();
	}
	else if ($action == 'logout')
	{
		performLogout();
	}

	$sepsLoggedUserCaption = null;
	$sepsLoggedUsername = null;
	$sepsLoggedUserMaxAccess = 0;
	loadLoggedUserInformation();

	$menu = null;

	if ($sepsPageMessage)
	{
		echo "<div class='globalmsg'>$sepsPageMessage</div>";
	}

	if (!$sepsLoggedUser)
	{
		if ($action == 'resetpass')
		{
			// reset hesla
			passwordResetForm();
		}
		elseif ($action == 'sendpassreset')
		{
			sendPasswordReset();
		}
		else
		{
			// nepřihlášený uživatel
			loginScreen();
		}
	}
	else
	{
		require_once('./include/Events.php');
		require_once('./include/News.php');
		require_once('./include/UserSettings.php');

		if ($sepsadminEnable)
		{
			include('./include/Administration.php');
			globalAdministration();
		}

		if ($action == 'createevent')
		{
			createNewEvent();
		}
		else if ($action == 'deleteevent')
		{
			deleteEvent(getVariableOrNull('eid'));
		}
		else if ($action == 'savedescription')
		{
			changeDescription(getVariableOrNull('eid'), getVariableOrNull('description'));
		}
		else if ($action == 'sendinvitation')
		{
			sendInvitation();
		}
		else if ($action == 'sendemailconfirmation')
		{
			sendVerificationEmail();
		}
		else if ($action == 'savesettings')
		{
			saveUserSettings();
		}
		else if ($action == 'eventlist')
		{
			require_once('./include/EventList.php');
			showEventList();
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
			else if ($action == 'changeguests')
			{
				changeGuestCount($selectedEvent, getVariableOrNull('guestcount'));
			}

			printEventDetails($selectedEvent);
		}

		printEventsCalendar($selectedEvent || $action);

		if ($action == 'newevent')
		{
			newEventForm(getVariableOrNull('date'));
		}
		else if ($action == 'invite')
		{
			invitationForm();
		}
		else if ($action == 'messaging')
		{
		}
		else if ($action == 'manageusers')
		{
			require_once('./include/UserManagement.php');
			manageUsersForm();
		}
		else if ($action == 'manageeventtypes')
		{
			require_once('./include/EventTypes.php');
			eventTypesForm();
		}
		else if ($action == 'settings')
		{
			userSettingsForm();
		}
		else if ($action == 'viewlog')
		{
			require_once('./include/Logging.php');
			display_log();
		}

		echo '<br class="cleaner" />';

		$menu = array();
		$menu[] = array('?', 'Kalendář');
		$menu[] = array('?action=eventlist', 'Seznam');
		if ($sepsLoggedUserEmail && ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanInvite)) $menu[] = array('?action=invite', 'Pozvat dalšího');
		if ($sepsLoggedUserMaxAccess & (sepsAccessFlagsCanSendWebMessages | sepsAccessFlagsCanSendMailMessages)) $menu[] = array('?action=messaging', 'Poslat zprávu');
		if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanChangeUserAccess) $menu[] = array('?action=manageusers', 'Spravovat uživatele');
		if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanEditEventTypes) $menu[] = array('?action=manageeventtypes', 'Spravovat typy událostí');
		$menu[] = array('?action=settings', 'Nastavení');
		$menu[] = array('?action=logout', 'Odhlásit se');
	}

	echo '	</div>';

	if ($menu)
	{
		echo '	<div id="menu">';
		echo '<p class="loggedin">Uživatel: ' . htmlspecialchars($sepsLoggedUserCaption) . '</p>';
		echo '<ul>';
		foreach($menu as $item)
		{
			echo "<li><a href='$item[0]'>$item[1]</a></li>";
		}
		echo '	</ul></div>';
	}
}

// -------------------------------------------------------------------------------------------------------

echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>$sepsTitle</title>
  <meta name="generator" content="$sepsSoftwareAboutLine" />
  <link rel="stylesheet" href="css/main.css" type="text/css" />
EOT;

  if ($sepsFavicon) echo "<link rel='shortcut icon' href='$sepsFavicon' type='image/vnd.microsoft.icon' />";

echo <<<EOT
 </head>
 <body>

  <div id='header'>
	<div id='sitelogo'><a href='?'><img src='$sepsSiteLogo' width='100' height='100' alt='' /></a></div>
	<h1 id='sitecaption'>$sepsTitle</h1>
	<br class="cleaner" />
  </div>
  <div id='page'>
	<div id='contents'>
EOT;

	mainPageContents();

  echo '</div>';
  echo '<br class="cleaner" />';
  echo "<div id='footer'>Powered by <a href='$sepsSoftwareHomePage'>$sepsSoftwareAboutLine</a>. Správce serveru: <a href='mailto:" . str_replace('@', '&#x40;', $sepsAdminMail) . "'>" . str_replace('@', '&#x40;', $sepsAdminMail) . '</a></div>';

?>
 </body>
</html>
