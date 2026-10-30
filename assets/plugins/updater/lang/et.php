<?php
/**
 * Evolution CMS et.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Estonian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

$_lang['pluginname'] = 'Uuendaja';
$_lang['system_update'] = 'Saadaval on uus CMS-i versioon';
$_lang['are_you_sure_update'] = 'Kas soovite kindlasti süsteemi uuendada?';
$_lang["cms_outdated_msg"] = 'Sisuhaldussüsteem on aegunud. Uuendamiseks võtke ühendust veebisaidi arendajatega. Praegune versioon';
$_lang['bkp_before_msg'] = 'Soovitame tungivalt enne süsteemi uuendamist varukoopia teha. Uuendamine toimub teie enda vastutusel !!';
$_lang['updateButton_txt'] = 'Uuenda versioonile';
$_lang['updateButtonCommit_txt'] = 'Uuenda sellele commit-ile';
$_lang['table_commitdate'] = 'Commit-i kuupäev';
$_lang['table_titleauthor'] = 'Pealkiri (autor)';
//Error Messages
$_lang['error_curl'] = 'PHP-s tuleb lubada CURL-i funktsioon';
$_lang['error_zip'] = 'PHP-s tuleb lubada ZIP';
$_lang['error_openssl'] = 'PHP-s tuleb lubada OpenSSL-i funktsioon';
$_lang['error_overwrite'] = 'Evolution CMS-i faile ei saa üle kirjutada';
$_lang['error_failedtogetfeed'] = 'Voo hankimine ebaõnnestus';

$_lang['artisan_update'] = 'Uuendamiseks käivitage kaustas <b>core</b> konsoolikäsk: <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'Ostke Evolution CMS-i arendajatele kohvi lehel <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Hakake juba täna Evolution CMS-i fänniks ❤️!';

$_lang['updater_notice_title'] = 'Saadaval on uus CMS-i versioon';
$_lang['updater_notice_text_1'] = 'Uuendamine on vabatahtlik, kuid parandab märgatavalt stabiilsust, jõudlust ning kaitset pahavara ja DDoS-rünnakute eest.';
$_lang['updater_notice_text_2'] = 'Saate süsteemi käsitsi uuendada. See nõuab tehnilisi oskusi, mistõttu on parem jätta see süsteemiadministraatori ülesandeks.';
$_lang['updater_notice_text_3'] = 'Või võtke uuendamiseks ühendust oma arendajatega.';
$_lang['updater_versions_line'] = 'Praegune: %s -> Saadaval: %s';

$_lang['updater_severity_critical'] = 'Kriitiline';
$_lang['updater_severity_warning'] = 'Hoiatus';
$_lang['updater_severity_info'] = 'Teave';

$_lang['updater_action_release'] = 'Muudatuste logi';
$_lang['updater_action_release_all'] = 'Kõik väljalasked';
$_lang['updater_action_branch'] = 'Vaata haru/viidet';
$_lang['updater_action_branch_all'] = 'Kõik harud';
$_lang['updater_action_support'] = 'Võta ühendust arendajatega';
$_lang['updater_action_hide_day'] = 'Ära näita 1 päeva jooksul';
$_lang['updater_action_hide_today'] = 'Ära näita seda teadet täna enam';
$_lang['updater_support_hint'] = 'Tugi:';

$_lang['updater_mail_subject'] = '[EVO] Veebisaidi uuendamise taotlus %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Veebisait: %s';
$_lang['updater_mail_line_update'] = 'Uuendus: %s -> %s';
$_lang['updater_mail_line_request'] = 'Palun hinnake ajakulu ja maksumust.';

$_lang['updater_widget_title'] = 'Saadaval on uus CMS-i versioon';
$_lang['updater_badge_current'] = 'Praegune';
$_lang['updater_badge_available'] = 'Saadaval';
$_lang['updater_current_full'] = 'Täisversioon: %s';
$_lang['updater_action_support_with_email'] = 'Võta ühendust arendajatega: %s';
$_lang['updater_cli_summary'] = 'Käsitsi uuendamine (CLI)';
$_lang['updater_cli_intro'] = 'Käsitsi uuendamisel käivitage terminalis järgmine käsk:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Haru/viide';
$_lang['updater_branch_mode_notice'] = 'Arendusharu/viite uuendamise režiim on lubatud. Kasutage seda ainult testimiseks või kontrollitud hoolduseks.';
$_lang['updater_line_mode_notice'] = 'See veebisait järgib viidet %s, millel on uusi commit-e. Väljalaskele naasmiseks käivitage kaustas core käsk php artisan make:site update <release>.';
$_lang['updater_action_compare'] = 'Vaata muudatusi';
$_lang['updater_notice_backup_warning'] = 'Ärge unustage enne uuendamist varukoopiat teha.';
$_lang['updater_backup_action_label'] = 'loo varukoopia';
$_lang['updater_live_update_button'] = 'Käsitsi uuendamine (WEB)';
$_lang['updater_live_update_title'] = 'Süsteemi uuendamine';
$_lang['updater_live_update_intro'] = 'Ajastaja ja tööprotsess on saadaval, seega saab Evolution CMS uuenduse järjekorda lisada ja siin edenemist reaalajas kuvada.';
$_lang['updater_live_update_backup'] = 'Enne jätkamist looge kontrollitud varukoopia. Uuendaja muudab faile ja andmebaasi andmeid.';
$_lang['updater_live_update_backup_checkbox'] = 'Loo enne uuendamist andmebaasi varukoopia';
$_lang['updater_live_update_current'] = 'Praegune andmebaasi versioon';
$_lang['updater_live_update_current_version'] = 'praegune andmebaasi versioon';
$_lang['updater_live_update_target'] = 'Sihtversioon';
$_lang['updater_live_update_branch_target'] = 'Sihtharu/viide';
$_lang['updater_live_update_repository'] = 'Hoidla';
$_lang['updater_live_update_health'] = 'Ajastaja / tööprotsess';
$_lang['updater_live_update_confirm'] = 'Alusta uuendamist';
$_lang['updater_live_update_cancel'] = 'Tühista';
$_lang['updater_live_update_queueing'] = 'Uuenduse lisamine järjekorda...';
$_lang['updater_live_update_queued'] = 'Uuendamisülesanne on järjekorras. Tööprotsessi ootel...';
$_lang['updater_live_update_status'] = 'Olek';
$_lang['updater_live_update_step'] = 'Samm';
$_lang['updater_live_update_progress'] = 'Edenemine';
$_lang['updater_live_update_close'] = 'Sulge';
$_lang['updater_live_update_close_reload'] = 'Sulge ja laadi uuesti';
$_lang['updater_live_update_completed'] = 'Uuendamine on lõpetatud. Sulgege see aken, et haldusliides uuesti laadida ja uut versiooni kontrollida.';
$_lang['updater_live_update_response_changed'] = 'Haldusliidese vastus muutus uuendamise ajal. Sulgege see aken, et haldusliides uuesti laadida ja lõplikku olekut vaadata.';
$_lang['updater_live_update_failed'] = 'Uuendamist ei saa alustada.';
$_lang['updater_live_update_invalid_response'] = 'Haldusliides tagastas vigase uuendamisvastuse.';
