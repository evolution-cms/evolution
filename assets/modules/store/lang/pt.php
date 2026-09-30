<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'O pacote Composer %1$s %2$s está na fila para instalação como tarefa do sistema #%3$s. O agendador vai instalá-lo a partir do arquivo enviado.';
$_Lang['install_file_artifact_no_version'] = 'O arquivo contém o pacote Composer %1$s, mas sem versão. Adicione "version" ao composer.json dele ou indique a versão no nome do arquivo, por exemplo %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Não foi possível colocar o pacote Composer %1$s na fila: %2$s';
$_Lang['install_file_artifact_invalid'] = 'O arquivo parece um pacote Composer, mas %1$s não é um JSON válido com o nome do pacote "name". Nada foi instalado.';
