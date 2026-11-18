<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Composer-paketet %1$s %2$s står i kö för installation som systemuppgift #%3$s. Schemaläggaren installerar det från det uppladdade arkivet.';
$_Lang['install_file_artifact_no_version'] = 'Arkivet innehåller Composer-paketet %1$s, men utan version. Lägg till "version" i dess composer.json eller ange versionen i filnamnet, t.ex. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composer-paketet %1$s kunde inte köas: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Arkivet ser ut som ett Composer-paket, men %1$s är inte giltig JSON med ett paketnamn "name". Inget installerades.';

$_Lang['check_updates'] = 'Sök efter uppdateringar';
$_Lang['check_updates_title'] = 'Sök efter nya versioner av Evolution CMS och tilläggen nu, utan att vänta på den dagliga kontrollen';
$_Lang['check_updates_done'] = 'Uppdateringsinformationen har hämtats på nytt: paketlistan visar de senaste versionerna. Öppna startsidan för att se systemversionen och inaktuella tillägg:';
$_Lang['check_updates_dashboard'] = 'Hem';
$_Lang['check_updates_partial'] = 'Uppdateringsinformationen har hämtats på nytt, men paketkatalogen kunde inte hämtas eller sparas: listan nedan kan vara inaktuell. Öppna startsidan för att se systemversionen och inaktuella tillägg:';
$_Lang['check_updates_failed'] = 'Det gick inte att söka efter uppdateringar. Försök igen senare.';
