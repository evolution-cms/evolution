<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function createTableIfMissing(string $table, \Closure $callback): void
    {
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, $callback);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isMySql = DB::connection()->getDriverName() === 'mysql';

        /*
        |--------------------------------------------------------------------------
        | The user's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('users', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Users authentication table - stores login credentials and authentication tokens');
            $table->increments('id');
            $table->string('username')->default('');
            $table->string('password')->default('');
            $table->string('cachepwd')->default('')->comment('Store new unconfirmed password');
            $table->string('refresh_token')->nullable();
            $table->string('access_token')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->string('verified_key')->nullable();
            $table->unique('username', "{$indexPrefix}_ix_username_unique");
        });

        $this->createTableIfMissing('user_attributes', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Users profile data - stores extended user information, personal details, and activity tracking');
            $table->increments('id');
            $table->unsignedInteger('internalKey')->default(0)->index("{$indexPrefix}_internalkey_index");
            $table->string('fullname')->default('');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->unsignedInteger('role')->default(0);
            $table->string('email')->default('');
            $table->string('phone')->default('');
            $table->string('mobilephone')->default('');
            $table->unsignedInteger('blocked')->default(0);
            $table->unsignedInteger('blockeduntil')->default(0);
            $table->unsignedInteger('blockedafter')->default(0);
            $table->unsignedInteger('logincount')->default(0);
            $table->unsignedInteger('lastlogin')->default(0);
            $table->unsignedInteger('thislogin')->default(0);
            $table->unsignedInteger('failedlogincount')->default(0);
            $table->string('sessionid')->default('');
            $table->unsignedInteger('dob')->nullable();
            $table->unsignedInteger('gender')->default(0)->comment('0 - unknown, 1 - Male 2 - female');
            $table->string('country', 25)->default('');
            $table->string('street')->default('');
            $table->string('city')->default('');
            $table->string('state', 25)->default('');
            $table->string('zip', 25)->default('');
            $table->string('fax')->default('');
            $table->string('photo')->default('')->comment('link to photo');
            $table->text('comment')->nullable();
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->tinyInteger('verified')->default(0);
        });

        $this->createTableIfMissing('user_roles', function(Blueprint $table)
        {
            $table->comment('User roles definition - defines available roles for web users');
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->string('description')->default('');
        });

        $this->createTableIfMissing('user_role_vars', function (Blueprint $table) {
            $table->comment('Template variables access control for user roles - defines which template variables are accessible to specific roles');
            $table->unsignedInteger('tmplvarid')->default(0);
            $table->unsignedInteger('roleid')->default(0);
            $table->unsignedInteger('rank')->default(0);
            $table->primary(['tmplvarid','roleid']);
        });

        $this->createTableIfMissing('user_settings', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('User preferences and settings - stores user-specific configuration options');
            $table->unsignedInteger('user')->index("{$indexPrefix}_user");
            $table->string('setting_name', 50)->default('')->index("{$indexPrefix}_setting_name");
            $table->longText('setting_value')->nullable();
            $table->primary(['user','setting_name']);
        });

        $this->createTableIfMissing('user_values', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Template variable values for web users - stores custom field values for individual users');
            $table->increments('id');
            $table->unsignedInteger('tmplvarid')->default(0)->index("{$indexPrefix}_tmplvarid_idx");
            $table->unsignedInteger('userid')->default(0)->index("{$indexPrefix}_userid_idx");
            $table->mediumText('value')->nullable();
            $table->unique(['tmplvarid','userid'], "{$indexPrefix}_tmplvarid_userid");
        });

        $this->createTableIfMissing('active_user_locks', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Resource editing locks - tracks which users are currently editing specific resources (documents, templates, etc.)');
            $table->increments('id');
            $table->string('sid', 128)->default('');
            $table->unsignedInteger('internalKey')->default(0);
            $table->unsignedInteger('elementType')->default(0);
            $table->unsignedInteger('elementId')->default(0);
            $table->unsignedInteger('lasthit')->default(0);
            $table->unique(['elementType','elementId','sid'], "{$indexPrefix}_ix_element_id");
        });

        $this->createTableIfMissing('active_user_sessions', function(Blueprint $table)
        {
            $table->comment('Active user sessions - tracks currently logged-in users with session IDs and IP addresses');
            $table->string('sid', 128)->default('')->primary();
            $table->unsignedInteger('internalKey')->default(0);
            $table->unsignedInteger('lasthit')->default(0);
            $table->ipAddress('ip')->default('');
        });

        $this->createTableIfMissing('active_users', function(Blueprint $table)
        {
            $table->comment('Active users tracking - monitors currently active users and their actions in the system');
            $table->string('sid', 128)->default('')->primary();
            $table->unsignedInteger('internalKey')->default(0);
            $table->string('username', 50)->default('');
            $table->unsignedInteger('lasthit')->default(0);
            $table->string('action', 10)->default('');
            $table->unsignedInteger('id')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | The categories tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('categories', function(Blueprint $table)
        {
            $table->comment('Categories for organizing elements - used to group templates, chunks, snippets, plugins, and modules');
            $table->increments('id');
            $table->string('category', 45)->default('');
            $table->unsignedInteger('rank')->default(0);
        });

        /*
        |--------------------------------------------------------------------------
        | The document group's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('document_groups', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Document group assignments - many-to-many relationship between documents and document groups');
            $table->increments('id');
            $table->unsignedInteger('document_group')->default(0)->index("{$indexPrefix}_document_group");
            $table->unsignedInteger('document')->default(0)->index("{$indexPrefix}_document");
            $table->unique(['document_group','document'], "{$indexPrefix}_ix_dg_id");
        });

        $this->createTableIfMissing('documentgroup_names', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Document group definitions - defines document groups for access control and organization');
            $table->increments('id');
            $table->string('name')->default('')->unique("{$indexPrefix}_name");
            $table->unsignedInteger('private_memgroup')->nullable()->default(0)->comment('determine whether the document group is private to manager users');
            $table->unsignedInteger('private_webgroup')->nullable()->default(0)->comment('determines whether the document is private to web users');
        });

        /*
        |--------------------------------------------------------------------------
        | The log's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('event_log', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('System event log - records all system events, errors, warnings, and informational messages');
            $table->increments('id');
            $table->unsignedInteger('eventid')->nullable()->default(0);
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('type')->default(1)->comment('1- information, 2 - warning, 3- error');
            $table->unsignedInteger('user')->default(0)->index("{$indexPrefix}_user")->comment('link to user table');
            $table->unsignedInteger('usertype')->default(0)->comment('0 - manager, 1 - web');
            $table->string('source', 128)->default('');
            $table->longText('description')->nullable();
        });

        $this->createTableIfMissing('manager_log', function(Blueprint $table)
        {
            $table->comment('Manager actions audit log - tracks all actions performed by manager users in the admin panel');
            $table->increments('id');
            $table->unsignedInteger('timestamp')->default(0);
            $table->unsignedInteger('internalKey')->default(0);
            $table->string('username')->nullable();
            $table->unsignedInteger('action')->default(0);
            $table->string('itemid', 10)->nullable()->default('0');
            $table->string('itemname')->nullable();
            $table->string('message')->default('');
            $table->ipAddress('ip')->nullable();
            $table->string('useragent')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | The member group's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('member_groups', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('User group memberships - many-to-many relationship between web users and member groups');
            $table->increments('id');
            $table->unsignedInteger('user_group')->default(0);
            $table->unsignedInteger('member')->default(0);
            $table->unique(['user_group','member'], "{$indexPrefix}_ix_group_member");
        });

        $this->createTableIfMissing('membergroup_access', function(Blueprint $table)
        {
            $table->comment('Member group access control - defines which document groups are accessible to specific member groups');
            $table->increments('id');
            $table->unsignedInteger('membergroup')->default(0);
            $table->unsignedInteger('documentgroup')->default(0);
            $table->unsignedInteger('context')->default(0);
        });

        $this->createTableIfMissing('membergroup_names', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Member group definitions - defines user groups for access control');
            $table->increments('id');
            $table->string('name', 245)->default('')->unique("{$indexPrefix}_name");
        });

        /*
        |--------------------------------------------------------------------------
        | The permission's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('permissions', function(Blueprint $table)
        {
            $table->comment('System permissions definition - defines available permissions for access control');
            $table->increments('id');
            $table->string('name');
            $table->string('key');
            $table->string('lang_key')->default('');
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('disabled')->nullable();
            $table->timestamps();
        });

        $this->createTableIfMissing('permissions_groups', function(Blueprint $table)
        {
            $table->comment('Permission groups - organizes permissions into logical groups for easier management');
            $table->increments('id');
            $table->string('name');
            $table->string('lang_key')->default('');
            $table->timestamps();
        });

        $this->createTableIfMissing('role_permissions', function(Blueprint $table)
        {
            $table->comment('Role permissions assignment - many-to-many relationship between roles and permissions');
            $table->increments('id');
            $table->string('permission');
            $table->unsignedInteger('role_id');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | The content's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_content', function(Blueprint $table) use ($isMySql)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Site content (documents) - main table storing all site pages, documents, and content resources');
            $table->increments('id');
            $table->string('type', 20)->default('document')->index("{$indexPrefix}_typeidx");
            $table->string('contentType', 50)->default('text/html');
            $table->string('pagetitle')->default('');
            $table->string('longtitle')->default('');
            $table->string('description')->default('');
            $table->string('alias', 245)->nullable()->default('')->index("{$indexPrefix}_aliasidx");
            $table->string('link_attributes')->default('')->comment('Link attriubtes');
            $table->unsignedInteger('published')->default(0);
            $table->unsignedInteger('pub_date')->default(0);
            $table->unsignedInteger('unpub_date')->default(0);
            $table->unsignedInteger('parent')->default(0)->index("{$indexPrefix}_parent");
            $table->unsignedInteger('isfolder')->default(0);
            $table->text('introtext')->nullable()->comment('Used to provide quick summary of the document');
            $table->longText('content')->nullable();
            $table->boolean('richtext')->default(1);
            $table->unsignedInteger('template')->default(0);
            $table->unsignedInteger('menuindex')->default(0);
            $table->unsignedInteger('searchable')->default(1);
            $table->unsignedInteger('cacheable')->default(1);
            $table->unsignedInteger('createdby')->default(0);
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedby')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->unsignedInteger('deleted')->default(0);
            $table->unsignedInteger('deletedon')->default(0);
            $table->unsignedInteger('deletedby')->default(0);
            $table->unsignedInteger('publishedon')->default(0)->comment('Date the document was published');
            $table->unsignedInteger('publishedby')->default(0)->comment('ID of user who published the document');
            $table->string('menutitle')->default('')->comment('Menu title');
            $table->boolean('hide_from_tree')->default(0)->comment('Hide from tree view');
            $table->boolean('privateweb')->default(0)->comment('Private web document');
            $table->boolean('privatemgr')->default(0)->comment('Private manager document');
            $table->boolean('content_dispo')->default(0)->comment('0-inline, 1-attachment');
            $table->boolean('hidemenu')->default(0)->comment('Hide document from menu');
            $table->unsignedInteger('alias_visible')->default(1);

            $table->index(['pub_date', 'unpub_date', 'published'], "{$indexPrefix}_pub_unpub_published_idx");
            $table->index(['pub_date', 'unpub_date'], "{$indexPrefix}_pub_unpub_idx");
            $table->index(['unpub_date'], "{$indexPrefix}_unpub_idx");
            $table->index(['pub_date'], "{$indexPrefix}_pub_idx");
            $table->index('template', "{$indexPrefix}_template_idx");
            $table->index('createdby', "{$indexPrefix}_createdby_idx");
            $table->index('editedby', "{$indexPrefix}_editedby_idx");

            if ($isMySql) {
                $table->fullText(['pagetitle', 'description', 'content'], 'content_ft_idx');
            }
        });

        $this->createTableIfMissing('site_content_closure', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Content hierarchy closure table - stores hierarchical relationships between documents for efficient tree queries');
            $table->increments('closure_id');
            $table->unsignedInteger('ancestor');
            $table->unsignedInteger('descendant');
            $table->unsignedInteger('depth');

            $table->index('ancestor', "{$indexPrefix}_closure_ancestor_idx");
            $table->index('descendant', "{$indexPrefix}_closure_descendant_idx");
            $table->index('depth', "{$indexPrefix}_closure_depth_idx");
            $table->unique(['ancestor', 'descendant'], "{$indexPrefix}_ix_unique_path");
            $table->index(['descendant', 'ancestor'], "{$indexPrefix}_closure_desc_anc_idx");
        });

        /*
        |--------------------------------------------------------------------------
        | The snippet's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_snippets', function(Blueprint $table)
        {
            $table->comment('PHP snippets - stores reusable PHP code snippets that can be called from templates and chunks');
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->string('description')->default('Snippet');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->unsignedInteger('cache_type')->default(0)->comment('Cache option');
            $table->mediumText('snippet')->nullable();
            $table->boolean('locked')->default(0);
            $table->text('properties')->nullable()->comment('Default Properties');
            $table->string('moduleguid', 32)->default('')->comment('GUID of module from which to import shared parameters');
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->boolean('disabled')->default(0)->comment('Disables the snippet');
        });

        $this->createTableIfMissing('site_htmlsnippets', function(Blueprint $table)
        {
            $table->comment('HTML chunks - stores reusable HTML/plain text chunks that can be included in templates');
            $table->increments('id');
            $table->string('name', 100)->default('');
            $table->string('description')->default('Chunk');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->string('editor_name', 50)->default('none');
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->boolean('cache_type')->default(0)->comment('Cache option');
            $table->mediumText('snippet')->nullable();
            $table->boolean('locked')->default(0);
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->boolean('disabled')->default(0)->comment('Disables the snippet');
        });

        /*
        |--------------------------------------------------------------------------
        | The module's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_modules', function(Blueprint $table)
        {
            $table->comment('System modules - stores module definitions and code for extended functionality');
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->string('description')->default('0');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->boolean('disabled')->default(0);
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->boolean('wrap')->default(0);
            $table->boolean('locked')->default(0);
            $table->string('icon')->default('')->comment('url to module icon');
            $table->boolean('enable_resource')->default(0)->comment('enables the resource file feature');
            $table->string('resourcefile')->default('')->comment('a physical link to a resource file');
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->string('guid', 32)->nullable()->comment('globally unique identifier');
            $table->boolean('enable_sharedparams')->default(0);
            $table->text('properties')->nullable();
            $table->mediumText('modulecode')->nullable()->comment('module boot up code');
        });

        $this->createTableIfMissing('site_module_access', function(Blueprint $table)
        {
            $table->comment('Module access control - defines which user groups have access to specific modules');
            $table->increments('id');
            $table->unsignedInteger('module')->default(0);
            $table->unsignedInteger('usergroup')->default(0);
        });

        $this->createTableIfMissing('site_module_depobj', function(Blueprint $table)
        {
            $table->comment('Module dependencies - stores relationships between modules and other system objects (chunks, docs, plugins, snippets, templates, TVs)');
            $table->increments('id');
            $table->unsignedInteger('module')->default(0);
            $table->unsignedInteger('resource')->default(0);
            $table->unsignedInteger('type')->default(0)->comment('10-chunks, 20-docs, 30-plugins, 40-snips, 50-tpls, 60-tvs');
        });

        /*
        |--------------------------------------------------------------------------
        | The plugin's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_plugins', function(Blueprint $table)
        {
            $table->comment('System plugins - stores plugin code that hooks into system events');
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->string('description')->default('Plugin');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->boolean('cache_type')->default(0)->comment('Cache option');
            $table->mediumText('plugincode')->nullable();
            $table->boolean('locked')->default(0);
            $table->text('properties')->nullable()->comment('Default Properties');
            $table->boolean('disabled')->default(0)->comment('Disables the plugin');
            $table->string('moduleguid', 32)->nullable()->comment('GUID of module from which to import shared parameters');
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
        });

        $this->createTableIfMissing('site_plugin_events', function(Blueprint $table)
        {
            $table->comment('Plugin event bindings - defines which plugins are executed on specific system events and their execution priority');
            $table->unsignedInteger('pluginid');
            $table->unsignedInteger('evtid')->default(0);
            $table->unsignedInteger('priority')->default(0)->comment('determines plugin run order');
            $table->primary(['pluginid','evtid']);
        });

        /*
        |--------------------------------------------------------------------------
        | The template's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_templates', function(Blueprint $table)
        {
            $table->comment('Site templates - stores page templates that define the structure and layout of documents');
            $table->increments('id');
            $table->string('templatename')->default('');
            $table->string('templatealias')->default('');
            $table->string('description')->default('Template');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->string('icon')->default('')->comment('url to icon file');
            $table->unsignedInteger('template_type')->default(0)->comment('0-page,1-content');
            $table->mediumText('content')->nullable();
            $table->boolean('locked')->default(0);
            $table->boolean('selectable')->default(1);
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
        });

        /*
        |--------------------------------------------------------------------------
        | The tv's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('site_tmplvars', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Template variables (TVs) - defines custom fields that can be attached to templates and have values per document');
            $table->increments('id');
            $table->string('type', 50)->default('');
            $table->string('name', 50)->default('');
            $table->string('caption', 80)->default('');
            $table->string('description')->default('');
            $table->unsignedInteger('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
            $table->unsignedInteger('category')->default(0)->comment('category id');
            $table->boolean('locked')->default(0);
            $table->text('elements')->nullable();
            $table->unsignedInteger('rank')->default(0)->index("{$indexPrefix}_indx_rank");
            $table->string('display', 32)->nullable()->comment('Display Control');
            $table->text('display_params')->nullable()->comment('Display Control Properties');
            $table->text('default_text')->nullable();
            $table->unsignedInteger('createdon')->default(0);
            $table->unsignedInteger('editedon')->default(0);
            $table->text('properties')->nullable();
        });

        $this->createTableIfMissing('site_tmplvar_access', function(Blueprint $table)
        {
            $table->comment('Template variable access control - defines which document groups can access specific template variables');
            $table->increments('id');
            $table->unsignedInteger('tmplvarid')->default(0);
            $table->unsignedInteger('documentgroup')->default(0);
        });

        $this->createTableIfMissing('site_tmplvar_contentvalues', function(Blueprint $table) use ($isMySql)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->comment('Template variable values - stores the actual values of template variables for each document');
            $table->increments('id');
            $table->unsignedInteger('tmplvarid')->default(0)->index("{$indexPrefix}_idx_tmplvarid")->comment('Template Variable id');
            $table->unsignedInteger('contentid')->default(0)->index("{$indexPrefix}_idx_id")->comment('Site Content Id');
            $table->mediumText('value')->nullable();
            $table->unique(['tmplvarid','contentid'], "{$indexPrefix}_ix_tvid_contentid");

            if ($isMySql) {
                $table->fullText(['value'], "{$indexPrefix}_ix_content_ft");
            }
        });

        $this->createTableIfMissing('site_tmplvar_templates', function(Blueprint $table)
        {
            $table->comment('Template variable to template bindings - many-to-many relationship defining which TVs are available on which templates');
            $table->unsignedInteger('tmplvarid')->default(0)->comment('Template Variable id');
            $table->unsignedInteger('templateid')->default(0);
            $table->unsignedInteger('rank')->default(0);
            $table->primary(['tmplvarid','templateid']);
        });

        /*
        |--------------------------------------------------------------------------
        | The event's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('system_eventnames', function(Blueprint $table)
        {
            $table->comment('System event names - defines available system events that plugins can hook into');
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->unsignedInteger('service')->default(0)->comment('System Service number');
            $table->string('groupname', 20)->default('');
        });

        /*
        |--------------------------------------------------------------------------
        | The settings's tables structure
        |--------------------------------------------------------------------------
        */
        $this->createTableIfMissing('system_settings', function(Blueprint $table)
        {
            $table->comment('System configuration settings - stores all system-wide configuration options and settings');
            $table->string('setting_name', 50)->default('')->primary();
            $table->longText('setting_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | The user's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_attributes');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_role_vars');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('user_values');
        Schema::dropIfExists('active_user_locks');
        Schema::dropIfExists('active_user_sessions');
        Schema::dropIfExists('active_users');

        /*
        |--------------------------------------------------------------------------
        | The categories tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('categories');

        /*
        |--------------------------------------------------------------------------
        | The document group's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('document_groups');
        Schema::dropIfExists('documentgroup_names');

        /*
        |--------------------------------------------------------------------------
        | The log's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('event_log');
        Schema::dropIfExists('manager_log');

        /*
        |--------------------------------------------------------------------------
        | The member group's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('member_groups');
        Schema::dropIfExists('membergroup_access');
        Schema::dropIfExists('membergroup_names');

        /*
        |--------------------------------------------------------------------------
        | The permission's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permissions_groups');
        Schema::dropIfExists('role_permissions');

        /*
        |--------------------------------------------------------------------------
        | The content's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_content');
        Schema::dropIfExists('site_content_closure');

        /*
        |--------------------------------------------------------------------------
        | The snippet's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_snippets');
        Schema::dropIfExists('site_htmlsnippets');

        /*
        |--------------------------------------------------------------------------
        | The module's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_modules');
        Schema::dropIfExists('site_module_access');
        Schema::dropIfExists('site_module_depobj');

        /*
        |--------------------------------------------------------------------------
        | The plugin's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_plugins');
        Schema::dropIfExists('site_plugin_events');

        /*
        |--------------------------------------------------------------------------
        | The template's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_templates');

        /*
        |--------------------------------------------------------------------------
        | The tv's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('site_tmplvars');
        Schema::dropIfExists('site_tmplvar_access');
        Schema::dropIfExists('site_tmplvar_contentvalues');
        Schema::dropIfExists('site_tmplvar_templates');

        /*
        |--------------------------------------------------------------------------
        | The event's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('system_eventnames');

        /*
        |--------------------------------------------------------------------------
        | The settings's tables structure
        |--------------------------------------------------------------------------
        */
        Schema::dropIfExists('system_settings');
    }
};
