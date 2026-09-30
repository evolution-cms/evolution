<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Pakiet Composer %1$s %2$s został dodany do kolejki instalacji jako zadanie systemowe #%3$s. Harmonogram zainstaluje go z przesłanego archiwum.';
$_Lang['install_file_artifact_no_version'] = 'Archiwum zawiera pakiet Composer %1$s, ale bez wersji. Dodaj "version" do jego composer.json lub podaj wersję w nazwie pliku, np. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Nie udało się dodać pakietu Composer %1$s do kolejki: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Archiwum wygląda jak pakiet Composer, ale %1$s nie jest poprawnym JSON z nazwą pakietu "name". Nic nie zostało zainstalowane.';
