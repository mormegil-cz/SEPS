<?php

$sepsSoftwareVersion = '0.1dev';
$sepsSoftwareVersionLine = 'SEPS/' . $sepsSoftwareVersion;
$sepsSoftwareVersionFpi = '-//mormegil.cz//NONSGML SEPS-' . $sepsSoftwareVersion . '//EN';
$sepsSoftwareAboutLine = 'SEPS version ' . $sepsSoftwareVersion;
$sepsSoftwareHomePage = 'https://opensvn.csie.org/traccgi/seps/';

$sepsTitle = '';
$sepsSiteLogo = 'img/defaultlogo.png';
$sepsFavicon = null;

$sepsCalendarWeeks = 4;
$sepsShowEmail = true;
$sepsShowIcqStatus = true;
$sepsShowSkypeStatus = true;
$sepsShowJabberStatus = true;
$sepsDefaultInvitationAccess = sepsAccessFlagsCanSee | sepsAccessFlagsHasAccess | sepsAccessFlagsCanSeeContacts | sepsAccessFlagsCanInvite;
$sepsPasswordHashingAlgorithm = 'sha256';

$sepsadminEnable = false;

$sepsDescriptionParser = 'htmlspecialchars';
$sepsDescriptionParserHelp = null;