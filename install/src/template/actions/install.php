<div class="stepcontainer">
    <ul class="progressbar">
        <li class="visited"><?=$_lang['choose_language']?></li>
        <li class="visited"><?=$_lang['installation_mode']?></li>
        <li class="visited"><?=$_lang['optional_items']?></li>
        <li class="visited"><?=$_lang['preinstall_validation']?></li>
        <li class="active"><?=$_lang['install_results']?></li>
    </ul>
    <div class="clearleft"></div>
</div>
<h2><?=$_lang['install_results']?></h2>
<h3><?=$_lang['setup_database']?></h3>
<?php if ($conn === false) : ?>
    <p>
        <?=$_lang['setup_database_create_connection']?>
        <span class="notok"><?=$_lang['setup_database_create_connection_failed']?></span>
    </p>
    <p><?=$_lang['setup_database_create_connection_failed_note']?></p>
<?php else : ?>
    <p><?=$_lang['setup_database_create_connection']?> <span class="ok"><?=$_lang['ok']?></span></p>
    <?php if (isset($selectDatabase) && $selectDatabase === false) : ?>
        <p><?=rtrim($_lang['setup_database_selection'], '`')?> <strong><?=trim($database_name, '` ')?></strong>:
            <span class="notok" style='color:#707070'>
                <?=$_lang['setup_database_selection_failed']?>
            </span>
            <?=$_lang['setup_database_selection_failed_note']?>
        </p>
        <?php if (isset($createDatabase) && $createDatabase === false) : ?>
            <p>
                <?=rtrim($_lang['setup_database_creation'], '`')?> <strong><?=trim($database_name, '` ')?></strong>:
                <span class="notok"><?=$_lang['setup_database_creation_failed']?></span>
                <?=$_lang['setup_database_creation_failed_note']?>
            </p>
            <pre>
                database charset = <?=$database_connection_charset?>
                database collation = <?=$database_collation?>
            </pre>
            <p><?=$_lang['setup_database_creation_failed_note2']?></p>
        <?php else : ?>
            <p>
                <?=rtrim($_lang['setup_database_creation'], '`')?> <strong><?=trim($database_name, '` ')?></strong>:
                <span class="ok"><?=$_lang['ok']?></span>
            </p>
        <?php endif; ?>
    <?php else : ?>
        <p>
            <?=rtrim($_lang['setup_database_selection'], '`')?> <strong><?=trim($database_name, '` ')?></strong>:
            <span class="ok"><?=$_lang['ok']?></span>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($installLevel >= 1) : ?>
    <?php if (isset($checkPrefix) && $checkPrefix === false) : ?>
        <p>
            <?=rtrim($_lang['checking_table_prefix'], '`')?> <strong><?=trim($table_prefix, '`')?></strong>:
            <span class="notok"><?=$_lang['failed']?></span> <?=$_lang['table_prefix_already_inuse']?>
        </p>
        <p><?=$_lang['table_prefix_already_inuse_note']?></p>
    <?php else : ?>
        <p>
            <?=rtrim($_lang['checking_table_prefix'], '`')?> <strong><?=trim($table_prefix, '`')?></strong>:
            <span class="ok"><?=$_lang['ok']?></span>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($installLevel >=  2 && $moduleSQLBaseFile) : ?>
    <?php if (isset($sqlParser) && $sqlParser->installFailed === false) : ?>
        <p>
            <?=$_lang['setup_database_creating_tables']?>
            <span class="notok"><b><?=$_lang['database_alerts']?></span>
        </p>
        <p><?=$_lang['setup_couldnt_install']?></p>
        <p><?=$_lang['installation_error_occured']?><br /><br />
        <?php $sqlErrors = count($sqlParser->mysqlErrors); ?>
        <?php for ($i = 0; $i < $sqlErrors; $i++) : ?>
            <em><?=$sqlParser->mysqlErrors[$i]["error"]?></em>
            <?=$_lang['during_execution_of_sql'];?>
            <code><?=strip_tags($sqlParser->mysqlErrors[$i]["sql"])?></code>
            <hr />
        <?php endfor; ?>
        <p><?=$_lang['some_tables_not_updated']?></p>
    <?php else : ?>
        <p><?=$_lang['setup_database_creating_tables']?> <span class="ok"><?=$_lang['ok']?></span></p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($installLevel >= 3) : ?>
    <?php if (isset($configFileFailed) && $configFileFailed === true) : ?>
        <p>
            <?=$_lang['writing_config_file']?> <span class="notok"><?=$_lang['failed']?></span>
        </p>
        <p><?=$_lang['cant_write_config_file']?> <code>/core/config/database/connections/default.php</code></p>
        <textarea style="width:400px; height:160px;"><?=$configString?></textarea>
        <p><?=$_lang['cant_write_config_file_note']?></p>
    <?php else : ?>
        <p>
            <?=$_lang['writing_config_file']?> <span class="ok"><?=$_lang['ok']?></span>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($installLevel >= 4 && $installData && $moduleSQLDataFile && $moduleSQLResetFile) : ?>
    <?php if (isset($sqlParser) && $sqlParser->installFailed === true) : ?>
        <p>
            <?=$_lang['resetting_database']?>
            <span class="notok"><b><?=$_lang['database_alerts']?></span>
        </p>
        <p><?=$_lang['setup_couldnt_install']?></p>
        <p><?=$_lang['installation_error_occured']?><br /><br />
        <?php $sqlErrors = count($sqlParser->mysqlErrors); ?>
        <?php for ($i = 0; $i < $sqlErrors; $i++) : ?>
            <em><?=$sqlParser->mysqlErrors[$i]["error"]?></em> <?=$_lang['during_execution_of_sql']?>
            <span class='mono'><?=strip_tags($sqlParser->mysqlErrors[$i]["sql"]);?></span>
            <hr />
        <?php endfor; ?>
        </p>
        <p><?=$_lang['some_tables_not_updated']?></p>
    <?php else : ?>
        <p>
            <?=$_lang['resetting_database']?>
            <span class="ok"><?=$_lang['ok']?></span>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($installLevel >= 5) : ?>
    <?php if (isset($installDataLevel['templates'])) : ?>
        <h3><?=$_lang['templates']?>:</h3>
        <?php foreach ($installDataLevel['templates'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php elseif ($itemData['error']['type'] === 'file_not_found') : ?>
                    <p>&nbsp;&nbsp;<?=$itemName?>:
                        <span class="notok">
                            <?=$_lang['unable_install_template']?> '<?=$itemData['data']['file']?>'
                            <?=$_lang['not_found']?>.
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['tvs'])) : ?>
        <h3><?=$_lang['tvs']?>:</h3>
        <?php foreach ($installDataLevel['tvs'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php elseif($itemData['type'] === 'skip') : ?>
                    <!-- SKIP -->
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['chunks'])) : ?>
        <h3><?=$_lang['chunks']?>:</h3>
        <?php foreach ($installDataLevel['chunks'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'overwrite') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php elseif($itemData['type'] === 'skip') : ?>
                    <!-- SKIP -->
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php elseif ($itemData['error']['type'] === 'file_not_found') : ?>
                    <p>&nbsp;&nbsp;<?=$itemName?>:
                        <span class="notok">
                            <?=$_lang['unable_install_chunk']?> '<?=$itemData['data']['file']?>'
                            <?=$_lang['not_found']?>.
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['modules'])) : ?>
        <h3><?=$_lang['modules']?>:</h3>
        <?php foreach ($installDataLevel['modules'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php elseif($itemData['type'] === 'skip') : ?>
                    <!-- SKIP -->
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php elseif ($itemData['error']['type'] === 'file_not_found') : ?>
                    <p>&nbsp;&nbsp;<?=$itemName?>:
                        <span class="notok">
                            <?=$_lang['unable_install_module']?> '<?=$itemData['data']['file']?>'
                            <?=$_lang['not_found']?>.
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['plugins'])) : ?>
        <h3><?=$_lang['plugins']?>:</h3>
        <?php foreach ($installDataLevel['plugins'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php elseif($itemData['type'] === 'skip') : ?>
                    <!-- SKIP -->
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php elseif ($itemData['error']['type'] === 'file_not_found') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>:
                        <span class="notok">
                            <?=$_lang['unable_install_plugin']?> '<?=$itemData['data']['file']?>'
                            <?=$_lang['not_found']?>.
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['snippets'])) : ?>
        <h3><?=$_lang['snippets']?>:</h3>
        <?php foreach ($installDataLevel['snippets'] as $itemName => $itemData) : ?>
            <?php if (empty($itemData['error'])) : ?>
                <?php if($itemData['type'] === 'create') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'overwrite') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['installed']?></span></p>
                <?php elseif($itemData['type'] === 'update') : ?>
                    <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>: <span class="ok"><?=$_lang['upgraded']?></span></p>
                <?php elseif($itemData['type'] === 'skip') : ?>
                    <!-- SKIP -->
                <?php endif; ?>
            <?php else : ?>
                <?php if ($itemData['error']['type'] === 'sql') : ?>
                    <p>&#10060; <?=$itemData['error']['content']?></p>
                <?php elseif ($itemData['error']['type'] === 'file_not_found') : ?>
                    <p>&nbsp;&nbsp;<?=$itemName?>:
                        <span class="notok">
                            <?=$_lang['unable_install_snippet']?> '<?=$itemData['data']['file']?>'
                            <?=$_lang['not_found']?>.
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($installDataLevel['demo'])) : ?>
        <h3><?=$_lang['installing_demo_site']?></h3>
        <?php if (empty($installDataLevel['demo']['error'])) : ?>
            <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp<span class="ok"><?=$_lang['ok']?></span></p>
        <?php else : ?>
            <p><span class="notok"><b><?=$_lang['database_alerts']?></span></p>
            <p><?=$_lang['setup_couldnt_install']?></p>
            <p><?=$_lang['installation_error_occured']?></p>
            <br /><br />
            <?php foreach($installDataLevel['demo']['error'] as $error): ?>
                <em><?=$error['content']?></em>
                <?=$_lang['during_execution_of_sql']?>
                <code><?=htmlspecialchars($error['sql'])?></code>
                <hr />
            <?php endforeach; ?>
            <p><?=$_lang['some_tables_not_updated']?></p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($installDependencyLevel) && $installLevel >= 6): ?>
    <?php foreach ($installDependencyLevel as $itemName => $itemData) : ?>
        <?php if (empty($itemData['error'])) : ?>
            <?php if($itemData['type'] === 'create') : ?>
                <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>Module: <span class="ok"><?=$_lang['depedency_create']?></span></p>
            <?php elseif($itemData['type'] === 'update') : ?>
                <p>&nbsp;&nbsp;&#10003;&nbsp;&nbsp;<?=$itemName?>Module: <span class="ok"><?=$_lang['depedency_update']?></span></p>
            <?php endif; ?>

            <?php if (isset($itemData['extra']['type']) && $itemData['extra']['type'] == 'done') : ?>
                <p>
                    &nbsp;&nbsp;&nbsp;&nbsp;<?=$itemData['extra']['content']?></span>
                </p>
            <?php elseif (isset($itemData['extra']['type']) && $itemData['extra']['type'] == 'error'): ?>
                <p>
                    &nbsp;&nbsp;&nbsp;&nbsp;<?=$itemData['extra']['content']?>:
                    <span class="ok"><?=$_lang['guid_set']?></span>
                </p>
            <?php endif; ?>
        <?php else : ?>
            <?php if ($itemData['error']['type'] === 'sql') : ?>
                <p>&#10060; <?=$itemData['error']['content']?></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($installLevel >= 7): ?>
    <h2><?=$_lang['installation_successful']?></h2>
    <p><?=$_lang['to_log_into_content_manager']?></p>
    <?php if ($installMode === 0) : ?>
        <p>
            <img src="img/ico_info.png" width="40" height="42" class="mx-2"/>
            <?= $_lang['installation_note'] ?>
        </p>
    <?php else : ?>
        <p>
            <img src="img/ico_info.png" width="40" height="42" class="mx-2"/>
            <?= $_lang['upgrade_note'] ?>
        </p>
    <?php endif; ?>

    <form name="install" id="install_form" action="index.php?action=options" method="post">
        <?php if ($errors === 0) : ?>
            <?php if (is_writable(dirname(__DIR__, 2))) : ?>
                <label id="removeinstall" class="clickable">
                    <input type="checkbox" name="rminstaller" <?= (empty($errors) ? 'checked="checked"' : '') ?>/>
                    <?= $_lang['remove_install_folder_auto'] ?>
                </label>
            <?php else : ?>
                <span id="removeinstall"><?= $_lang['remove_install_folder_manual'] ?></span>
            <?php endif; ?>
        <?php endif; ?>
        <p class="buttonlinks">
            <button type="button" id="closepage" nonce="<?= csrfNonce(); ?>" title="<?= $_lang['btnclose_value'] ?>">
                <span><?= $_lang['btnclose_value'] ?></span>
            </button>
        </p>
        <br/>
    </form>
    <br/>
    <script type="text/javascript" nonce="<?= csrfNonce(); ?>">
      function closepage() {
        var chk = document.install.rminstaller;
        if (chk && chk.checked) {
          // remove install folder and files
          window.location.href = "../<?=MGR_DIR;?>/processors/remove_installer.processor.php?rminstall=1";
        } else {
          window.location.href = "../<?=MGR_DIR;?>/";
        }
      }
      document.getElementById('closepage').onclick = closepage;
    </script>
<?php endif; ?>
