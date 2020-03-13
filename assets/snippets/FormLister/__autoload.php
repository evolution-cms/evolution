<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
$loader = function($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'APIhelpers' => '/../../lib/APIHelpers.class.php',
            'AssetsHelper' => '/../../lib/Helpers/Assets.php',
            'DLTemplate' => '/../DocLister/lib/DLTemplate.class.php',
            'DLphx' => '/../DocLister/lib/DLphx.class.php',
            'DrewM\\MailChimp\\Batch' => '/lib/MailChimp/Batch.php',
            'DrewM\\MailChimp\\MailChimp' => '/lib/MailChimp/MailChimp.php',
            'FormLister\\Activate' => '/core/controller/Activate.php',
            'FormLister\\CaptchaInterface' => '/lib/captcha/Captcha.php',
            'FormLister\\Content' => '/core/controller/Content.php',
            'FormLister\\Core' => '/core/FormLister.abstract.php',
            'FormLister\\DateConverter' => '/lib/DateConverter.php',
            'FormLister\\DeleteContent' => '/core/controller/DeleteContent.php',
            'FormLister\\DeleteUser' => '/core/controller/DeleteUser.php',
            'FormLister\\FileValidator' => '/lib/FileValidator.php',
            'FormLister\\Filters' => '/lib/Filters.php',
            'FormLister\\Form' => '/core/controller/Form.php',
            'FormLister\\Login' => '/core/controller/Login.php',
            'FormLister\\MailChimp' => '/core/controller/MailChimp.php',
            'FormLister\\Profile' => '/core/controller/Profile.php',
            'FormLister\\Register' => '/core/controller/Register.php',
            'FormLister\\Reminder' => '/core/controller/Reminder.php',
            'FormLister\\SubmitProtection' => '/lib/SubmitProtection.php',
            'FormLister\\Validator' => '/lib/Validator.php',
            'Formatter\\HtmlFormatter' => '/../../lib/Formatter/HtmlFormatter.php',
            'Formatter\\SqlFormatter' => '/../../lib/Formatter/SqlFormatter.php',
            'Helpers\\Collection' => '/../../lib/Helpers/Collection.php',
            'Helpers\\Config' => '/../../lib/Helpers/Config.php',
            'Helpers\\Debug' => '/lib/Debug.php',
            'Helpers\\FS' => '/../../lib/Helpers/FS.php',
            'Helpers\\Gpc' => '/lib/Gpc.php',
            'Helpers\\Lexicon' => '/lib/Lexicon.php',
            'Helpers\\Lexicon\\AbstractLexiconHandler' => '/lib/LexiconHandlers/AbstractLexiconHandler.php',
            'Helpers\\Lexicon\\EvoBabelLexiconHandler' => '/lib/LexiconHandlers/EvoBabelLexiconHandler.php',
            'Helpers\\Mailer' => '/../../lib/Helpers/Mailer.php',
            'Helpers\\PHPThumb' => '/../../lib/Helpers/PHPThumb.php',
            'Helpers\\Video' => '/../../lib/Helpers/Video.php',
            'MODxAPI' => '/../../lib/MODxAPI/MODx.php',
            'MODxAPIhelpers' => '/../../lib/MODxAPI/MODx.php',
            'ModxCaptcha' => '/lib/captcha/modxCaptcha/modxCaptcha.php',
            'ModxCaptchaWrapper' => '/lib/captcha/modxCaptcha/wrapper.php',
            'ReCaptchaWrapper' => '/lib/captcha/reCaptcha/wrapper.php',
            'SmsCaptchaWrapper' => '/lib/captcha/smsCaptcha/wrapper.php',
            'SmsModel' => '/lib/captcha/smsCaptcha/model.php',
            'SummaryText' => '/../../lib/class.summary.php',
            'autoTable' => '/../../lib/MODxAPI/autoTable.abstract.php',
            'jsonHelper' => '/../DocLister/lib/jsonHelper.class.php',
            'modCategories' => '/../../lib/MODxAPI/modCategories.php',
            'modChunk' => '/../../lib/MODxAPI/modChunk.php',
            'modModule' => '/../../lib/MODxAPI/modModule.php',
            'modPlugin' => '/../../lib/MODxAPI/modPlugin.php',
            'modResource' => '/../../lib/MODxAPI/modResource.php',
            'modSnippet' => '/../../lib/MODxAPI/modSnippet.php',
            'modTV' => '/../../lib/MODxAPI/modTV.php',
            'modTemplate' => '/../../lib/MODxAPI/modTemplate.php',
            'modUsers' => '/../../lib/MODxAPI/modUsers.php',
            'sqlHelper' => '/../DocLister/lib/sqlHelper.class.php',
            'xNop' => '/../DocLister/lib/xnop.class.php'
        );
    }
    if (isset($classes[$class])) {
        require dirname(__FILE__) . $classes[$class];
    }
};
spl_autoload_register($loader, true);
// @codeCoverageIgnoreEnd
