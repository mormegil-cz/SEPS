<?php

$sepsSoftwareVersion = '0.5dev';
$sepsSoftwareVersionLine = 'SEPS/' . $sepsSoftwareVersion;
$sepsSoftwareVersionFpi = '-//mormegil.cz//NONSGML SEPS-' . $sepsSoftwareVersion . '//EN';
$sepsSoftwareAboutLine = 'SEPS version ' . $sepsSoftwareVersion;
$sepsSoftwareHomePage = 'https://github.com/mormegil-cz/SEPS';

$sepsTitle = '';
$sepsSiteLogo = 'img/defaultlogo.png';
$sepsFavicon = null;

$sepsCountry = 'CZ';

$sepsCalendarWeeks = 4;
$sepsShowEmail = true;
$sepsShowIcqStatus = true;
$sepsShowSkypeStatus = true;
$sepsShowJabberStatus = true;
$sepsDefaultInvitationAccess = sepsAccessFlagsCanSee | sepsAccessFlagsHasAccess | sepsAccessFlagsCanSeeContacts | sepsAccessFlagsCanInvite;
$sepsPasswordHashingAlgorithm = 'sha256';

$sepsDescriptionParser = 'htmlspecialchars';
$sepsDescriptionParserHelp = null;