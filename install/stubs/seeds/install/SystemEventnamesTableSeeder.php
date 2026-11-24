<?php namespace EvolutionCMS\Installer\Install;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemEventnamesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('system_eventnames')->delete();
        DB::table('system_eventnames')->insert([
            0   =>
                [
                    'name'      => 'OnDocPublished',
                    'service'   => 5,
                    'groupname' => '',
                ],
            1   =>
                [
                    'name'      => 'OnDocUnPublished',
                    'service'   => 5,
                    'groupname' => '',
                ],
            2   =>
                [
                    'name'      => 'OnWebPagePrerender',
                    'service'   => 5,
                    'groupname' => '',
                ],
            3   =>
                [
                    'name'      => 'OnWebLogin',
                    'service'   => 3,
                    'groupname' => '',
                ],
            4   =>
                [
                    'name'      => 'OnBeforeWebLogout',
                    'service'   => 3,
                    'groupname' => '',
                ],
            5   =>
                [
                    'name'      => 'OnWebLogout',
                    'service'   => 3,
                    'groupname' => '',
                ],
            6   =>
                [
                    'name'      => 'OnWebSaveUser',
                    'service'   => 3,
                    'groupname' => '',
                ],
            7   =>
                [
                    'name'      => 'OnWebDeleteUser',
                    'service'   => 3,
                    'groupname' => '',
                ],
            8   =>
                [
                    'name'      => 'OnWebChangePassword',
                    'service'   => 3,
                    'groupname' => '',
                ],
            9   =>
                [
                    'name'      => 'OnWebCreateGroup',
                    'service'   => 3,
                    'groupname' => '',
                ],
            10  =>
                [
                    'name'      => 'OnManagerLogin',
                    'service'   => 2,
                    'groupname' => '',
                ],
            11  =>
                [
                    'name'      => 'OnBeforeManagerLogout',
                    'service'   => 2,
                    'groupname' => '',
                ],
            12  =>
                [
                    'name'      => 'OnManagerLogout',
                    'service'   => 2,
                    'groupname' => '',
                ],
            13  =>
                [
                    'name'      => 'OnManagerSaveUser',
                    'service'   => 2,
                    'groupname' => '',
                ],
            14  =>
                [
                    'name'      => 'OnManagerDeleteUser',
                    'service'   => 2,
                    'groupname' => '',
                ],
            15  =>
                [
                    'name'      => 'OnManagerChangePassword',
                    'service'   => 2,
                    'groupname' => '',
                ],
            16  =>
                [
                    'name'      => 'OnManagerCreateGroup',
                    'service'   => 2,
                    'groupname' => '',
                ],
            17  =>
                [
                    'name'      => 'OnBeforeCacheUpdate',
                    'service'   => 4,
                    'groupname' => '',
                ],
            18  =>
                [
                    'name'      => 'OnCacheUpdate',
                    'service'   => 4,
                    'groupname' => '',
                ],
            19  =>
                [
                    'name'      => 'OnMakePageCacheKey',
                    'service'   => 4,
                    'groupname' => '',
                ],
            20  =>
                [
                    'name'      => 'OnLoadWebPageCache',
                    'service'   => 4,
                    'groupname' => '',
                ],
            21  =>
                [
                    'name'      => 'OnBeforeSaveWebPageCache',
                    'service'   => 4,
                    'groupname' => '',
                ],
            22  =>
                [
                    'name'      => 'OnChunkFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            23  =>
                [
                    'name'      => 'OnChunkFormRender',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            24  =>
                [
                    'name'      => 'OnBeforeChunkFormSave',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            25  =>
                [
                    'name'      => 'OnChunkFormSave',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            26  =>
                [
                    'name'      => 'OnBeforeChunkFormDelete',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            27  =>
                [
                    'name'      => 'OnChunkFormDelete',
                    'service'   => 1,
                    'groupname' => 'Chunks',
                ],
            28  =>
                [
                    'name'      => 'OnDocFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            29  =>
                [
                    'name'      => 'OnDocFormRender',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            30  =>
                [
                    'name'      => 'OnBeforeDocFormSave',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            31  =>
                [
                    'name'      => 'OnDocFormSave',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            32  =>
                [
                    'name'      => 'OnBeforeDocFormDelete',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            33  =>
                [
                    'name'      => 'OnDocFormDelete',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            34  =>
                [
                    'name'      => 'OnDocFormUnDelete',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            35  =>
                [
                    'name'      => 'onBeforeMoveDocument',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            36  =>
                [
                    'name'      => 'onAfterMoveDocument',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            37  =>
                [
                    'name'      => 'OnPluginFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            38  =>
                [
                    'name'      => 'OnPluginFormRender',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            39  =>
                [
                    'name'      => 'OnBeforePluginFormSave',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            40  =>
                [
                    'name'      => 'OnPluginFormSave',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            41  =>
                [
                    'name'      => 'OnBeforePluginFormDelete',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            42  =>
                [
                    'name'      => 'OnPluginFormDelete',
                    'service'   => 1,
                    'groupname' => 'Plugins',
                ],
            43  =>
                [
                    'name'      => 'OnSnipFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            44  =>
                [
                    'name'      => 'OnSnipFormRender',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            45  =>
                [
                    'name'      => 'OnBeforeSnipFormSave',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            46  =>
                [
                    'name'      => 'OnSnipFormSave',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            47  =>
                [
                    'name'      => 'OnBeforeSnipFormDelete',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            48  =>
                [
                    'name'      => 'OnSnipFormDelete',
                    'service'   => 1,
                    'groupname' => 'Snippets',
                ],
            49  =>
                [
                    'name'      => 'OnTempFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            50  =>
                [
                    'name'      => 'OnTempFormRender',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            51  =>
                [
                    'name'      => 'OnBeforeTempFormSave',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            52  =>
                [
                    'name'      => 'OnTempFormSave',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            53  =>
                [
                    'name'      => 'OnBeforeTempFormDelete',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            54  =>
                [
                    'name'      => 'OnTempFormDelete',
                    'service'   => 1,
                    'groupname' => 'Templates',
                ],
            55  =>
                [
                    'name'      => 'OnTVFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            56  =>
                [
                    'name'      => 'OnTVFormRender',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            57  =>
                [
                    'name'      => 'OnBeforeTVFormSave',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            58  =>
                [
                    'name'      => 'OnTVFormSave',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            59  =>
                [
                    'name'      => 'OnBeforeTVFormDelete',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            60  =>
                [
                    'name'      => 'OnTVFormDelete',
                    'service'   => 1,
                    'groupname' => 'Template Variables',
                ],
            61  =>
                [
                    'name'      => 'OnUserFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            62  =>
                [
                    'name'      => 'OnUserFormRender',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            63  =>
                [
                    'name'      => 'OnBeforeUserSave',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            64  =>
                [
                    'name'      => 'OnUserSave',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            65  =>
                [
                    'name'      => 'OnBeforeUserDelete',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            66  =>
                [
                    'name'      => 'OnUserDelete',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
            73  =>
                [
                    'name'      => 'OnSiteRefresh',
                    'service'   => 1,
                    'groupname' => '',
                ],
            74  =>
                [
                    'name'      => 'OnFileManagerUpload',
                    'service'   => 1,
                    'groupname' => '',
                ],
            75  =>
                [
                    'name'      => 'OnModFormPrerender',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            76  =>
                [
                    'name'      => 'OnModFormRender',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            77  =>
                [
                    'name'      => 'OnBeforeModFormDelete',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            78  =>
                [
                    'name'      => 'OnModFormDelete',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            79  =>
                [
                    'name'      => 'OnBeforeModFormSave',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            80  =>
                [
                    'name'      => 'OnModFormSave',
                    'service'   => 1,
                    'groupname' => 'Modules',
                ],
            81  =>
                [
                    'name'      => 'OnBeforeWebLogin',
                    'service'   => 3,
                    'groupname' => '',
                ],
            82  =>
                [
                    'name'      => 'OnWebAuthentication',
                    'service'   => 3,
                    'groupname' => '',
                ],
            83  =>
                [
                    'name'      => 'OnBeforeManagerLogin',
                    'service'   => 2,
                    'groupname' => '',
                ],
            84  =>
                [
                    'name'      => 'OnManagerAuthentication',
                    'service'   => 2,
                    'groupname' => '',
                ],
            85  =>
                [
                    'name'      => 'OnSiteSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            86  =>
                [
                    'name'      => 'OnFriendlyURLSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            87  =>
                [
                    'name'      => 'OnUserSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            88  =>
                [
                    'name'      => 'OnInterfaceSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            89  =>
                [
                    'name'      => 'OnSecuritySettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            90  =>
                [
                    'name'      => 'OnFileManagerSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            91  =>
                [
                    'name'      => 'OnMiscSettingsRender',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            92  =>
                [
                    'name'      => 'OnRichTextEditorRegister',
                    'service'   => 1,
                    'groupname' => 'RichText Editor',
                ],
            93  =>
                [
                    'name'      => 'OnRichTextEditorInit',
                    'service'   => 1,
                    'groupname' => 'RichText Editor',
                ],
            94  =>
                [
                    'name'      => 'OnManagerPageInit',
                    'service'   => 2,
                    'groupname' => '',
                ],
            95  =>
                [
                    'name'      => 'OnWebPageInit',
                    'service'   => 5,
                    'groupname' => '',
                ],
            96  =>
                [
                    'name'      => 'OnLoadDocumentObject',
                    'service'   => 5,
                    'groupname' => '',
                ],
            97  =>
                [
                    'name'      => 'OnBeforeLoadDocumentObject',
                    'service'   => 5,
                    'groupname' => '',
                ],
            98  =>
                [
                    'name'      => 'OnAfterLoadDocumentObject',
                    'service'   => 5,
                    'groupname' => '',
                ],
            99  =>
                [
                    'name'      => 'OnLoadWebDocument',
                    'service'   => 5,
                    'groupname' => '',
                ],
            100 =>
                [
                    'name'      => 'OnParseDocument',
                    'service'   => 5,
                    'groupname' => '',
                ],
            101 =>
                [
                    'name'      => 'OnParseProperties',
                    'service'   => 5,
                    'groupname' => '',
                ],
            102 =>
                [
                    'name'      => 'OnBeforeParseParams',
                    'service'   => 5,
                    'groupname' => '',
                ],
            103 =>
                [
                    'name'      => 'OnManagerLoginFormRender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            104 =>
                [
                    'name'      => 'OnWebPageComplete',
                    'service'   => 5,
                    'groupname' => '',
                ],
            105 =>
                [
                    'name'      => 'OnLogPageHit',
                    'service'   => 5,
                    'groupname' => '',
                ],
            106 =>
                [
                    'name'      => 'OnBeforeManagerPageInit',
                    'service'   => 2,
                    'groupname' => '',
                ],
            107 =>
                [
                    'name'      => 'OnBeforeEmptyTrash',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            108 =>
                [
                    'name'      => 'OnEmptyTrash',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            109 =>
                [
                    'name'      => 'OnManagerLoginFormPrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            110 =>
                [
                    'name'      => 'OnStripAlias',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            111 =>
                [
                    'name'      => 'OnMakeDocUrl',
                    'service'   => 5,
                    'groupname' => '',
                ],
            112 =>
                [
                    'name'      => 'OnBeforeLoadExtension',
                    'service'   => 5,
                    'groupname' => '',
                ],
            113 =>
                [
                    'name'      => 'OnCreateDocGroup',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            114 =>
                [
                    'name'      => 'OnManagerWelcomePrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            115 =>
                [
                    'name'      => 'OnManagerWelcomeHome',
                    'service'   => 2,
                    'groupname' => '',
                ],
            116 =>
                [
                    'name'      => 'OnManagerWelcomeRender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            117 =>
                [
                    'name'      => 'OnBeforeDocDuplicate',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            118 =>
                [
                    'name'      => 'OnDocDuplicate',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            119 =>
                [
                    'name'      => 'OnManagerMainFrameHeaderHTMLBlock',
                    'service'   => 2,
                    'groupname' => '',
                ],
            120 =>
                [
                    'name'      => 'OnManagerPreFrameLoader',
                    'service'   => 2,
                    'groupname' => '',
                ],
            121 =>
                [
                    'name'      => 'OnManagerFrameLoader',
                    'service'   => 2,
                    'groupname' => '',
                ],
            122 =>
                [
                    'name'      => 'OnManagerTreeInit',
                    'service'   => 2,
                    'groupname' => '',
                ],
            123 =>
                [
                    'name'      => 'OnManagerTreePrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            124 =>
                [
                    'name'      => 'OnManagerTreeRender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            125 =>
                [
                    'name'      => 'OnManagerNodePrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            126 =>
                [
                    'name'      => 'OnManagerNodeRender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            127 =>
                [
                    'name'      => 'OnManagerMenuPrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            128 =>
                [
                    'name'      => 'OnManagerTopPrerender',
                    'service'   => 2,
                    'groupname' => '',
                ],
            129 =>
                [
                    'name'      => 'OnDocFormTemplateRender',
                    'service'   => 1,
                    'groupname' => 'Documents',
                ],
            130 =>
                [
                    'name'      => 'OnBeforeMinifyCss',
                    'service'   => 1,
                    'groupname' => '',
                ],
            131 =>
                [
                    'name'      => 'OnPageUnauthorized',
                    'service'   => 1,
                    'groupname' => '',
                ],
            132 =>
                [
                    'name'      => 'OnPageNotFound',
                    'service'   => 1,
                    'groupname' => '',
                ],
            133 =>
                [
                    'name'      => 'OnFileBrowserUpload',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            134 =>
                [
                    'name'      => 'OnBeforeFileBrowserUpload',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            135 =>
                [
                    'name'      => 'OnFileBrowserDelete',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            136 =>
                [
                    'name'      => 'OnBeforeFileBrowserDelete',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            137 =>
                [
                    'name'      => 'OnFileBrowserInit',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            138 =>
                [
                    'name'      => 'OnFileBrowserMove',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            139 =>
                [
                    'name'      => 'OnBeforeFileBrowserMove',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            140 =>
                [
                    'name'      => 'OnFileBrowserCopy',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            141 =>
                [
                    'name'      => 'OnBeforeFileBrowserCopy',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            142 =>
                [
                    'name'      => 'OnBeforeFileBrowserRename',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            143 =>
                [
                    'name'      => 'OnFileBrowserRename',
                    'service'   => 1,
                    'groupname' => 'File Browser Events',
                ],
            144 =>
                [
                    'name'      => 'OnLogEvent',
                    'service'   => 1,
                    'groupname' => 'Log Event',
                ],
            145 =>
                [
                    'name'      => 'OnLoadSettings',
                    'service'   => 1,
                    'groupname' => 'System Settings',
                ],
            146 =>
                [
                    'name'      => 'OnTwoFactorMethods',
                    'service'   => 1,
                    'groupname' => 'Users',
                ],
        ]);
    }
}
