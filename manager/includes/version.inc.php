<?php
$data = include EVO_CORE_PATH . 'factory/version.php';

$evo_version      = $data['version']; // Current version number
$evo_release_date = $data['release_date']; // Date of release
$evo_branch       = $data['branch']; // Codebase name
$evo_full_appname = $data['full_appname'];
