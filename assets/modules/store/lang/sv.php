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
