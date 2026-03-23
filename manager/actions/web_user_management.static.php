<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('edit_user')) {
    $modx->webAlertAndQuit(ManagerTheme::getLexicon('error_no_privileges'));
}

$query = [
    'search' => isset($_REQUEST['search']) && is_scalar($_REQUEST['search']) ? $_REQUEST['search'] : '',
    'role' => isset($_REQUEST['role']) && is_scalar($_REQUEST['role']) ? $_REQUEST['role'] : '',
];

$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] - 1 : 0;

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

switch ($op) {
    case 'search':
        $page = 0;
        break;
    case 'reset':
        $query = [
            'search' => '',
            'role' => '',
        ];
        $page = 0;
        break;
}

// context menu
$cm = new \EvolutionCMS\Support\ContextMenu("cntxm", 150);
$cm->addItem(ManagerTheme::getLexicon('edit'), "js:menuAction(1)", $_style["icon_edit"], (!$modx->hasPermission('edit_user') ? 1 : 0));
$cm->addItem(ManagerTheme::getLexicon('delete'), "js:menuAction(2)", $_style["icon_trash"], (!$modx->hasPermission('delete_user') ? 1 : 0));
echo $cm->render();

// roles
$role_options = '<option value="0"' . ($query['role'] == '0' ? ' selected' : '') . '>' . ManagerTheme::getLexicon('no_user_role') . '</option>';
$roles = \EvolutionCMS\Models\UserRole::query()->select('id', 'name')->get()->toArray();
foreach ($roles as $row) {
    $role_options .= '<option value="'.$row['id'].'" '.($query['role'] != '' && $row['id'] == $query['role'] ? 'selected' : '').'>'.$row['name'].'</option>';
}

// prepare data
$managerUsers = \EvolutionCMS\Models\User::query()
    ->select('users.id', 'users.username', 'user_attributes.fullname', 'user_attributes.email', 'user_attributes.blocked', 'user_attributes.thislogin', 'user_attributes.logincount', 'user_attributes.blockeduntil', 'user_attributes.blockedafter', 'user_roles.name')
    ->join('user_attributes', 'user_attributes.internalKey', '=', 'users.id')
    ->leftJoin('user_roles', 'user_roles.id', '=', 'user_attributes.role')
    ->orderBy('users.username', 'ASC');

if ($query['search'] != '') {
    $val = $query['search'];
    $managerUsers = $managerUsers->where(function ($q) use ($val) {
        $q->where('users.username', 'LIKE', $val.'%')
            ->orWhere('user_attributes.fullname', 'LIKE', '%'.$val.'%')
            ->orWhere('user_attributes.email', 'LIKE', '%'.$val.'%');
    });
}
if ($query['role'] != '') {
    $val = $query['role'];
    $managerUsers = $managerUsers->where(function ($q) use ($val) {
        $q->where('user_attributes.role', '=', $val);
    });
}

$maxpageSize = $modx->getConfig('number_of_results');
define('MAX_DISPLAY_RECORDS_NUM', $maxpageSize);

$numRecords = $managerUsers->count();

