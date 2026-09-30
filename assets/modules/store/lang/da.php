<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Composer-pakken %1$s %2$s er sat i kø til installation som systemopgave #%3$s. Planlæggeren installerer den fra det uploadede arkiv.';
$_Lang['install_file_artifact_no_version'] = 'Arkivet indeholder Composer-pakken %1$s, men uden version. Tilføj "version" til dens composer.json, eller angiv versionen i filnavnet, f.eks. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composer-pakken %1$s kunne ikke sættes i kø: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Arkivet ligner en Composer-pakke, men %1$s er ikke gyldig JSON med et pakkenavn "name". Intet blev installeret.';
