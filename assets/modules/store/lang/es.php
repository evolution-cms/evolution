<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'El paquete de Composer %1$s %2$s está en cola para instalarse como tarea del sistema #%3$s. El planificador lo instalará desde el archivo subido.';
$_Lang['install_file_artifact_no_version'] = 'El archivo contiene el paquete de Composer %1$s, pero sin versión. Añada "version" a su composer.json o indique la versión en el nombre del archivo, p. ej. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'No se pudo poner en cola el paquete de Composer %1$s: %2$s';
$_Lang['install_file_artifact_invalid'] = 'El archivo parece un paquete de Composer, pero %1$s no es un JSON válido con un nombre de paquete "name". No se instaló nada.';
