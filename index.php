<?php

require_once('./include/Constants.php');
require_once('./include/GlobalFunctions.php');
require_once('./include/DefaultSettings.php');
require_once('./LocalSettings.php');
require_once('./include/Setup.php');
require_once('./include/Login.php');
require_once('./include/Invitations.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (!verifyCsrfToken()) die();
}

function mainPageContents()
{
	global $action, $sepsLoggedUserCaption, $sepsLoggedUserMaxAccess, $sepsLoggedUserEmail, $sepsPageMessage, $sepsLoggedUser, $sepsLoggedUserGlobalRights, $sepsTitle;

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
		require_once('./include/UserManagement.php');
		require_once('./include/Administration.php');

        // --- menu
        echo '<div class="navbar navbar-inverse navbar-fixed-top" role="navigation"><div class="container"><div class="navbar-header"><button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse"><span class="sr-only">Navigace</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span></button> <a class="navbar-brand" href="?">' . htmlspecialchars($sepsTitle) . '</a></div><div class="collapse navbar-collapse"><ul class="nav navbar-nav">';
        echo '<li><a href="?">Kalendář</a></li>';
        echo '<li><a href="?action=eventlist">Seznam</a></li>';

		if ($sepsLoggedUserEmail && ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanInvite)) echo '<li><a href="?action=invite">Přizvat nováčka</a></li>';
		if ($sepsLoggedUserMaxAccess & (sepsAccessFlagsCanSendWebMessages | sepsAccessFlagsCanSendMailMessages)) echo '<li><a href="?action=messaging">Poslat zprávu</a></li>';

		if (($sepsLoggedUserMaxAccess & sepsAccessFlagsCanCreateAccount) ||
            ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanChangeUserAccess) ||
            ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanEditEventTypes))
        {
            echo '<ul class="nav navbar-nav"><li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Správa projektu<span class="caret"></span></a><ul class="dropdown-menu" role="menu">';
            if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanCreateAccount) echo '<li><a href="?action=createaccount">Založit uživatele</a></li>';
            if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanChangeUserAccess) echo '<li><a href="?action=manageusers">Spravovat uživatele</a></li>';
            if ($sepsLoggedUserMaxAccess & sepsAccessFlagsCanEditEventTypes) echo '<li><a href="?action=manageeventtypes">Spravovat typy událostí</a></li>';
            echo '</ul></li>';
        }

        if (($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanCreateProjects) ||
            ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanDeleteProjects) ||
            ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanManageGlobalPermissions) ||
            ($sepsLoggedUserGlobalRights & (sepsGlobalAccessFlagsCanSendGlobalWebMessages | sepsGlobalAccessFlagsCanSendGlobalMailMessages)) ||
            ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanViewLog))
        {
            echo '<ul class="nav navbar-nav"><li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Administrace<span class="caret"></span></a><ul class="dropdown-menu" role="menu">';
            if ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanCreateProjects) echo '<li><a href="?action=newproject">Založit nový projekt</a></li>';
            if ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanDeleteProjects) echo '<li><a href="?action=deleteproject">Zrušit projekt</a></li>';
            if ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanManageGlobalPermissions) echo '<li><a href="?action=manageglobalpermissions">Spravovat globální práva</a></li>';
            if ($sepsLoggedUserGlobalRights & (sepsGlobalAccessFlagsCanSendGlobalWebMessages | sepsGlobalAccessFlagsCanSendGlobalMailMessages)) echo '<li><a href="?action=globalmessaging">Poslat globální zprávu</a></li>';
            if ($sepsLoggedUserGlobalRights & sepsGlobalAccessFlagsCanViewLog) echo '<li><a href="?action=viewlog">Log</a></li>';
            echo '</ul></li>';
        }

        echo '<ul class="nav navbar-nav navbar-right"><li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">' . htmlspecialchars($sepsLoggedUserCaption) . '<span class="caret"></span></a><ul class="dropdown-menu" role="menu">';
        echo '<li><a href="?action=export">Export</a></li>';
        echo '<li><a href="?action=settings">Nastavení</a></li>';
        echo '<li><a href="?action=logout">Odhlásit se</a></li>';
        echo '</ul></li>';

        echo '</ul></div></div></div>';
        // ---

        echo '<div class="container">';
        
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
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
			else if ($action == 'createuser')
			{
				createNewUser();
			}
			else if ($action == 'sendemailconfirmation')
			{
				sendVerificationEmail();
			}
			else if ($action == 'savesettings')
			{
				saveUserSettings();
			}
			else if ($action == 'createproject')
			{
				createProject();
			}
			else if ($action == 'removeproject')
			{
				deleteProject();
			}
			else if ($action == 'changeglobalpermissions')
			{
				changeGlobalPermissions();
			}
		}

		if ($action == 'eventlist')
		{
			require_once('./include/EventList.php');
			showEventList();
		}
		else if ($action == 'export' || $action == 'genapitoken')
		{
			require_once('./include/ExportMenu.php');
			if ($action == 'genapitoken' && $_SERVER['REQUEST_METHOD'] == 'POST')
			{
				generateApiToken();
			}
			showExportMenu();
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
			else if ($action == 'rejectevent')
			{
				rejectEvent($selectedEvent);
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
		else if ($action == 'createaccount')
		{
			accountCreationForm();
		}
		else if ($action == 'messaging')
		{
			// messaging();
		}
		else if ($action == 'manageusers')
		{
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
		else if ($action == 'newproject')
		{
			projectCreationForm();
		}
		else if ($action == 'deleteproject')
		{
			projectDeletionForm();
		}
		else if ($action == 'manageglobalpermissions')
		{
			manageGlobalPermissionsForm();
		}
		else if ($action == 'globalmessaging')
		{
			// globalMessaging();
		}

        echo '</div>';
    }
}

// -------------------------------------------------------------------------------------------------------

echo <<<EOT
<!DOCTYPE html>
<html lang="cs">
 <head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>$sepsTitle</title>
  <meta name="generator" content="$sepsSoftwareAboutLine" />
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
EOT;

  if ($sepsFavicon) echo "<link rel='shortcut icon' href='$sepsFavicon' type='image/vnd.microsoft.icon' />";

echo <<<EOT
 </head>
 <body>
EOT;

	mainPageContents();

  echo "<div class='footer'><div class='container'><p class='text-muted'>Powered by <a href='$sepsSoftwareHomePage'>$sepsSoftwareAboutLine</a>. Správce serveru: <a href='mailto:" . str_replace('@', '&#x40;', $sepsAdminMail) . "'>" . str_replace('@', '&#x40;', $sepsAdminMail) . '</a></p></div></div>';

?>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script>
$(function() {
    $('.modal').modal('show');
});
  </script>
 </body>
</html>
