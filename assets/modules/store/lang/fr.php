<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Le paquet Composer %1$s %2$s est en file d\'attente pour installation en tant que tâche système #%3$s. Le planificateur l\'installera depuis l\'archive envoyée.';
$_Lang['install_file_artifact_no_version'] = 'L\'archive contient le paquet Composer %1$s, mais sans version. Ajoutez "version" à son composer.json ou indiquez la version dans le nom du fichier, par ex. %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Impossible de mettre en file d\'attente le paquet Composer %1$s : %2$s';
$_Lang['install_file_artifact_invalid'] = 'L\'archive ressemble à un paquet Composer, mais %1$s n\'est pas un JSON valide avec un nom de paquet "name". Rien n\'a été installé.';

$_Lang['check_updates'] = 'Rechercher des mises à jour';
$_Lang['check_updates_title'] = 'Rechercher dès maintenant les nouvelles versions d’Evolution CMS et des extensions, sans attendre la vérification quotidienne';
$_Lang['check_updates_done'] = 'Informations de mise à jour actualisées : la liste des paquets affiche les dernières versions. Ouvrez l’accueil pour voir la version du système et les extensions obsolètes :';
$_Lang['check_updates_dashboard'] = 'Accueil';
$_Lang['check_updates_failed'] = 'Impossible de rechercher des mises à jour. Réessayez plus tard.';
