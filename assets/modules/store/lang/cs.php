<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Balíček Composer %1$s %2$s je zařazen k instalaci jako systémová úloha #%3$s. Plánovač jej nainstaluje z nahraného archivu.';
$_Lang['install_file_artifact_no_version'] = 'Archiv obsahuje balíček Composer %1$s, ale bez verze. Přidejte "version" do jeho composer.json nebo uveďte verzi v názvu souboru, např. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Balíček Composer %1$s se nepodařilo zařadit do fronty: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Archiv vypadá jako balíček Composer, ale %1$s není platný JSON s názvem balíčku "name". Nic nebylo nainstalováno.';
