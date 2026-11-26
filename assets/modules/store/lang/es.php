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

$_Lang['more'] = 'Más';
$_Lang['check_updates'] = 'Buscar actualizaciones';
$_Lang['check_updates_title'] = 'Buscar ahora nuevas versiones de Evolution CMS y de los complementos, sin esperar a la comprobación diaria';
$_Lang['check_updates_done'] = 'Información de actualizaciones renovada: la lista de paquetes muestra las versiones más recientes. Abra el Cuadro de Mando para ver la versión del sistema y los complementos desactualizados:';
$_Lang['check_updates_dashboard'] = 'Cuadro de Mando';
$_Lang['check_updates_partial'] = 'Información de actualizaciones renovada, pero no se pudo obtener o guardar el catálogo de paquetes: la lista de abajo puede estar desactualizada. Abra el Cuadro de Mando para ver la versión del sistema y los complementos desactualizados:';
$_Lang['check_updates_failed'] = 'No se pudieron buscar actualizaciones. Inténtelo de nuevo más tarde.';
