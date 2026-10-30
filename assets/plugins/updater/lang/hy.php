<?php
/**
 * Evolution CMS hy.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Armenian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

// ---------------------------------------------------------------
// :: Թարմացնող
// ----------------------------------------------------------------
//
//
//
// ----------------------------------------------------------------
// :: Հեղինակային իրավունք և արտոնագրում
// ----------------------------------------------------------------
//
//   GNU ընդհանուր հանրային արտոնագիր (GPL - http://www.gnu.org/copyleft/gpl.html)
//

$_lang['pluginname'] = 'Թարմացնող';
$_lang['system_update'] = 'Հասանելի է CMS-ի նոր տարբերակ';
$_lang['are_you_sure_update'] = 'Վստա՞հ եք, որ ցանկանում եք գործարկել համակարգի թարմացումը։';
$_lang["cms_outdated_msg"] = 'Բովանդակության կառավարման համակարգը հնացած է։ Թարմացնելու համար դիմեք կայքի մշակողներին։ Ընթացիկ տարբերակը';
$_lang['bkp_before_msg'] = 'Խստորեն խորհուրդ ենք տալիս համակարգը թարմացնելուց առաջ ստեղծել պահուստային պատճեն․ թարմացումը կատարվում է ձեր պատասխանատվությամբ !!';
$_lang['updateButton_txt'] = 'Թարմացնել մինչև տարբերակը';
$_lang['updateButtonCommit_txt'] = 'Թարմացնել մինչև այս փոփոխությունների ամրագրումը';
$_lang['table_commitdate'] = 'Փոփոխությունների ամրագրման ամսաթիվ';
$_lang['table_titleauthor'] = 'Վերնագիր (հեղինակ)';
// Սխալի հաղորդագրություններ
$_lang['error_curl'] = 'Անհրաժեշտ է PHP-ում միացնել CURL գործառույթը';
$_lang['error_zip'] = 'Անհրաժեշտ է PHP-ում միացնել ZIP-ը';
$_lang['error_openssl'] = 'Անհրաժեշտ է PHP-ում միացնել OpenSSL գործառույթը';
$_lang['error_overwrite'] = 'Evolution CMS-ի ֆայլերը հասանելի չեն վերագրելու համար';
$_lang['error_failedtogetfeed'] = 'Չհաջողվեց ստանալ լրահոսը';

$_lang['artisan_update'] = 'Թարմացնելու համար <b>core</b> պանակից գործարկեք վահանակի հրամանը՝ <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'Սուրճ հյուրասիրեք Evolution CMS-ի մշակողներին <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a> կայքում։ Դարձեք Evolution CMS-ի երկրպագու ❤️ հենց այսօր։';

$_lang['updater_notice_title'] = 'Հասանելի է CMS-ի նոր տարբերակ';
$_lang['updater_notice_text_1'] = 'Թարմացումը պարտադիր չէ, սակայն այն զգալիորեն բարելավում է կայունությունը, կատարողականությունը և պաշտպանությունը վնասաբեր ծրագրերից ու DDoS հարձակումներից։';
$_lang['updater_notice_text_2'] = 'Դուք կարող եք համակարգը թարմացնել ձեռքով։ Գործընթացը պահանջում է տեխնիկական հմտություններ, ուստի ավելի լավ է այն վստահել համակարգային ադմինիստրատորին։';
$_lang['updater_notice_text_3'] = 'Կամ թարմացումն իրականացնելու համար դիմեք ձեր մշակողներին։';
$_lang['updater_versions_line'] = 'Ընթացիկ՝ %s -> Հասանելի՝ %s';

$_lang['updater_severity_critical'] = 'Կրիտիկական';
$_lang['updater_severity_warning'] = 'Զգուշացում';
$_lang['updater_severity_info'] = 'Տեղեկություն';

$_lang['updater_action_release'] = 'Փոփոխությունների մատյան';
$_lang['updater_action_release_all'] = 'Բոլոր թողարկումները';
$_lang['updater_action_branch'] = 'Դիտել ճյուղը/հղումը';
$_lang['updater_action_branch_all'] = 'Բոլոր ճյուղերը';
$_lang['updater_action_support'] = 'Կապվել մշակողների հետ';
$_lang['updater_action_hide_day'] = 'Չցուցադրել 1 օր';
$_lang['updater_action_hide_today'] = 'Այսօր այլևս չցուցադրել այս հաղորդագրությունը';
$_lang['updater_support_hint'] = 'Աջակցություն՝';

$_lang['updater_mail_subject'] = '[EVO] Կայքի թարմացման հարցում %s՝ %s -> %s';
$_lang['updater_mail_line_site'] = 'Կայք՝ %s';
$_lang['updater_mail_line_update'] = 'Թարմացում՝ %s -> %s';
$_lang['updater_mail_line_request'] = 'Խնդրում ենք գնահատել ժամկետը և արժեքը։';

$_lang['updater_widget_title'] = 'Հասանելի է CMS-ի նոր տարբերակ';
$_lang['updater_badge_current'] = 'Ընթացիկ';
$_lang['updater_badge_available'] = 'Հասանելի';
$_lang['updater_current_full'] = 'Ամբողջական տարբերակ՝ %s';
$_lang['updater_action_support_with_email'] = 'Կապվել մշակողների հետ՝ %s';
$_lang['updater_cli_summary'] = 'Ձեռքով թարմացում (CLI)';
$_lang['updater_cli_intro'] = 'Եթե թարմացնում եք ձեռքով, տերմինալում գործարկեք այս հրամանը՝';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Ճյուղ/հղում';
$_lang['updater_branch_mode_notice'] = 'Միացված է մշակման ճյուղի/հղման թարմացման ռեժիմը։ Օգտագործեք այն միայն փորձարկման կամ վերահսկվող սպասարկման համար։';
$_lang['updater_line_mode_notice'] = 'Այս կայքը հետևում է %s-ին, որն ունի նոր փոփոխությունների ամրագրումներ։ Թողարկմանը վերադառնալու համար core պանակում գործարկեք php artisan make:site update <release> հրամանը։';
$_lang['updater_action_compare'] = 'Դիտել փոփոխությունները';
$_lang['updater_notice_backup_warning'] = 'Մի մոռացեք թարմացնելուց առաջ ստեղծել պահուստային պատճեն։';
$_lang['updater_backup_action_label'] = 'ստեղծել պահուստային պատճեն';
$_lang['updater_live_update_button'] = 'Ձեռքով թարմացում (WEB)';
$_lang['updater_live_update_title'] = 'Համակարգի թարմացում';
$_lang['updater_live_update_intro'] = 'Առաջադրանքների ժամանակացույցը և կատարողը հասանելի են, ուստի Evolution CMS-ը կարող է թարմացումը հերթագրել և այստեղ ցուցադրել ընթացքը իրական ժամանակում։';
$_lang['updater_live_update_backup'] = 'Շարունակելուց առաջ ստեղծեք ստուգված պահուստային պատճեն։ Թարմացնողը փոխում է ֆայլերն ու տվյալների բազայի տվյալները։';
$_lang['updater_live_update_backup_checkbox'] = 'Թարմացնելուց առաջ ստեղծել տվյալների բազայի պահուստային պատճեն';
$_lang['updater_live_update_current'] = 'Տվյալների բազայի ընթացիկ տարբերակը';
$_lang['updater_live_update_current_version'] = 'տվյալների բազայի ընթացիկ տարբերակը';
$_lang['updater_live_update_target'] = 'Նպատակային տարբերակ';
$_lang['updater_live_update_branch_target'] = 'Նպատակային ճյուղ/հղում';
$_lang['updater_live_update_repository'] = 'Պահոց';
$_lang['updater_live_update_health'] = 'Ժամանակացույց / կատարող';
$_lang['updater_live_update_confirm'] = 'Սկսել թարմացումը';
$_lang['updater_live_update_cancel'] = 'Չեղարկել';
$_lang['updater_live_update_queueing'] = 'Թարմացումը հերթագրվում է...';
$_lang['updater_live_update_queued'] = 'Թարմացման առաջադրանքը հերթագրված է։ Սպասում է կատարողին...';
$_lang['updater_live_update_status'] = 'Վիճակ';
$_lang['updater_live_update_step'] = 'Քայլ';
$_lang['updater_live_update_progress'] = 'Առաջընթաց';
$_lang['updater_live_update_close'] = 'Փակել';
$_lang['updater_live_update_close_reload'] = 'Փակել և վերաբեռնել';
$_lang['updater_live_update_completed'] = 'Թարմացումն ավարտված է։ Փակեք այս պատուհանը՝ կառավարման վահանակը վերաբեռնելու և նոր տարբերակը ստուգելու համար։';
$_lang['updater_live_update_response_changed'] = 'Թարմացման ընթացքում կառավարման վահանակի պատասխանը փոխվել է։ Փակեք այս պատուհանը՝ կառավարման վահանակը վերաբեռնելու և վերջնական վիճակը կարդալու համար։';
$_lang['updater_live_update_failed'] = 'Չհաջողվեց սկսել թարմացումը։';
$_lang['updater_live_update_invalid_response'] = 'Կառավարման վահանակը թարմացման անվավեր պատասխան է վերադարձրել։';
