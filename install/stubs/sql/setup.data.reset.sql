# EVO Database Script for New/Upgrade Installations
#
# Each sql command is separated by double lines


#
# Empty tables first
# 

TRUNCATE TABLE `{PREFIX}documentgroup_names`;

TRUNCATE TABLE `{PREFIX}site_content`;

TRUNCATE TABLE `{PREFIX}site_htmlsnippets`;

TRUNCATE TABLE `{PREFIX}site_plugins`;

TRUNCATE TABLE `{PREFIX}site_snippets`;

TRUNCATE TABLE `{PREFIX}site_tmplvar_contentvalues`;

TRUNCATE TABLE `{PREFIX}site_tmplvar_access`;

TRUNCATE TABLE `{PREFIX}site_tmplvar_templates`;

TRUNCATE TABLE `{PREFIX}site_tmplvars`;

# TRUNCATE TABLE `{PREFIX}web_groups`;
# TRUNCATE TABLE `{PREFIX}web_user_attributes`;
# TRUNCATE TABLE `{PREFIX}web_users`;
# TRUNCATE TABLE `{PREFIX}webgroup_access`;
# TRUNCATE TABLE `{PREFIX}webgroup_names`;

# Tables not existing at new installations
# TRUNCATE TABLE `{PREFIX}jot_content`;
# TRUNCATE TABLE `{PREFIX}jot_subscriptions`;