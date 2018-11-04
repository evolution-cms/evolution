# EVO Database Script for New/Upgrade Installations
# EVO was created By Raymond Irving - Nov 2004
#
# Each sql command is separated by double lines \n\n


DROP TABLE IF EXISTS `{PREFIX}active_users`;

CREATE TABLE `{PREFIX}active_users` (
  `sid` varchar(32) NOT NULL default '',
  `internalKey` int(9) NOT NULL default '0',
  `username` varchar(50) NOT NULL default '',
  `lasthit` int(20) NOT NULL default '0',
  `action` varchar(10) NOT NULL default '',
  `id` int(10) default NULL,
  PRIMARY KEY (`sid`, `username`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data about last user action.';

DROP TABLE IF EXISTS `{PREFIX}active_user_locks`;

CREATE TABLE `{PREFIX}active_user_locks` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `sid` varchar(32) NOT NULL default '',
  `internalKey` int(9) NOT NULL default '0',
  `elementType` int(1) NOT NULL default '0',
  `elementId` int(10) NOT NULL default '0',
  `lasthit` int(20) NOT NULL default '0',
  PRIMARY KEY(`id`),
  UNIQUE INDEX ix_element_id (`elementType`,`elementId`,`sid`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data about locked elements.';

DROP TABLE IF EXISTS `{PREFIX}active_user_sessions`;

CREATE TABLE `{PREFIX}active_user_sessions` (
  `sid` varchar(32) NOT NULL default '',
  `internalKey` int(9) NOT NULL default '0',
  `lasthit` int(20) NOT NULL default '0',
  `ip` varchar(50) NOT NULL default '',
  PRIMARY KEY(`sid`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data about valid user sessions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}categories` (
  `id` integer NOT NULL AUTO_INCREMENT,
  `category` varchar(45) NOT NULL DEFAULT '',
  `rank` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY(`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Categories to be used snippets,tv,chunks, etc';

CREATE TABLE IF NOT EXISTS `{PREFIX}document_groups` (
  `id` int(10) NOT NULL auto_increment,
  `document_group` int(10) NOT NULL default '0',
  `document` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `document` (`document`),
  KEY `document_group` (`document_group`),
  UNIQUE INDEX `ix_dg_id` (`document_group`,`document`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}documentgroup_names` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(245) NOT NULL default '',
  `private_memgroup` tinyint DEFAULT 0 COMMENT 'determine whether the document group is private to manager users',
  `private_webgroup` tinyint DEFAULT 0 COMMENT 'determines whether the document is private to web users',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}event_log` (
  `id` integer NOT NULL AUTO_INCREMENT,
  `eventid` integer DEFAULT 0,
  `createdon` integer NOT NULL DEFAULT 0,
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '1- information, 2 - warning, 3- error',
  `user` integer NOT NULL DEFAULT 0 COMMENT 'link to user table',
  `usertype` tinyint NOT NULL DEFAULT 0 COMMENT '0 - manager, 1 - web',
  `source` varchar(50) NOT NULL DEFAULT '',
  `description` text,
  PRIMARY KEY(`id`),
  KEY `user`(`user`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Stores event and error logs';

CREATE TABLE IF NOT EXISTS `{PREFIX}manager_log` (
  `id` int(10) NOT NULL auto_increment,
  `timestamp` int(20) NOT NULL default '0',
  `internalKey` int(10) NOT NULL default '0',
  `username` varchar(255) default NULL,
  `action` int(10) NOT NULL default '0',
  `itemid` varchar(10) default '0',
  `itemname` varchar(255) default NULL,
  `message` varchar(255) NOT NULL default '',
  `ip` varchar(46),
  `useragent` varchar(255),
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains a record of user interaction.';

CREATE TABLE IF NOT EXISTS `{PREFIX}manager_users` (
  `id` int(10) NOT NULL auto_increment,
  `username` varchar(100) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains login information for backend users.';

CREATE TABLE IF NOT EXISTS `{PREFIX}member_groups` (
  `id` int(10) NOT NULL auto_increment,
  `user_group` int(10) NOT NULL default '0',
  `member` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE INDEX `ix_group_member` (`user_group`,`member`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}membergroup_access` (
  `id` int(10) NOT NULL auto_increment,
  `membergroup` int(10) NOT NULL default '0',
  `documentgroup` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}membergroup_names` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(245) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_content` (
  `id` int(10) NOT NULL auto_increment,
  `type` varchar(20) NOT NULL default 'document',
  `contentType` varchar(50) NOT NULL default 'text/html',
  `pagetitle` varchar(255) NOT NULL default '',
  `longtitle` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `alias` varchar(245) default '',
  `link_attributes` varchar(255) NOT NULL default '' COMMENT 'Link attriubtes',
  `published` int(1) NOT NULL default '0',
  `pub_date` int(20) NOT NULL default '0',
  `unpub_date` int(20) NOT NULL default '0',
  `parent` int(10) NOT NULL default '0',
  `isfolder` int(1) NOT NULL default '0',
  `introtext` text COMMENT 'Used to provide quick summary of the document',
  `content` mediumtext,
  `richtext` tinyint(1) NOT NULL default '1',
  `template` int(10) NOT NULL default '0',
  `menuindex` int(10) NOT NULL default '0',
  `searchable` int(1) NOT NULL default '1',
  `cacheable` int(1) NOT NULL default '1',
  `createdby` int(10) NOT NULL default '0',
  `createdon` int(20) NOT NULL default '0',
  `editedby` int(10) NOT NULL default '0',
  `editedon` int(20) NOT NULL default '0',
  `deleted` int(1) NOT NULL default '0',
  `deletedon` int(20) NOT NULL default '0',
  `deletedby` int(10) NOT NULL default '0',
  `publishedon` int(20) NOT NULL default '0' COMMENT 'Date the document was published',
  `publishedby` int(10) NOT NULL default '0' COMMENT 'ID of user who published the document',
  `menutitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'Menu title',
  `donthit` tinyint(1) NOT NULL default '0' COMMENT 'Disable page hit count',
  `privateweb` tinyint(1) NOT NULL default '0' COMMENT 'Private web document',
  `privatemgr` tinyint(1) NOT NULL default '0' COMMENT 'Private manager document',
  `content_dispo` tinyint(1) NOT NULL default '0' COMMENT '0-inline, 1-attachment',
  `hidemenu` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Hide document from menu',
  `alias_visible` INT(2) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`id`),
  KEY `id` (`id`),
  KEY `parent` (`parent`),
  KEY aliasidx (`alias`),
  KEY typeidx (`type`),
  FULLTEXT KEY `content_ft_idx` (`pagetitle`,`description`,`content`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains the site document tree.';


CREATE TABLE IF NOT EXISTS `{PREFIX}site_htmlsnippets` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(255) NOT NULL default 'Chunk',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `editor_name` VARCHAR(50) NOT NULL DEFAULT 'none',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `cache_type`  tinyint(1) NOT NULL default '0' COMMENT 'Cache option',
  `snippet` mediumtext,
  `locked` tinyint(4) NOT NULL default '0',
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  `disabled` tinyint NOT NULL DEFAULT '0' COMMENT 'Disables the snippet',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains the site chunks.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_modules` (
  `id` integer NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '0',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `disabled` tinyint NOT NULL DEFAULT '0',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `wrap` tinyint NOT NULL DEFAULT '0',
  `locked` tinyint NOT NULL default '0',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT 'url to module icon',
  `enable_resource` tinyint NOT NULL DEFAULT '0' COMMENT 'enables the resource file feature',
  `resourcefile` varchar(255) NOT NULL DEFAULT '' COMMENT 'a physical link to a resource file',
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  `guid` varchar(32) NOT NULL DEFAULT '' COMMENT 'globally unique identifier',
  `enable_sharedparams` tinyint NOT NULL DEFAULT '0',
  `properties` text,
  `modulecode` mediumtext COMMENT 'module boot up code',
  PRIMARY KEY(`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Site Modules';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_module_depobj` (
  `id` integer NOT NULL AUTO_INCREMENT,
  `module` integer NOT NULL DEFAULT 0,
  `resource` integer NOT NULL DEFAULT 0,
  `type` integer(2) NOT NULL DEFAULT 0 COMMENT '10-chunks, 20-docs, 30-plugins, 40-snips, 50-tpls, 60-tvs',
  PRIMARY KEY(`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Module Dependencies';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_module_access` (
  `id` integer UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` integer NOT NULL DEFAULT 0,
  `usergroup` integer NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Module users group access permission';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_plugins` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default 'Plugin',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `cache_type` tinyint(1) NOT NULL default '0' COMMENT 'Cache option',
  `plugincode` mediumtext,
  `locked` tinyint(4) NOT NULL default '0',
  `properties` text COMMENT 'Default Properties',
  `disabled` tinyint NOT NULL DEFAULT '0' COMMENT 'Disables the plugin',
  `moduleguid` varchar(32) NOT NULL default '' COMMENT 'GUID of module from which to import shared parameters',
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains the site plugins.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_plugin_events` (
  `pluginid` INT(10) NOT NULL,
  `evtid` INT(10) NOT NULL default 0,
  `priority` INT(10) NOT NULL default 0 COMMENT 'determines plugin run order',
  PRIMARY KEY ( `pluginid` , `evtid` )
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Links to system events';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_snippets` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default 'Snippet',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `cache_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Cache option',
  `snippet` mediumtext,
  `locked` tinyint(4) NOT NULL default '0',
  `properties` text COMMENT 'Default Properties',
  `moduleguid` varchar(32) NOT NULL default '' COMMENT 'GUID of module from which to import shared parameters',
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  `disabled` tinyint NOT NULL DEFAULT '0' COMMENT 'Disables the snippet',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains the site snippets.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_templates` (
  `id` int(10) NOT NULL auto_increment,
  `templatename` varchar(100) NOT NULL default '',
  `description` varchar(255) NOT NULL default 'Template',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `icon` varchar(255) NOT NULL default '' COMMENT 'url to icon file',
  `template_type` integer NOT NULL DEFAULT '0' COMMENT '0-page,1-content',
  `content` mediumtext,
  `locked` tinyint(4) NOT NULL default '0',
  `selectable` tinyint(4) NOT NULL default '1',
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains the site templates.';

CREATE TABLE IF NOT EXISTS `{PREFIX}system_eventnames` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL default '',
  `service` tinyint NOT NULL default '0' COMMENT 'System Service number',
  `groupname` varchar(20) NOT NULL default '',
  PRIMARY KEY(`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='System Event Names.';

CREATE TABLE IF NOT EXISTS `{PREFIX}system_settings` (
  `setting_name` varchar(50) NOT NULL default '',
  `setting_value` text,
  PRIMARY KEY (`setting_name`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains Content Manager settings.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_tmplvar_access` (
  `id` int(10) NOT NULL auto_increment,
  `tmplvarid` int(10) NOT NULL default '0',
  `documentgroup` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for template variable access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_tmplvar_contentvalues` (
  `id` int(11) NOT NULL auto_increment,
  `tmplvarid` int(10) NOT NULL default '0' COMMENT 'Template Variable id',
  `contentid` int(10) NOT NULL default '0' COMMENT 'Site Content Id',
  `value` mediumtext,
  PRIMARY KEY  (id),
  KEY idx_tmplvarid (tmplvarid),
  KEY idx_id (contentid),
  FULLTEXT KEY `value_ft_idx` (`value`),
  UNIQUE INDEX `ix_tvid_contentid` (`tmplvarid`,`contentid`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Site Template Variables Content Values Link Table';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_tmplvar_templates` (
  `tmplvarid` int(10) NOT NULL default '0' COMMENT 'Template Variable id',
  `templateid` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  PRIMARY KEY (`tmplvarid`, `templateid`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Site Template Variables Templates Link Table';

CREATE TABLE IF NOT EXISTS `{PREFIX}site_tmplvars` (
  `id` INT(11) NOT NULL auto_increment,
  `type` varchar(50) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `caption` varchar(80) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `editor_type` integer NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `category` integer NOT NULL DEFAULT '0' COMMENT 'category id',
  `locked` tinyint(4) NOT NULL default '0',
  `elements` text,
  `rank` int(11) NOT NULL default '0',
  `display` varchar(20) NOT NULL default '' COMMENT 'Display Control',
  `display_params` text COMMENT 'Display Control Properties',
  `default_text` text,
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  PRIMARY KEY  (id),
  KEY `indx_rank`(`rank`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Site Template Variables';

CREATE TABLE IF NOT EXISTS `{PREFIX}user_attributes` (
  `id` int(10) NOT NULL auto_increment,
  `internalKey` int(10) NOT NULL default '0',
  `fullname` varchar(100) NOT NULL default '',
  `role` int(10) NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `phone` varchar(100) NOT NULL default '',
  `mobilephone` varchar(100) NOT NULL default '',
  `blocked` int(1) NOT NULL default '0',
  `blockeduntil` int(11) NOT NULL default '0',
  `blockedafter` int(11) NOT NULL default '0',
  `logincount` int(11) NOT NULL default '0',
  `lastlogin` int(11) NOT NULL default '0',
  `thislogin` int(11) NOT NULL default '0',
  `failedlogincount` int(10) NOT NULL default '0',
  `sessionid` varchar(100) NOT NULL default '',
  `dob` int(10) NOT NULL DEFAULT '0',
  `gender` int(1) NOT NULL DEFAULT '0' COMMENT '0 - unknown, 1 - Male 2 - female',
  `country` varchar(5) NOT NULL default '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `state` varchar(25) NOT NULL default '',
  `zip` varchar(25) NOT NULL default '',
  `fax` varchar(100) NOT NULL default '',
  `photo` varchar(255) NOT NULL default '' COMMENT 'link to photo',
  `comment` text,
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`internalKey`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains information about the backend users.';

CREATE TABLE IF NOT EXISTS `{PREFIX}user_messages` (
  `id` int(10) NOT NULL auto_increment,
  `type` varchar(15) NOT NULL default '',
  `subject` varchar(60) NOT NULL default '',
  `message` text,
  `sender` int(10) NOT NULL default '0',
  `recipient` int(10) NOT NULL default '0',
  `private` tinyint(4) NOT NULL default '0',
  `postdate` int(20) NOT NULL default '0',
  `messageread` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains messages for the Content Manager messaging system.';

CREATE TABLE IF NOT EXISTS `{PREFIX}user_roles` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `frames` int(1) NOT NULL default '0',
  `home` int(1) NOT NULL default '0',
  `view_document` int(1) NOT NULL default '0',
  `new_document` int(1) NOT NULL default '0',
  `save_document` int(1) NOT NULL default '0',
  `publish_document` int(1) NOT NULL default '0',
  `delete_document` int(1) NOT NULL default '0',
  `empty_trash` int(1) NOT NULL default '0',
  `action_ok` int(1) NOT NULL default '0',
  `logout` int(1) NOT NULL default '0',
  `help` int(1) NOT NULL default '0',
  `messages` int(1) NOT NULL default '0',
  `new_user` int(1) NOT NULL default '0',
  `edit_user` int(1) NOT NULL default '0',
  `logs` int(1) NOT NULL default '0',
  `edit_parser` int(1) NOT NULL default '0',
  `save_parser` int(1) NOT NULL default '0',
  `edit_template` int(1) NOT NULL default '0',
  `settings` int(1) NOT NULL default '0',
  `credits` int(1) NOT NULL default '0',
  `new_template` int(1) NOT NULL default '0',
  `save_template` int(1) NOT NULL default '0',
  `delete_template` int(1) NOT NULL default '0',
  `edit_snippet` int(1) NOT NULL default '0',
  `new_snippet` int(1) NOT NULL default '0',
  `save_snippet` int(1) NOT NULL default '0',
  `delete_snippet` int(1) NOT NULL default '0',
  `edit_chunk` int(1) NOT NULL default '0',
  `new_chunk` int(1) NOT NULL default '0',
  `save_chunk` int(1) NOT NULL default '0',
  `delete_chunk` int(1) NOT NULL default '0',
  `empty_cache` int(1) NOT NULL default '0',
  `edit_document` int(1) NOT NULL default '0',
  `change_password` int(1) NOT NULL default '0',
  `error_dialog` int(1) NOT NULL default '0',
  `about` int(1) NOT NULL default '0',
  `category_manager` int(1) NOT NULL default '0',
  `file_manager` int(1) NOT NULL default '0',
  `assets_files` int(1) NOT NULL default '0',
  `assets_images` int(1) NOT NULL default '0',
  `save_user` int(1) NOT NULL default '0',
  `delete_user` int(1) NOT NULL default '0',
  `save_password` int(11) NOT NULL default '0',
  `edit_role` int(1) NOT NULL default '0',
  `save_role` int(1) NOT NULL default '0',
  `delete_role` int(1) NOT NULL default '0',
  `new_role` int(1) NOT NULL default '0',
  `access_permissions` int(1) NOT NULL default '0',
  `bk_manager` int(1) NOT NULL DEFAULT 0,
  `new_plugin` int(1) NOT NULL DEFAULT 0,
  `edit_plugin` int(1) NOT NULL DEFAULT 0,
  `save_plugin` int(1) NOT NULL DEFAULT 0,
  `delete_plugin` int(1) NOT NULL DEFAULT 0,
  `new_module` int(1) NOT NULL DEFAULT 0,
  `edit_module` int(1) NOT NULL DEFAULT 0,
  `save_module` int(1) NOT NULL DEFAULT 0,
  `delete_module` int(1) NOT NULL DEFAULT 0,
  `exec_module` int(1) NOT NULL DEFAULT 0,
  `view_eventlog` int(1) NOT NULL DEFAULT 0,
  `delete_eventlog` int(1) NOT NULL DEFAULT 0,
  `new_web_user` int(1) NOT NULL default '0',
  `edit_web_user` int(1) NOT NULL default '0',
  `save_web_user` int(1) NOT NULL default '0',
  `delete_web_user` int(1) NOT NULL default '0',
  `web_access_permissions` int(1) NOT NULL default '0',
  `view_unpublished` int(1) NOT NULL default '0',
  `import_static` int(1) NOT NULL default '0',
  `export_static` int(1) NOT NULL default '0',
  `remove_locks` int(1) NOT NULL default '0',
  `display_locks` int(1) NOT NULL default '0',
  `change_resourcetype` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains information describing the user roles.';

CREATE TABLE IF NOT EXISTS `{PREFIX}user_settings` (
  `user` integer NOT NULL,
  `setting_name` varchar(50) NOT NULL default '',
  `setting_value` text,
  PRIMARY KEY ( `user` , `setting_name` ),
  KEY `setting_name` (`setting_name`),
  KEY `user` (`user`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains backend user settings.';

CREATE TABLE IF NOT EXISTS `{PREFIX}web_groups` (
  `id` int(10) NOT NULL auto_increment,
  `webgroup` int(10) NOT NULL default '0',
  `webuser` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE INDEX `ix_group_user` (`webgroup`,`webuser`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for web access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}webgroup_access` (
  `id` int(10) NOT NULL auto_increment,
  `webgroup` int(10) NOT NULL default '0',
  `documentgroup` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for web access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}webgroup_names` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(245) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains data used for web access permissions.';

CREATE TABLE IF NOT EXISTS `{PREFIX}web_user_attributes` (
  `id` int(10) NOT NULL auto_increment,
  `internalKey` int(10) NOT NULL default '0',
  `fullname` varchar(100) NOT NULL default '',
  `role` int(10) NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `phone` varchar(100) NOT NULL default '',
  `mobilephone` varchar(100) NOT NULL default '',
  `blocked` int(1) NOT NULL default '0',
  `blockeduntil` int(11) NOT NULL default '0',
  `blockedafter` int(11) NOT NULL default '0',
  `logincount` int(11) NOT NULL default '0',
  `lastlogin` int(11) NOT NULL default '0',
  `thislogin` int(11) NOT NULL default '0',
  `failedlogincount` int(10) NOT NULL default '0',
  `sessionid` varchar(100) NOT NULL default '',
  `dob` int(10) NOT NULL DEFAULT '0',
  `gender` int(1) NOT NULL DEFAULT '0' COMMENT '0 - unknown, 1 - Male 2 - female',
  `country` varchar(25) NOT NULL default '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `state` varchar(25) NOT NULL default '',
  `zip` varchar(25) NOT NULL default '',
  `fax` varchar(100) NOT NULL default '',
  `photo` varchar(255) NOT NULL default '' COMMENT 'link to photo',
  `comment` text,
  `createdon` integer NOT NULL DEFAULT '0',
  `editedon` integer NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`internalKey`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains information for web users.';

CREATE TABLE IF NOT EXISTS `{PREFIX}web_users` (
  `id` int(10) NOT NULL auto_increment,
  `username` varchar(100) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  `cachepwd` varchar(100) NOT NULL default '' COMMENT 'Store new unconfirmed password',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM {TABLEENCODING};

CREATE TABLE IF NOT EXISTS `{PREFIX}web_user_settings` (
  `webuser` integer NOT NULL,
  `setting_name` varchar(50) NOT NULL default '',
  `setting_value` text,
  PRIMARY KEY ( `webuser` , `setting_name` ),
  KEY `setting_name` (`setting_name`),
  KEY `webuserid` (`webuser`)
) ENGINE=MyISAM {TABLEENCODING} COMMENT='Contains web user settings.';


# upgrade-able[[ - This block of code will be executed during upgrades

# For backward compatibilty with early versions
#::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

ALTER TABLE `{PREFIX}site_content`
  ADD COLUMN `publishedon` int(20) NOT NULL DEFAULT '0' COMMENT 'Date the document was published' AFTER `deletedby`;

ALTER TABLE `{PREFIX}site_content`
  ADD COLUMN `publishedby` int(10) NOT NULL DEFAULT '0' COMMENT 'ID of user who published the document' AFTER `publishedon`;

ALTER TABLE `{PREFIX}site_content`
  ADD COLUMN `link_attributes` varchar(255) NOT NULL DEFAULT '' COMMENT 'Link attriubtes' AFTER `alias`;

ALTER TABLE `{PREFIX}site_content`
  ADD COLUMN `alias_visible` INT(2) NOT NULL DEFAULT '1' COMMENT 'Hide document from alias path';

ALTER TABLE `{PREFIX}site_htmlsnippets`
  ADD COLUMN `editor_name` VARCHAR(50) NOT NULL DEFAULT 'none' AFTER `editor_type`;

ALTER TABLE `{PREFIX}site_htmlsnippets`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_htmlsnippets`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_htmlsnippets`
  ADD COLUMN `disabled` tinyint NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_plugin_events`
  ADD COLUMN `priority` INT(10) NOT NULL default '0' COMMENT 'determines the run order of the plugin' AFTER `evtid`;

ALTER TABLE `{PREFIX}site_templates`
  ADD COLUMN `selectable` TINYINT(4) NOT NULL DEFAULT '1' AFTER `locked`;

ALTER TABLE `{PREFIX}site_templates`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_templates`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_tmplvar_templates`
  ADD COLUMN `rank` integer(11) NOT NULL DEFAULT '0' AFTER `templateid`;

ALTER TABLE `{PREFIX}site_tmplvars`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_tmplvars`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}user_attributes`
  ADD COLUMN `street` varchar(255) NOT NULL DEFAULT '' AFTER `country`;

ALTER TABLE `{PREFIX}user_attributes`
  ADD COLUMN `city` varchar(255) NOT NULL DEFAULT '' AFTER `street`;

ALTER TABLE `{PREFIX}user_attributes`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}user_attributes`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `edit_chunk`          INT(1) NOT NULL DEFAULT '0' AFTER `delete_snippet`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `new_chunk`           INT(1) NOT NULL DEFAULT '0' AFTER `edit_chunk`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `save_chunk`          INT(1) NOT NULL DEFAULT '0' AFTER `new_chunk`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `delete_chunk`        INT(1) NOT NULL DEFAULT '0' AFTER `save_chunk`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `empty_trash`         INT(1) NOT NULL DEFAULT '0' AFTER `delete_document`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `view_unpublished`    INT(1) NOT NULL DEFAULT '0' AFTER `web_access_permissions`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `import_static`       INT(1) NOT NULL DEFAULT '0' AFTER `view_unpublished`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `export_static`       INT(1) NOT NULL DEFAULT '0' AFTER `import_static`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `remove_locks`        INT(1) NOT NULL DEFAULT '0' AFTER `export_static`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `display_locks`       INT(1) NOT NULL DEFAULT '0' AFTER `remove_locks`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `publish_document`    INT(1) NOT NULL DEFAULT '0' AFTER `save_document`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `change_resourcetype` INT(1) NOT NULL DEFAULT '0' AFTER `remove_locks`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `assets_images`       INT(1) NOT NULL DEFAULT '1' AFTER `file_manager`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `assets_files`        INT(1) NOT NULL DEFAULT '1' AFTER `assets_images`;

ALTER TABLE `{PREFIX}user_roles`
  ADD COLUMN `category_manager`    INT(1) NOT NULL DEFAULT '0' AFTER `about`;

ALTER TABLE `{PREFIX}categories`
  ADD COLUMN `rank` INT(5) UNSIGNED NOT NULL DEFAULT '0' AFTER `category`;

ALTER TABLE `{PREFIX}web_user_attributes`
  ADD COLUMN `street` varchar(255) NOT NULL DEFAULT '' AFTER `country`;

ALTER TABLE `{PREFIX}web_user_attributes`
  ADD COLUMN `city` varchar(255) NOT NULL DEFAULT '' AFTER `street`;

ALTER TABLE `{PREFIX}web_user_attributes`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}web_user_attributes`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_snippets`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_snippets`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_snippets`
  ADD COLUMN `disabled` tinyint NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_plugins`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_plugins`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_templates`
  ADD COLUMN `createdon` integer NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_templates`
  ADD COLUMN `editedon` integer NOT NULL DEFAULT '0';

# Set the private manager group flag

UPDATE {PREFIX}documentgroup_names AS dgn
  LEFT JOIN {PREFIX}membergroup_access AS mga ON mga.documentgroup = dgn.id
  LEFT JOIN {PREFIX}webgroup_access AS wga ON wga.documentgroup = dgn.id
  SET dgn.private_memgroup = (mga.membergroup IS NOT NULL),
      dgn.private_webgroup = (wga.webgroup IS NOT NULL);


UPDATE `{PREFIX}site_plugins` SET `disabled`='1' WHERE `name` IN ('Bottom Button Bar');

UPDATE `{PREFIX}site_plugins` SET `disabled`='1' WHERE `name` IN ('Inherit Parent Template');

UPDATE `{PREFIX}system_settings` SET `setting_value` = '0' WHERE `setting_name` = 'validate_referer' AND `setting_value` = '00';

# start related to #EVO-1321

UPDATE `{PREFIX}site_content` SET `type`='reference', `contentType`='text/html' WHERE `type`='' AND `content` REGEXP '^https?://([-\w\.]+)+(:\d+)?/?';

UPDATE `{PREFIX}site_content` SET `type`='document', `contentType`='text/xml' WHERE `type`='' AND `alias` REGEXP '\.(rss|xml)$';

UPDATE `{PREFIX}site_content` SET `type`='document', `contentType`='text/javascript' WHERE `type`='' AND `alias` REGEXP '\.js$';

UPDATE `{PREFIX}site_content` SET `type`='document', `contentType`='text/css' WHERE `type`='' AND `alias` REGEXP '\.css$';

UPDATE `{PREFIX}site_content` SET `type`='document', `contentType`='text/html' WHERE `type`='';

ALTER TABLE `{PREFIX}documentgroup_names`
  MODIFY COLUMN `name` varchar(245) NOT NULL default '';

ALTER TABLE `{PREFIX}event_log`
  MODIFY COLUMN `source` varchar(50) NOT NULL DEFAULT '',
  MODIFY COLUMN `description` text;

ALTER TABLE `{PREFIX}categories`
  MODIFY COLUMN `category` varchar(45) NOT NULL DEFAULT '';

ALTER TABLE `{PREFIX}manager_users`
  MODIFY COLUMN `username` varchar(100) NOT NULL DEFAULT '';

ALTER TABLE `{PREFIX}membergroup_names`
 MODIFY COLUMN `name` varchar(245) NOT NULL default '';

ALTER TABLE `{PREFIX}site_content`
  MODIFY COLUMN `pagetitle` varchar(255) NOT NULL default '',
  MODIFY COLUMN `alias` varchar(245) default '',
  MODIFY COLUMN `introtext` text COMMENT 'Used to provide quick summary of the document',
  MODIFY COLUMN `content` mediumtext,
  MODIFY COLUMN `menutitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'Menu title',
  MODIFY COLUMN `template` int(10) NOT NULL default '0';

ALTER TABLE `{PREFIX}site_htmlsnippets`
  MODIFY COLUMN `snippet` mediumtext;

ALTER TABLE `{PREFIX}site_module_access`
  MODIFY COLUMN `module` int(11) NOT NULL DEFAULT '0',
  MODIFY COLUMN `usergroup` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_module_depobj`
  MODIFY COLUMN `module` int(11) NOT NULL DEFAULT '0',
  MODIFY COLUMN `resource` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_modules`
  MODIFY COLUMN `name` varchar(50) NOT NULL DEFAULT '',
  MODIFY COLUMN `disabled` tinyint(4) NOT NULL DEFAULT '0',
  MODIFY COLUMN `icon` varchar(255) NOT NULL DEFAULT '' COMMENT 'url to module icon',
  MODIFY COLUMN `resourcefile` varchar(255) NOT NULL DEFAULT '' COMMENT 'a physical link to a resource file',
  MODIFY COLUMN `createdon` int(11) NOT NULL DEFAULT '0',
  MODIFY COLUMN `editedon` int(11) NOT NULL DEFAULT '0',
  MODIFY COLUMN `guid` varchar(32) NOT NULL DEFAULT '' COMMENT 'globally unique identifier',
  MODIFY COLUMN `properties` text,
  MODIFY COLUMN `modulecode` mediumtext COMMENT 'module boot up code';

ALTER TABLE `{PREFIX}site_plugin_events`
  MODIFY COLUMN `evtid` int(10) NOT NULL DEFAULT '0';

ALTER TABLE `{PREFIX}site_plugins`
  MODIFY COLUMN `properties` text COMMENT 'Default Properties',
  MODIFY COLUMN `plugincode` mediumtext,
  MODIFY COLUMN `moduleguid` varchar(32) NOT NULL DEFAULT '' COMMENT 'GUID of module from which to import shared parameters';

ALTER TABLE `{PREFIX}site_snippets`
  MODIFY COLUMN `properties` text COMMENT 'Default Properties',
  MODIFY COLUMN `snippet` mediumtext,
  MODIFY COLUMN `moduleguid` varchar(32) NOT NULL DEFAULT '' COMMENT 'GUID of module from which to import shared parameters';

ALTER TABLE `{PREFIX}site_templates`
 MODIFY COLUMN `icon` varchar(255) NOT NULL default '' COMMENT 'url to icon file',
 MODIFY COLUMN `content` mediumtext;

ALTER TABLE `{PREFIX}site_tmplvar_contentvalues`
 MODIFY COLUMN `tmplvarid` int(10) NOT NULL DEFAULT '0' COMMENT 'Template Variable id',
 MODIFY COLUMN `value` mediumtext;

ALTER TABLE `{PREFIX}site_tmplvar_templates`
  MODIFY COLUMN `tmplvarid` int(10) NOT NULL DEFAULT '0' COMMENT 'Template Variable id';

ALTER TABLE `{PREFIX}site_tmplvars`
 MODIFY COLUMN `name` varchar(50) NOT NULL default '',
 MODIFY COLUMN `elements` text,
 MODIFY COLUMN `display` varchar(20) NOT NULL DEFAULT '' COMMENT 'Display Control',
 MODIFY COLUMN `display_params` text COMMENT 'Display Control Properties',
 MODIFY COLUMN `default_text` text;

ALTER TABLE `{PREFIX}system_eventnames`
 MODIFY COLUMN  `name` varchar(50) NOT NULL DEFAULT '',
 MODIFY COLUMN `service` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'System Service number';

ALTER TABLE `{PREFIX}system_settings` MODIFY COLUMN `setting_value` text;

ALTER TABLE `{PREFIX}user_attributes`
 MODIFY COLUMN `country` varchar(5) NOT NULL DEFAULT '',
 MODIFY COLUMN `state` varchar(25) NOT NULL DEFAULT '',
 MODIFY COLUMN `zip` varchar(25) NOT NULL DEFAULT '',
 MODIFY COLUMN `fax` varchar(100) NOT NULL DEFAULT '',
 MODIFY COLUMN `photo` varchar(255) NOT NULL DEFAULT '' COMMENT 'link to photo',
 MODIFY COLUMN `comment` text;

ALTER TABLE `{PREFIX}user_messages` MODIFY COLUMN `message` text;

ALTER TABLE `{PREFIX}user_settings` MODIFY COLUMN `setting_value` text;

ALTER TABLE `{PREFIX}web_users`
 MODIFY COLUMN `username` varchar(100) NOT NULL DEFAULT '',
 MODIFY COLUMN `cachepwd` varchar(100) NOT NULL DEFAULT '' COMMENT 'Store new unconfirmed password' AFTER `password`;

ALTER TABLE `{PREFIX}web_user_settings` MODIFY COLUMN `setting_value` text;

ALTER TABLE `{PREFIX}web_user_attributes`
  MODIFY COLUMN `country` varchar(25) NOT NULL DEFAULT '',
  MODIFY COLUMN `state` varchar(25) NOT NULL DEFAULT '',
  MODIFY COLUMN `zip` varchar(25) NOT NULL DEFAULT '',
  MODIFY COLUMN `fax` varchar(100) NOT NULL DEFAULT '',
  MODIFY COLUMN `photo` varchar(255) NOT NULL DEFAULT '' COMMENT 'link to photo',
  MODIFY COLUMN `comment` text;

ALTER TABLE `{PREFIX}webgroup_names`
 MODIFY COLUMN `name` varchar(245) NOT NULL default '';

ALTER TABLE `{PREFIX}site_content` ADD INDEX `typeidx` (`type`);

ALTER TABLE `{PREFIX}system_settings` DROP PRIMARY KEY;

ALTER TABLE `{PREFIX}system_settings` DROP INDEX `setting_name`;

ALTER TABLE `{PREFIX}system_settings` ADD PRIMARY KEY (`setting_name`);

ALTER TABLE `{PREFIX}user_settings` DROP PRIMARY KEY;

ALTER TABLE `{PREFIX}user_settings` ADD PRIMARY KEY (`user`, `setting_name`);

ALTER TABLE `{PREFIX}web_user_settings` DROP PRIMARY KEY;

ALTER TABLE `{PREFIX}web_user_settings` ADD PRIMARY KEY (`webuser`, `setting_name`);

ALTER TABLE `{PREFIX}site_plugin_events` DROP PRIMARY KEY;

ALTER TABLE `{PREFIX}site_plugin_events` ADD PRIMARY KEY (`pluginid`, `evtid`);

ALTER TABLE `{PREFIX}site_tmplvar_contentvalues` ADD FULLTEXT `value_ft_idx` (`value`);

ALTER TABLE `{PREFIX}site_tmplvar_contentvalues` ADD UNIQUE INDEX `ix_tvid_contentid` (`tmplvarid`,`contentid`);

ALTER TABLE `{PREFIX}site_tmplvar_templates` DROP INDEX `idx_tmplvarid`;

ALTER TABLE `{PREFIX}site_tmplvar_templates` DROP INDEX `idx_templateid`;

ALTER TABLE `{PREFIX}site_tmplvar_templates` DROP PRIMARY KEY;

ALTER TABLE `{PREFIX}site_tmplvar_templates` ADD PRIMARY KEY (`tmplvarid`, `templateid`);

ALTER TABLE `{PREFIX}member_groups` ADD UNIQUE INDEX `ix_group_member` (`user_group`,`member`);

ALTER TABLE `{PREFIX}web_groups` ADD UNIQUE INDEX `ix_group_user` (`webgroup`,`webuser`);

ALTER TABLE `{PREFIX}document_groups` ADD UNIQUE INDEX `ix_dg_id` (`document_group`,`document`);

ALTER TABLE `{PREFIX}manager_log`
  ADD COLUMN `ip` varchar(46);

ALTER TABLE `{PREFIX}manager_log`
  ADD COLUMN `useragent` varchar(255);

ALTER TABLE `{PREFIX}manager_log`
	CHANGE COLUMN `ip` `ip` VARCHAR(46) NULL DEFAULT NULL AFTER `message`;

# ]]upgrade-able


# Insert / Replace system records
#::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


# non-upgrade-able[[ - This block of code will not be executed during upgrades


# Default Site Template


REPLACE INTO `{PREFIX}site_templates`
(id, templatename, description, editor_type, category, icon, template_type, content, locked, selectable) VALUES ('3','Minimal Template','Default minimal empty template (content returned only)','0','0','','0','[*content*]','0','1');


# Default Site Documents


REPLACE INTO `{PREFIX}site_content` VALUES (1,'document','text/html','Evolution CMS Install Success','Welcome to the EVO Content Management System','','minimal-base','',1,0,0,0,0,'','<h3>Install Successful!</h3>\r\n<p>You have successfully installed Evolution CMS.</p>\r\n\r\n<h3>Getting Help</h3>\r\n<p>The <a href=\"http://evo.im/\" target=\"_blank\">EVO Community</a> provides a great starting point to learn all things Evolution CMS, or you can also <a href=\"http://evo.im/\">see some great learning resources</a> (books, tutorials, blogs and screencasts).</p>\r\n<p>Welcome to EVO!</p>\r\n',1,3,0,1,1,1,1130304721,1,1130304927,0,0,0,1130304721,1,'Base Install',0,0,0,0,0,1);


REPLACE INTO `{PREFIX}manager_users`
(id, username, password)VALUES
(1, '{ADMIN}', MD5('{ADMINPASS}'));

REPLACE INTO `{PREFIX}user_attributes`
(id, internalKey, fullname, role, email, phone, mobilephone, blocked, blockeduntil, blockedafter, logincount, lastlogin, thislogin, failedlogincount, sessionid, dob, gender, country, street, city, state, zip, fax, photo, comment) VALUES
(1, 1, 'Admin', 1, '{ADMINEMAIL}', '', '', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, '', '', '', '', '', '', '', '');


REPLACE INTO `{PREFIX}user_roles`
(id,name,description,frames,home,view_document,new_document,save_document,publish_document,delete_document,empty_trash,action_ok,logout,help,messages,new_user,edit_user,logs,edit_parser,save_parser,edit_template,settings,credits,new_template,save_template,delete_template,edit_snippet,new_snippet,save_snippet,delete_snippet,edit_chunk,new_chunk,save_chunk,delete_chunk,empty_cache,edit_document,change_password,error_dialog,about,file_manager,save_user,delete_user,save_password,edit_role,save_role,delete_role,new_role,access_permissions,bk_manager,new_plugin,edit_plugin,save_plugin,delete_plugin,new_module,edit_module,save_module,exec_module,delete_module,view_eventlog,delete_eventlog,new_web_user,edit_web_user,save_web_user,delete_web_user,web_access_permissions,view_unpublished,import_static,export_static,remove_locks,assets_images,assets_files,change_resourcetype,display_locks,category_manager) VALUES
(2,'Editor','Limited to managing content',1,1,1,1,1,1,1,0,1,1,1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,1,0,1,0,1,1,1,1,1,1,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1,0,0,1,1,1,1,1,0),
(3,'Publisher','Editor with expanded permissions including manage users\, update Elements and site settings',1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,0,0,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,1,1,1,1,1,1,1,1,0,0,0,0,0,1,0,0,0,0,0,0,0,1,0,0,0,1,1,1,1,0,1,0,0,1,1,1,1,1,0);


# ]]non-upgrade-able


# Default Site Settings


INSERT IGNORE INTO `{PREFIX}system_settings`
(setting_name, setting_value) VALUES
('settings_version',''),
('manager_theme','default'),
('server_offset_time','0'),
('manager_language','{MANAGERLANGUAGE}'),
('modx_charset','UTF-8'),
('site_name','My Evolution Site'),
('site_start','1'),
('error_page','1'),
('unauthorized_page','1'),
('site_status','1'),
('auto_template_logic','{AUTOTEMPLATELOGIC}'),
('default_template','3'),
('old_template',''),
('publish_default','1'),
('friendly_urls','1'),
('friendly_alias_urls','1'),
('use_alias_path','1'),
('cache_type','2'),
('failed_login_attempts','3'),
('blocked_minutes','60'),
('use_captcha','0'),
('emailsender','{ADMINEMAIL}'),
('use_editor','1'),
('use_browser','1'),
('fe_editor_lang','{MANAGERLANGUAGE}'),
('fck_editor_toolbar','standard'),
('fck_editor_autolang','0'),
('editor_css_path',''),
('editor_css_selectors',''),
('upload_maxsize','10485760'),
('manager_layout','4'),
('auto_menuindex','1'),
('session.cookie.lifetime','604800'),
('mail_check_timeperiod','600'),
('manager_direction','ltr'),
('xhtml_urls','0'),
('automatic_alias','1'),
('datetime_format','dd-mm-YYYY'),
('warning_visibility', '0'),
('remember_last_tab', '1'),
('enable_bindings', '1'),
('seostrict', '1'),
('number_of_results','30'),
('theme_refresher',''),
('show_picker', '0'),
('show_newresource_btn', '0'),
('show_fullscreen_btn', '0');

REPLACE INTO `{PREFIX}user_roles`
(id,name,description,frames,home,view_document,new_document,save_document,publish_document,delete_document,empty_trash,action_ok,logout,help,messages,new_user,edit_user,logs,edit_parser,save_parser,edit_template,settings,credits,new_template,save_template,delete_template,edit_snippet,new_snippet,save_snippet,delete_snippet,edit_chunk,new_chunk,save_chunk,delete_chunk,empty_cache,edit_document,change_password,error_dialog,about,file_manager,save_user,delete_user,save_password,edit_role,save_role,delete_role,new_role,access_permissions,bk_manager,new_plugin,edit_plugin,save_plugin,delete_plugin,new_module,edit_module,save_module,exec_module,delete_module,view_eventlog,delete_eventlog,new_web_user,edit_web_user,save_web_user,delete_web_user,web_access_permissions,view_unpublished,import_static,export_static,remove_locks,assets_images,assets_files,change_resourcetype,display_locks,category_manager) VALUES
(1, 'Administrator', 'Site administrators have full access to all functions',1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);


# 1 - "Parser Service Events", 2 -  "Manager Access Events", 3 - "Web Access Service Events", 4 - "Cache Service Events", 5 - "Template Service Events", 6 - Custom Events


REPLACE INTO `{PREFIX}system_eventnames`
(id,name,service,groupname) VALUES
('1','OnDocPublished','5',''),
('2','OnDocUnPublished','5',''),
('3','OnWebPagePrerender','5',''),
('4','OnWebLogin','3',''),
('5','OnBeforeWebLogout','3',''),
('6','OnWebLogout','3',''),
('7','OnWebSaveUser','3',''),
('8','OnWebDeleteUser','3',''),
('9','OnWebChangePassword','3',''),
('10','OnWebCreateGroup','3',''),
('11','OnManagerLogin','2',''),
('12','OnBeforeManagerLogout','2',''),
('13','OnManagerLogout','2',''),
('14','OnManagerSaveUser','2',''),
('15','OnManagerDeleteUser','2',''),
('16','OnManagerChangePassword','2',''),
('17','OnManagerCreateGroup','2',''),
('18','OnBeforeCacheUpdate','4',''),
('19','OnCacheUpdate','4',''),
('107','OnMakePageCacheKey','4',''),
('20','OnLoadWebPageCache','4',''),
('21','OnBeforeSaveWebPageCache','4',''),
('22','OnChunkFormPrerender','1','Chunks'),
('23','OnChunkFormRender','1','Chunks'),
('24','OnBeforeChunkFormSave','1','Chunks'),
('25','OnChunkFormSave','1','Chunks'),
('26','OnBeforeChunkFormDelete','1','Chunks'),
('27','OnChunkFormDelete','1','Chunks'),
('28','OnDocFormPrerender','1','Documents'),
('29','OnDocFormRender','1','Documents'),
('30','OnBeforeDocFormSave','1','Documents'),
('31','OnDocFormSave','1','Documents'),
('32','OnBeforeDocFormDelete','1','Documents'),
('33','OnDocFormDelete','1','Documents'),
('1033','OnDocFormUnDelete','1','Documents'),
('1034','onBeforeMoveDocument','1','Documents'),
('1035','onAfterMoveDocument','1','Documents'),
('34','OnPluginFormPrerender','1','Plugins'),
('35','OnPluginFormRender','1','Plugins'),
('36','OnBeforePluginFormSave','1','Plugins'),
('37','OnPluginFormSave','1','Plugins'),
('38','OnBeforePluginFormDelete','1','Plugins'),
('39','OnPluginFormDelete','1','Plugins'),
('40','OnSnipFormPrerender','1','Snippets'),
('41','OnSnipFormRender','1','Snippets'),
('42','OnBeforeSnipFormSave','1','Snippets'),
('43','OnSnipFormSave','1','Snippets'),
('44','OnBeforeSnipFormDelete','1','Snippets'),
('45','OnSnipFormDelete','1','Snippets'),
('46','OnTempFormPrerender','1','Templates'),
('47','OnTempFormRender','1','Templates'),
('48','OnBeforeTempFormSave','1','Templates'),
('49','OnTempFormSave','1','Templates'),
('50','OnBeforeTempFormDelete','1','Templates'),
('51','OnTempFormDelete','1','Templates'),
('52','OnTVFormPrerender','1','Template Variables'),
('53','OnTVFormRender','1','Template Variables'),
('54','OnBeforeTVFormSave','1','Template Variables'),
('55','OnTVFormSave','1','Template Variables'),
('56','OnBeforeTVFormDelete','1','Template Variables'),
('57','OnTVFormDelete','1','Template Variables'),
('58','OnUserFormPrerender','1','Users'),
('59','OnUserFormRender','1','Users'),
('60','OnBeforeUserFormSave','1','Users'),
('61','OnUserFormSave','1','Users'),
('62','OnBeforeUserFormDelete','1','Users'),
('63','OnUserFormDelete','1','Users'),
('64','OnWUsrFormPrerender','1','Web Users'),
('65','OnWUsrFormRender','1','Web Users'),
('66','OnBeforeWUsrFormSave','1','Web Users'),
('67','OnWUsrFormSave','1','Web Users'),
('68','OnBeforeWUsrFormDelete','1','Web Users'),
('69','OnWUsrFormDelete','1','Web Users'),
('70','OnSiteRefresh','1',''),
('71','OnFileManagerUpload','1',''),
('72','OnModFormPrerender','1','Modules'),
('73','OnModFormRender','1','Modules'),
('74','OnBeforeModFormDelete','1','Modules'),
('75','OnModFormDelete','1','Modules'),
('76','OnBeforeModFormSave','1','Modules'),
('77','OnModFormSave','1','Modules'),
('78','OnBeforeWebLogin','3',''),
('79','OnWebAuthentication','3',''),
('80','OnBeforeManagerLogin','2',''),
('81','OnManagerAuthentication','2',''),
('82','OnSiteSettingsRender','1','System Settings'),
('83','OnFriendlyURLSettingsRender','1','System Settings'),
('84','OnUserSettingsRender','1','System Settings'),
('85','OnInterfaceSettingsRender','1','System Settings'),
('109','OnSecuritySettingsRender','1','System Settings'),
('110','OnFileManagerSettingsRender','1','System Settings'),
('86','OnMiscSettingsRender','1','System Settings'),
('87','OnRichTextEditorRegister','1','RichText Editor'),
('88','OnRichTextEditorInit','1','RichText Editor'),
('89','OnManagerPageInit','2',''),
('90','OnWebPageInit','5',''),
('101','OnLoadDocumentObject','5',''),
('104','OnBeforeLoadDocumentObject','5',''),
('105','OnAfterLoadDocumentObject','5',''),
('91','OnLoadWebDocument','5',''),
('92','OnParseDocument','5',''),
('106','OnParseProperties','5',''),
('108','OnBeforeParseParams','5',''),
('93','OnManagerLoginFormRender','2',''),
('94','OnWebPageComplete','5',''),
('95','OnLogPageHit','5',''),
('96','OnBeforeManagerPageInit','2',''),
('97','OnBeforeEmptyTrash','1','Documents'),
('98','OnEmptyTrash','1','Documents'),
('99','OnManagerLoginFormPrerender','2',''),
('100','OnStripAlias','1','Documents'),
('102','OnMakeDocUrl','5',''),
('103','OnBeforeLoadExtension','5',''),
('200','OnCreateDocGroup','1','Documents'),
('201','OnManagerWelcomePrerender','2',''),
('202','OnManagerWelcomeHome','2',''),
('203','OnManagerWelcomeRender','2',''),
('204','OnBeforeDocDuplicate','1','Documents'),
('205','OnDocDuplicate','1','Documents'),
('206','OnManagerMainFrameHeaderHTMLBlock','2',''),
('207','OnManagerPreFrameLoader','2',''),
('208','OnManagerFrameLoader','2',''),
('209','OnManagerTreeInit','2',''),
('210','OnManagerTreePrerender','2',''),
('211','OnManagerTreeRender','2',''),
('212','OnManagerNodePrerender','2',''),
('213','OnManagerNodeRender','2',''),
('214','OnManagerMenuPrerender','2',''),
('215','OnManagerTopPrerender','2',''),
('224','OnDocFormTemplateRender','1','Documents'),
('225','OnBeforeMinifyCss', '1', ''),
('999','OnPageUnauthorized','1',''),
('1000','OnPageNotFound','1',''),
('1001','OnFileBrowserUpload','1','File Browser Events'),
('1002','OnBeforeFileBrowserUpload','1','File Browser Events'),
('1003','OnFileBrowserDelete','1','File Browser Events'),
('1004','OnBeforeFileBrowserDelete','1','File Browser Events'),
('1005','OnFileBrowserInit','1','File Browser Events'),
('1006','OnFileBrowserMove','1','File Browser Events'),
('1007','OnBeforeFileBrowserMove','1','File Browser Events'),
('1008','OnFileBrowserCopy','1','File Browser Events'),
('1009','OnBeforeFileBrowserCopy','1','File Browser Events'),
('1010','OnBeforeFileBrowserRename','1','File Browser Events'),
('1011','OnFileBrowserRename','1','File Browser Events');


# ^ I don't think we need more than 1000 built-in events. Custom events will start at 1001


# Update System Tables
#::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


UPDATE `{PREFIX}user_roles` SET
  bk_manager=1,
  new_plugin=1,
  edit_plugin=1,
  save_plugin=1,
  delete_plugin=1,
  new_module=1,
  edit_module=1,
  save_module=1,
  delete_module=1,
  exec_module=1,
  view_eventlog = 1,
  delete_eventlog = 1,
  new_web_user = 1,
  edit_web_user = 1,
  save_web_user = 1,
  delete_web_user = 1,
  new_chunk = 1,
  edit_chunk = 1,
  save_chunk = 1,
  delete_chunk = 1,
  web_access_permissions = 1,
  view_unpublished = 1,
  publish_document = 1,
  import_static = 1,
  export_static = 1,
  empty_trash = 1,
  remove_locks = 1,
  display_locks = 1,
  assets_images = 1,
  assets_files = 1,
  change_resourcetype = 1
  WHERE `id`=1;


# Update any invalid Manager Themes in User Settings and reset the default theme


UPDATE `{PREFIX}user_settings` SET
  `setting_value`='default'
  WHERE `setting_name`='manager_theme';


REPLACE INTO `{PREFIX}system_settings` (setting_name, setting_value) VALUES ('manager_theme','default');

REPLACE INTO `{PREFIX}system_settings` (setting_name, setting_value) VALUES ('email_sender_method','1');

UPDATE `{PREFIX}system_settings` set setting_value = if(setting_value REGEXP 'application/json',setting_value,concat_ws(",",setting_value,"application/json")) WHERE setting_name='custom_contenttype';
