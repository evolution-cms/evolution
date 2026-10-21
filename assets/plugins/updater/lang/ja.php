<?php
// ---------------------------------------------------------------
// :: Updater
// ----------------------------------------------------------------
//
//
//
// ----------------------------------------------------------------
// :: Copyright & Licencing
// ----------------------------------------------------------------
//
//   GNU General Public License (GPL - http://www.gnu.org/copyleft/gpl.html)
//

$_lang['pluginname'] = 'Updater';
$_lang['system_update'] = 'システムアップデート';
$_lang['are_you_sure_update'] = 'システムアップデートを実行してよろしいですか？';
$_lang["cms_outdated_msg"] = 'CMSが古いバージョンです。更新するにはサイト開発者に連絡してください。現在のバージョン';
$_lang['bkp_before_msg'] = 'システムをアップグレードする前にバックアップを作成することを強くお勧めします。アップデートはご自身の責任で行ってください。';
$_lang['updateButton_txt'] = 'アップデートするバージョン';
$_lang['updateButtonCommit_txt'] = 'このコミットに更新';
$_lang['table_commitdate'] = 'コミット日';
$_lang['table_titleauthor'] = 'タイトル (作成者)';
//Error Messages
$_lang['error_curl'] = 'PHPでCURL機能を有効にする必要があります';
$_lang['error_zip'] = 'PHPでZIPを有効にする必要があります';
$_lang['error_openssl'] = 'あなたはPHPでOpenSSL機能を有効にする必要があります';
$_lang['error_overwrite'] = 'EVOのファイルは上書きできません';
$_lang['error_failedtogetfeed'] = 'フィードを取得できませんでした';

$_lang['artisan_update'] = '更新するには、フォルダからコンソールコマンドを実行する必要があります <b>core</b>: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = '更新前にサイトのバックアップを作成することを忘れないでください。';
$_lang['updater_backup_action_label'] = 'サイトのバックアップを作成';
$_lang['updater_live_update_button'] = '手動更新 (WEB)';
$_lang['updater_line_mode_notice'] = 'このサイトは %s を追跡しており、新しいコミットがあります。リリースに戻すには、core フォルダで php artisan make:site update <release> を実行してください。';
$_lang['updater_action_compare'] = '変更を表示';
$_lang["help_donate_msg"] = '<a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a> で、Evolution CMS 開発者からコーヒーを購入できます。今すぐ Evolution CMS ❤️ のファンになりましょう!';

$_lang['updater_notice_title'] = 'CMSの新しいバージョンが利用可能です';
$_lang['updater_notice_text_1'] = '更新は任意ですが、安定性、パフォーマンス、およびマルウェアやDDoS攻撃に対する保護が大幅に向上します。';
$_lang['updater_notice_text_2'] = 'システムは手動でも更新できます。この作業には技術的な知識が必要なため、システム管理者に依頼することをお勧めします。';
$_lang['updater_notice_text_3'] = 'または、開発者に更新を依頼してください。';
$_lang['updater_versions_line'] = '現在：%s -> 利用可能：%s';
$_lang['updater_severity_critical'] = '重大';
$_lang['updater_severity_warning'] = '警告';
$_lang['updater_severity_info'] = '情報';
$_lang['updater_action_release'] = '変更履歴';
$_lang['updater_action_release_all'] = 'すべてのリリース';
$_lang['updater_action_branch'] = 'ブランチ/参照を表示';
$_lang['updater_action_branch_all'] = 'すべてのブランチ';
$_lang['updater_action_support'] = '開発者に問い合わせる';
$_lang['updater_action_hide_day'] = '1日間表示しない';
$_lang['updater_action_hide_today'] = '今日はこのメッセージを再表示しない';
$_lang['updater_support_hint'] = 'サポート：';
$_lang['updater_mail_subject'] = '[EVO] サイトの更新依頼 %s：%s -> %s';
$_lang['updater_mail_line_site'] = 'サイト：%s';
$_lang['updater_mail_line_update'] = '更新：%s -> %s';
$_lang['updater_mail_line_request'] = '所要時間と費用のお見積もりをお願いします。';
$_lang['updater_widget_title'] = 'CMSの新しいバージョンが利用可能です';
$_lang['updater_badge_current'] = '現在';
$_lang['updater_badge_available'] = '利用可能';
$_lang['updater_current_full'] = '完全なバージョン：%s';
$_lang['updater_action_support_with_email'] = '開発者に問い合わせる：%s';
$_lang['updater_cli_summary'] = '手動更新 (CLI)';
$_lang['updater_cli_intro'] = '手動で更新する場合は、ターミナルで次のコマンドを実行してください：';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'ブランチ/参照';
$_lang['updater_branch_mode_notice'] = '開発ブランチ/参照の更新モードが有効です。テストまたは管理下でのメンテナンスにのみ使用してください。';
$_lang['updater_live_update_title'] = 'システムの更新';
$_lang['updater_live_update_intro'] = 'スケジューラーとワーカーが利用可能なため、Evolution CMSは更新をキューに登録し、ここに進行状況をリアルタイムで表示できます。';
$_lang['updater_live_update_backup'] = '続行する前に、検証済みのバックアップを作成してください。更新によりファイルとデータベースのデータが変更されます。';
$_lang['updater_live_update_backup_checkbox'] = '更新前にデータベースのバックアップを作成する';
$_lang['updater_live_update_current'] = '現在のデータベースバージョン';
$_lang['updater_live_update_current_version'] = '現在のデータベースバージョン';
$_lang['updater_live_update_target'] = '更新先のバージョン';
$_lang['updater_live_update_branch_target'] = '更新先のブランチ/参照';
$_lang['updater_live_update_repository'] = 'リポジトリ';
$_lang['updater_live_update_health'] = 'スケジューラー / ワーカー';
$_lang['updater_live_update_confirm'] = '更新を開始';
$_lang['updater_live_update_cancel'] = 'キャンセル';
$_lang['updater_live_update_queueing'] = '更新をキューに登録しています...';
$_lang['updater_live_update_queued'] = '更新タスクをキューに登録しました。ワーカーを待機しています...';
$_lang['updater_live_update_status'] = '状態';
$_lang['updater_live_update_step'] = '手順';
$_lang['updater_live_update_progress'] = '進行状況';
$_lang['updater_live_update_close'] = '閉じる';
$_lang['updater_live_update_close_reload'] = '閉じて再読み込み';
$_lang['updater_live_update_completed'] = '更新が完了しました。このウィンドウを閉じて管理画面を再読み込みし、新しいバージョンを確認してください。';
$_lang['updater_live_update_response_changed'] = '更新中に管理画面の応答が変わりました。このウィンドウを閉じて管理画面を再読み込みし、最終的な状態を確認してください。';
$_lang['updater_live_update_failed'] = '更新を開始できませんでした。';
$_lang['updater_live_update_invalid_response'] = '管理画面から無効な更新応答が返されました。';
