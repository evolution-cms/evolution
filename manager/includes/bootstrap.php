<?php
/**
 * @see https://github.com/theseer/Autoload
 */
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'evolutioncms\\cache' => '/src/Cache.php',
                'evolutioncms\\core' => '/src/Core.php',
                'evolutioncms\\database' => '/src/Database.php',
                'evolutioncms\\event' => '/src/Event.php',
                'evolutioncms\\exceptions\\containerexception' => '/src/Exceptions/ContainerException.php',
                'evolutioncms\\exceptions\\propertyexception' => '/src/Exceptions/PropertyException.php',
                'evolutioncms\\exceptions\\servicenotfoundexception' => '/src/Exceptions/ServiceNotFoundException.php',
                'evolutioncms\\interfaces\\captchainterface' => '/src/Interfaces/CaptchaInterface.php',
                'evolutioncms\\interfaces\\contextmenuinterface' => '/src/Interfaces/ContextMenuInterface.php',
                'evolutioncms\\interfaces\\coreinterface' => '/src/Interfaces/CoreInterface.php',
                'evolutioncms\\interfaces\\databaseinterface' => '/src/Interfaces/DatabaseInterface.php',
                'evolutioncms\\interfaces\\datagridinterface' => '/src/Interfaces/DataGridInterface.php',
                'evolutioncms\\interfaces\\datasetpagerinterface' => '/src/Interfaces/DataSetPagerInterface.php',
                'evolutioncms\\interfaces\\deprecatedcoreinterface' => '/src/Interfaces/DeprecatedCoreInterface.php',
                'evolutioncms\\interfaces\\eventinterface' => '/src/Interfaces/EventInterface.php',
                'evolutioncms\\interfaces\\exportsiteinerface' => '/src/Interfaces/ExportSiteInerface.php',
                'evolutioncms\\interfaces\\mailinterface' => '/src/Interfaces/MailInterface.php',
                'evolutioncms\\interfaces\\maketableinterface' => '/src/Interfaces/MakeTableInterface.php',
                'evolutioncms\\interfaces\\managerapiinterface' => '/src/Interfaces/ManagerApiInterface.php',
                'evolutioncms\\interfaces\\managerthemeinterface' => '/src/Interfaces/ManagerThemeInterface.php',
                'evolutioncms\\interfaces\\menuinterface' => '/src/Interfaces/MenuInterface.php',
                'evolutioncms\\interfaces\\modifiersinterface' => '/src/Interfaces/ModifiersInterface.php',
                'evolutioncms\\interfaces\\mysqldumperinterface' => '/src/Interfaces/MysqlDumperInterface.php',
                'evolutioncms\\interfaces\\paginginateinterface' => '/src/Interfaces/PaginginateInterface.php',
                'evolutioncms\\interfaces\\passwordhashinterface' => '/src/Interfaces/PasswordHashInterface.php',
                'evolutioncms\\interfaces\\phpcompatinterface' => '/src/Interfaces/PhpCompatInterface.php',
                'evolutioncms\\interfaces\\serviceproviderinterface' => '/src/Interfaces/ServiceProviderInterface.php',
                'evolutioncms\\legacy\\categories' => '/src/Legacy/Categories.php',
                'evolutioncms\\legacy\\deprecatedcore' => '/src/Legacy/DeprecatedCore.php',
                'evolutioncms\\legacy\\errorhandler' => '/src/Legacy/ErrorHandler.php',
                'evolutioncms\\legacy\\exportsite' => '/src/Legacy/ExportSite.php',
                'evolutioncms\\legacy\\loghandler' => '/src/Legacy/LogHandler.php',
                'evolutioncms\\legacy\\managerapi' => '/src/Legacy/ManagerApi.php',
                'evolutioncms\\legacy\\mgrresources' => '/src/Legacy/mgrResources.php',
                'evolutioncms\\legacy\\modifiers' => '/src/Legacy/Modifiers.php',
                'evolutioncms\\legacy\\modulecategoriesmanager' => '/src/Legacy/ModuleCategoriesManager.php',
                'evolutioncms\\legacy\\passwordhash' => '/src/Legacy/PasswordHash.php',
                'evolutioncms\\legacy\\permissions' => '/src/Legacy/Permissions.php',
                'evolutioncms\\legacy\\phpcompat' => '/src/Legacy/PhpCompat.php',
                'evolutioncms\\legacy\\templateparser' => '/src/Legacy/TemplateParser.php',
                'evolutioncms\\mail' => '/src/Mail.php',
                'evolutioncms\\managertheme' => '/src/ManagerTheme.php',
                'evolutioncms\\serviceprovider' => '/src/ServiceProvider.php',
                'evolutioncms\\support\\captcha' => '/src/Support/Captcha.php',
                'evolutioncms\\support\\contextmenu' => '/src/Support/ContextMenu.php',
                'evolutioncms\\support\\datagrid' => '/src/Support/DataGrid.php',
                'evolutioncms\\support\\datasetpager' => '/src/Support/DataSetPager.php',
                'evolutioncms\\support\\maketable' => '/src/Support/MakeTable.php',
                'evolutioncms\\support\\menu' => '/src/Support/Menu.php',
                'evolutioncms\\support\\mysqldumper' => '/src/Support/MysqlDumper.php',
                'evolutioncms\\support\\paginate' => '/src/Support/Paginate.php',
                'phpmailer\\phpmailer\\exception' => '/controls/phpmailer/Exception.php',
                'phpmailer\\phpmailer\\oauth' => '/controls/phpmailer/OAuth.php',
                'phpmailer\\phpmailer\\phpmailer' => '/controls/phpmailer/PHPMailer.php',
                'phpmailer\\phpmailer\\pop3' => '/controls/phpmailer/POP3.php',
                'phpmailer\\phpmailer\\smtp' => '/controls/phpmailer/SMTP.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);

global $site_sessionname;

require_once 'document.parser.class.inc.php';

require_once 'functions/actions/bkmanager.php';
require_once 'functions/actions/files.php';
require_once 'functions/actions/help.php';
require_once 'functions/actions/import.php';
require_once 'functions/actions/logging.php';
require_once 'functions/actions/mutate_content.php';
require_once 'functions/actions/mutate_plugin.php';
require_once 'functions/actions/mutate_role.php';
require_once 'functions/actions/search.php';
require_once 'functions/actions/settings.php';

require_once 'functions/helper.php';
require_once 'functions/preload.php';
require_once 'functions/tv.php';
require_once 'functions/nodes.php';
require_once 'functions/processors.php';

$site_sessionname = genEvoSessionName(); // For legacy extras not using startCMSSession
