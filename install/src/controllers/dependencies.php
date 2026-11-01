<?php
// Reached in place of the requested step while core/vendor cannot boot the code laid over it
// (install/index.php, installerDependenciesStale()). Composer brings core/vendor to the new lock, then a
// fresh request goes on: this one still runs the autoloader of the previous vendor.
$repair = installerRepairDependencies();
$customPackages = $repair['customPackages'];
$composerDependencies = $repair['composerDependencies'];
$vendorStaleAfter = ($composerDependencies['installed'] ?? false) ? installerDependenciesStale(EVO_CORE_PATH) : $vendorStale;

if ($vendorStaleAfter === null) {
    // A GET resumes the step it asked for; a form post cannot be replayed, so the update starts over.
    $resume = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? ($resumeAction ?? 'mode') : 'mode';
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: index.php?action=' . rawurlencode($resume), true, 303);
    exit;
}

include dirname(__DIR__) . '/template/actions/dependencies.php';
