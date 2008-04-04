<?php

define('SEPS', 1);

define('sepsAccessFlagsCanSee', 0x0001);
define('sepsAccessFlagsHasAccess', 0x0002);
define('sepsAccessFlagsCanSeeContacts', 0x0004);
define('sepsAccessFlagsCanInvite', 0x0008);
define('sepsAccessFlagsCanCreateEvents', 0x0010);
define('sepsAccessFlagsCanDeleteEvents', 0x0020);
define('sepsAccessFlagsCanTweakPriority', 0x0040);
define('sepsAccessFlagsCanChangeUserAccess', 0x0080);
define('sepsAccessFlagsCanSendWebMessages', 0x0100);
define('sepsAccessFlagsCanSendMailMessages', 0x0200);
define('sepsAccessFlagsCanEditEventTypes', 0x0400);
define('sepsAccessFlagsCanCreateAccount', 0x0800);

define('sepsAccessMaxValidBit', 0x0800);

$sepsAccessFlagNames = array('Prohlížet události', 'Měnit svou účast', 'Vidět kontakty', 'Posílat pozvánky do projektu', 'Vytvářet nové události', 'Rušit události', 'Upravovat prioritu účastníků události', 'Spravovat uživatele projektu', 'Přidávat zprávy na web', 'Posílat hromadné zprávy e-mailem', 'Spravovat typy událostí', 'Přímo založit účet');

define('sepsEmailCodeLength', 25);

define('sepsEmailCodeProjectInvitation', 1);
define('sepsEmailCodeEmailConfirmation', 2);
define('sepsEmailCodePasswordReset', 3);
