<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the `system_eventnames` table for a fresh installation.
 *
 * Safety: this seeder is idempotent and will not duplicate/overwrite existing rows.
 * If the `system_eventnames` table already contains any rows, it does nothing.
 */
class SystemEventnamesTableSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     *
     * Inserts the default system event names only when the `system_eventnames` table is empty.
     */
    public function run(): void
    {
        if (!Schema::hasTable('system_eventnames')) {
            return;
        }

        if (DB::table('system_eventnames')->count() > 0) {
            return;
        }

        DB::table('system_eventnames')->insert([
            [
                'name'      => 'OnDocPublished',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnDocUnPublished',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebPagePrerender',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebLogin',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeWebLogout',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebLogout',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebSaveUser',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebDeleteUser',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebChangePassword',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebCreateGroup',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerLogin',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeManagerLogout',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerLogout',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerSaveUser',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerDeleteUser',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerChangePassword',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerCreateGroup',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeCacheUpdate',
                'service'   => 4,
                'groupname' => '',
            ],
            [
                'name'      => 'OnCacheUpdate',
                'service'   => 4,
                'groupname' => '',
            ],
            [
                'name'      => 'OnMakePageCacheKey',
                'service'   => 4,
                'groupname' => '',
            ],
            [
                'name'      => 'OnLoadWebPageCache',
                'service'   => 4,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeSaveWebPageCache',
                'service'   => 4,
                'groupname' => '',
            ],
            [
                'name'      => 'OnChunkFormPrerender',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnChunkFormRender',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnBeforeChunkFormSave',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnChunkFormSave',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnBeforeChunkFormDelete',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnChunkFormDelete',
                'service'   => 1,
                'groupname' => 'Chunks',
            ],
            [
                'name'      => 'OnDocFormPrerender',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnDocFormRender',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnBeforeDocFormSave',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnDocFormSave',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnBeforeDocFormDelete',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnDocFormDelete',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnDocFormUnDelete',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'onBeforeMoveDocument',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'onAfterMoveDocument',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnPluginFormPrerender',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnPluginFormRender',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnBeforePluginFormSave',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnPluginFormSave',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnBeforePluginFormDelete',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnPluginFormDelete',
                'service'   => 1,
                'groupname' => 'Plugins',
            ],
            [
                'name'      => 'OnSnipFormPrerender',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnSnipFormRender',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnBeforeSnipFormSave',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnSnipFormSave',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnBeforeSnipFormDelete',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnSnipFormDelete',
                'service'   => 1,
                'groupname' => 'Snippets',
            ],
            [
                'name'      => 'OnTempFormPrerender',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnTempFormRender',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnBeforeTempFormSave',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnTempFormSave',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnBeforeTempFormDelete',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnTempFormDelete',
                'service'   => 1,
                'groupname' => 'Templates',
            ],
            [
                'name'      => 'OnTVFormPrerender',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnTVFormRender',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnBeforeTVFormSave',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnTVFormSave',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnBeforeTVFormDelete',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnTVFormDelete',
                'service'   => 1,
                'groupname' => 'Template Variables',
            ],
            [
                'name'      => 'OnUserFormPrerender',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnUserFormRender',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnBeforeUserSave',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnUserSave',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnBeforeUserDelete',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnUserDelete',
                'service'   => 1,
                'groupname' => 'Users',
            ],
            [
                'name'      => 'OnSiteRefresh',
                'service'   => 1,
                'groupname' => '',
            ],
            [
                'name'      => 'OnFileManagerUpload',
                'service'   => 1,
                'groupname' => '',
            ],
            [
                'name'      => 'OnModFormPrerender',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnModFormRender',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnBeforeModFormDelete',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnModFormDelete',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnBeforeModFormSave',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnModFormSave',
                'service'   => 1,
                'groupname' => 'Modules',
            ],
            [
                'name'      => 'OnBeforeWebLogin',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebAuthentication',
                'service'   => 3,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeManagerLogin',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerAuthentication',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnSiteSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnFriendlyURLSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnUserSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnInterfaceSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnSecuritySettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnFileManagerSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnMiscSettingsRender',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
            [
                'name'      => 'OnRichTextEditorRegister',
                'service'   => 1,
                'groupname' => 'RichText Editor',
            ],
            [
                'name'      => 'OnRichTextEditorInit',
                'service'   => 1,
                'groupname' => 'RichText Editor',
            ],
            [
                'name'      => 'OnManagerPageInit',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebPageInit',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnLoadDocumentObject',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeLoadDocumentObject',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnAfterLoadDocumentObject',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnLoadWebDocument',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnParseDocument',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnParseProperties',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeParseParams',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerLoginFormRender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnWebPageComplete',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnLogPageHit',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeManagerPageInit',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeEmptyTrash',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnEmptyTrash',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnManagerLoginFormPrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnStripAlias',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnMakeDocUrl',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeLoadExtension',
                'service'   => 5,
                'groupname' => '',
            ],
            [
                'name'      => 'OnCreateDocGroup',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnManagerWelcomePrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerWelcomeHome',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerWelcomeRender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnBeforeDocDuplicate',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnDocDuplicate',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnManagerMainFrameHeaderHTMLBlock',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerPreFrameLoader',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerFrameLoader',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerTreeInit',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerTreePrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerTreeRender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerNodePrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerNodeRender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerMenuPrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnManagerTopPrerender',
                'service'   => 2,
                'groupname' => '',
            ],
            [
                'name'      => 'OnDocFormTemplateRender',
                'service'   => 1,
                'groupname' => 'Documents',
            ],
            [
                'name'      => 'OnBeforeMinifyCss',
                'service'   => 1,
                'groupname' => '',
            ],
            [
                'name'      => 'OnPageUnauthorized',
                'service'   => 1,
                'groupname' => '',
            ],
            [
                'name'      => 'OnPageNotFound',
                'service'   => 1,
                'groupname' => '',
            ],
            [
                'name'      => 'OnFileBrowserUpload',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnBeforeFileBrowserUpload',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnFileBrowserDelete',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnBeforeFileBrowserDelete',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnFileBrowserInit',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnFileBrowserMove',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnBeforeFileBrowserMove',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnFileBrowserCopy',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnBeforeFileBrowserCopy',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnBeforeFileBrowserRename',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnFileBrowserRename',
                'service'   => 1,
                'groupname' => 'File Browser Events',
            ],
            [
                'name'      => 'OnLogEvent',
                'service'   => 1,
                'groupname' => 'Log Event',
            ],
            [
                'name'      => 'OnLoadSettings',
                'service'   => 1,
                'groupname' => 'System Settings',
            ],
        ]);
    }
}