if ($numRecords > 0) {
    $managerUsers = $managerUsers->offset($page * $maxpageSize)->limit($maxpageSize)->get()->toArray();

    // CSS style for table
    // $tableClass = 'grid';
    // $rowHeaderClass = 'gridHeader';
    // $rowRegularClass = 'gridItem';
    // $rowAlternateClass = 'gridAltItem';
    $tableClass = 'table data nowrap';
    $columnHeaderClass = [
        'center',
        '',
        '',
        '',
        'right" nowrap="nowrap,right,center',
    ];
    $table = new \EvolutionCMS\Support\MakeTable();
    $table->setTableClass($tableClass);
    $table->setColumnHeaderClass($columnHeaderClass);
    // $modx->getMakeTable()->setRowHeaderClass($rowHeaderClass);
    // $modx->getMakeTable()->setRowRegularClass($rowRegularClass);
    // $modx->getMakeTable()->setRowAlternateClass($rowAlternateClass);

    // Table header
    $listTableHeader = [
        'icon' => ManagerTheme::getLexicon('icon'),
        'name' => ManagerTheme::getLexicon('name'),
        'user_full_name' => ManagerTheme::getLexicon('user_full_name'),
        'email' => ManagerTheme::getLexicon('email'),
        'user_prevlogin' => ManagerTheme::getLexicon('user_prevlogin'),
        'user_logincount' => ManagerTheme::getLexicon('user_logincount'),
        'user_block' => ManagerTheme::getLexicon('user_block'),
    ];
    $tbWidth = [ '1%', '', '', '', '1%', '1%', '1%' ];
    $table->setColumnWidths($tbWidth);

    $listDocs = [];
    foreach ($managerUsers as $k => $el) {
        // дата блокировки
        $blocked_title = '';
        if ($el['blocked']) {
            if ($el['blockedafter']) {
                $blocked_title .= ManagerTheme::getLexicon('user_blockedafter').' '.$modx->toDateFormat($el['blockedafter']);
            }
            if ($el['blockedafter'] && $el['blockeduntil']) {
                $blocked_title .= ', ';
            }
            if ($el['blockeduntil']) {
                $blocked_title .= ManagerTheme::getLexicon('user_blockeduntil').' '.$modx->toDateFormat($el['blockeduntil']);
            }
        }

        $listDocs[] = [
            'icon' => '<a class="gridRowIcon" href="javascript:;" onclick="return showContentMenu(' . $el['id'] . ',event);" title="' . ManagerTheme::getLexicon('click_to_context') . '"><i class="' . $_style[empty($el['name']) ? 'icon_no_user_role' : 'icon_web_user'] . '"></i></a>',
            'name' => '<a href="index.php?a=88&id=' . $el['id'] . '" title="' . ManagerTheme::getLexicon('click_to_edit_title') . '">' . $el['username'] . '</a>',
            'user_full_name' => $el['fullname'],
            'email' => $el['email'],
            'role' => $el['name'] ?: ManagerTheme::getLexicon('no_user_role'),
            'user_prevlogin' => $el['thislogin'] ? $modx->toDateFormat($el['thislogin']) : '-',
            'user_logincount' => $el['logincount'],
            'user_block' => $el['blocked'] ? ManagerTheme::getLexicon('yes').' <i class="fa fa-question-circle help" data-tooltip="'.htmlspecialchars($blocked_title, ENT_QUOTES, ManagerTheme::getCharset()).'"></i>' : '-',
        ];
    }

    $table->createPagingNavigation($numRecords, 'a=99&'.http_build_query($query));
    $output = $table->create($listDocs, $listTableHeader, 'index.php?a=99');
} else {
    // no documents
    $output = '<div class="container"><p>' . ManagerTheme::getLexicon('resources_in_container_no') . '</p></div>';
}
?>
<script type="text/javascript">
    function searchResource() {
        document.resource.op.value = "search";
        document.resource.submit();
    };

    function resetSearch() {
        document.resource.op.value = "reset";
        document.resource.submit();
    };

    var selectedItem;
    var contextm = <?= $cm->getClientScriptObject() ?>;

    function showContentMenu(id, e) {
        selectedItem = id;
        contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) + "px";
        contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + "px";
        contextm.style.visibility = "visible";
        e.cancelBubble = true;
        return false;
    };

    function menuAction(a) {
        var id = selectedItem;
        switch(a) {
            case 1: // edit
                window.location.href = 'index.php?a=88&id=' + id;
                break;
            case 2: // delete
                if(confirm("<?php echo ManagerTheme::getLexicon('confirm_delete_user') ?>") === true) {
                    window.location.href = 'index.php?a=90&id=' + id;
                }
                break;
        }
    }

    document.addEventListener('click', function() {
        contextm.style.visibility = "hidden";
    });

    document.addEventListener('DOMContentLoaded', function() {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function() {
            document.querySelector('.element-edit-message').classList.toggle('show')
        }

    });
</script>

<form name="resource" method="post" action="?a=99">
    <input type="hidden" name="op" value="" />

    <h1>
        <i class="<?= $_style['icon_web_user'] ?>"></i><?php echo ManagerTheme::getLexicon('web_user_management_title') ?> <i class="<?= $_style['icon_question_circle'] ?> help"></i>
    </h1>

    <div class="container element-edit-message">
        <div class="alert alert-info"><?php echo ManagerTheme::getLexicon('web_user_management_msg') ?></div>
    </div>

    <div class="tab-page">
        <div class="container container-body">
            <div class="row searchbar form-group">
                <div class="col-sm-6 input-group">
                    <div class="input-group-btn">
                        <a class="btn btn-success btn-sm" href="index.php?a=87"><i class="<?= $_style['icon_add'] ?>"></i> <?php echo ManagerTheme::getLexicon('new_web_user') ?></a>
                    </div>
                </div>
                <div class="col-sm-6 ">
                    <div class="input-group float-right w-auto">
                        <select class="form-control form-control-sm" name="role">
                            <option value=""><?php echo ManagerTheme::getLexicon('web_user_management_select_role') ?></option>
                            <?php echo $role_options ?>
                        </select>
                        <input class="form-control form-control-sm" name="search" type="text" value="<?php echo $query['search'] ?>" placeholder="<?php echo ManagerTheme::getLexicon('search') ?>" />
                        <div class="input-group-append">
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?php echo ManagerTheme::getLexicon('search') ?>" onclick="searchResource(); return false;"><i class="<?= $_style['icon_search'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?php echo ManagerTheme::getLexicon('reset') ?>" onclick="resetSearch(); return false;"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group clearfix">
                <?php if ($numRecords > 0) : ?>
                    <div class="float-xs-left">
                        <span class="publishedDoc"><?php echo $numRecords . ' ' . ManagerTheme::getLexicon('resources_in_container') ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="row">
                <div class="table-responsive">
                <?php echo $output; ?>
                </div>
            </div>
        </div>
    </div>
</form>
