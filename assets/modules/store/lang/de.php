<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Das Composer-Paket %1$s %2$s ist als Systemaufgabe #%3$s zur Installation eingeplant. Der Scheduler installiert es aus dem hochgeladenen Archiv.';
$_Lang['install_file_artifact_no_version'] = 'Das Archiv enthält das Composer-Paket %1$s, aber keine Version. Ergänzen Sie "version" in seiner composer.json oder geben Sie die Version im Dateinamen an, z. B. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Das Composer-Paket %1$s konnte nicht eingeplant werden: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Das Archiv sieht aus wie ein Composer-Paket, aber %1$s ist kein gültiges JSON mit einem Paketnamen "name". Es wurde nichts installiert.';
