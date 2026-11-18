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

$_Lang['check_updates'] = 'Nach Updates suchen';
$_Lang['check_updates_title'] = 'Jetzt nach neuen Versionen von Evolution CMS und den Erweiterungen suchen, ohne auf die tägliche Prüfung zu warten';
$_Lang['check_updates_done'] = 'Update-Informationen aktualisiert: Die Paketliste zeigt die neuesten Versionen. Öffnen Sie das Dashboard, um die Systemversion und veraltete Erweiterungen zu sehen:';
$_Lang['check_updates_dashboard'] = 'Dashboard';
$_Lang['check_updates_partial'] = 'Update-Informationen aktualisiert, aber der Paketkatalog konnte nicht abgerufen oder gespeichert werden: Die Liste unten ist möglicherweise veraltet. Öffnen Sie das Dashboard, um die Systemversion und veraltete Erweiterungen zu sehen:';
$_Lang['check_updates_failed'] = 'Die Suche nach Updates ist fehlgeschlagen. Versuchen Sie es später erneut.';
