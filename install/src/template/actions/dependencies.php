<?php
/**
 * Shown when the Composer repair of controllers/dependencies.php could not bring core/vendor to the new lock.
 *
 * @var array $_lang
 * @var string $vendorStale Why core/vendor could not boot this version.
 * @var string|null $vendorStaleAfter Why it still cannot, after the repair.
 * @var array $customPackages restoreCustomComposerPackages() result.
 * @var array|null $composerDependencies installerInstallComposerDependencies() result, null when it did not run.
 * @var string|null $resumeAction The step that was asked for.
 */
$failed = !$customPackages['restored'] ? $customPackages : $composerDependencies;
?>
<h2><?=$_lang['dependencies_title']?></h2>
<p><?=$_lang['dependencies_note']?></p>
<pre class="mono"><?=htmlspecialchars((string) $vendorStale, ENT_QUOTES, 'UTF-8')?></pre>

<?php if (!$customPackages['restored']) : ?>
    <p>
        <?=$_lang['custom_packages_not_restored']?> <strong><?=htmlspecialchars(implode(', ', $customPackages['packages']), ENT_QUOTES, 'UTF-8')?></strong>
        <span class="notok"><?=$_lang['failed']?></span>
    </p>
<?php elseif ($composerDependencies !== null && !$composerDependencies['installed']) : ?>
    <p>
        <?=$_lang['composer_dependencies_not_installed']?>
        <span class="notok"><?=$_lang['failed']?></span>
    </p>
<?php elseif ($vendorStaleAfter !== null) : ?>
    <pre class="mono"><?=htmlspecialchars((string) $vendorStaleAfter, ENT_QUOTES, 'UTF-8')?></pre>
<?php endif; ?>

<?php if (is_array($failed) && ($failed['command'] ?? '') !== '') : ?>
    <p><?=$_lang['custom_packages_run_command']?></p>
    <p><code><?=htmlspecialchars($failed['command'], ENT_QUOTES, 'UTF-8')?></code></p>
<?php endif; ?>
<?php if (is_array($failed) && ($failed['output'] ?? '') !== '') : ?>
    <pre class="mono"><?=htmlspecialchars($failed['output'], ENT_QUOTES, 'UTF-8')?></pre>
<?php endif; ?>

<p><?=$_lang['dependencies_retry']?></p>
<p><a class="btn btn-primary" href="index.php?action=<?=htmlspecialchars(rawurlencode($resumeAction ?? 'mode'), ENT_QUOTES, 'UTF-8')?>"><?=$_lang['dependencies_check_again']?></a></p>
