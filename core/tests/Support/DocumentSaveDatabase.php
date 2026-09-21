<?php

namespace Tests\Support;

use EvolutionCMS\Services\DocumentSave\DocumentSaveContext;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;

/**
 * An in-memory SQLite copy of every table the document save touches, plus a
 * DocumentSaveContext that answers from arrays instead of the manager session.
 */
final class DocumentSaveDatabase
{
    public static function boot(): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        // model events, so the closure table hooks of SiteContent run as they do in the manager
        $capsule->setEventDispatcher(new Dispatcher($capsule->getContainer()));
        $capsule->bootEloquent();

        $container = $capsule->getContainer();
        $container->instance('db', $capsule->getDatabaseManager());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        Model::setConnectionResolver($capsule->getDatabaseManager());
        // a model boots once per process; re-boot so its listeners land on this dispatcher
        Model::clearBootedModels();

        $schema = $capsule->getConnection()->getSchemaBuilder();

        $schema->create('site_content', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->default('document');
            $table->string('contentType')->default('text/html');
            $table->string('pagetitle')->default('');
            $table->string('longtitle')->default('');
            $table->string('description')->default('');
            $table->string('alias')->default('');
            $table->string('link_attributes')->default('');
            $table->integer('published')->default(0);
            $table->integer('pub_date')->default(0);
            $table->integer('unpub_date')->default(0);
            $table->integer('parent')->default(0);
            $table->integer('isfolder')->default(0);
            $table->text('introtext')->nullable();
            $table->text('content')->nullable();
            $table->integer('richtext')->default(1);
            $table->integer('template')->default(0);
            $table->integer('menuindex')->default(0);
            $table->integer('searchable')->default(1);
            $table->integer('cacheable')->default(1);
            $table->integer('createdby')->default(0);
            $table->integer('createdon')->default(0);
            $table->integer('editedby')->default(0);
            $table->integer('editedon')->default(0);
            $table->integer('deleted')->default(0);
            $table->integer('deletedon')->default(0);
            $table->integer('deletedby')->default(0);
            $table->integer('publishedon')->default(0);
            $table->integer('publishedby')->default(0);
            $table->string('menutitle')->default('');
            $table->integer('hide_from_tree')->default(0);
            $table->integer('privateweb')->default(0);
            $table->integer('privatemgr')->default(0);
            $table->integer('content_dispo')->default(0);
            $table->integer('hidemenu')->default(0);
            $table->integer('alias_visible')->default(1);
        });
        $schema->create('site_content_closure', function (Blueprint $table) {
            $table->increments('closure_id');
            $table->integer('ancestor');
            $table->integer('descendant');
            $table->integer('depth');
        });
        $schema->create('site_tmplvars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->default('text');
            $table->string('name')->default('');
            $table->text('default_text')->nullable();
            $table->integer('rank')->default(0);
        });
        $schema->create('site_tmplvar_templates', function (Blueprint $table) {
            $table->integer('tmplvarid');
            $table->integer('templateid');
        });
        $schema->create('site_tmplvar_contentvalues', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tmplvarid');
            $table->integer('contentid');
            $table->text('value')->nullable();
            $table->unique(['tmplvarid', 'contentid']);
        });
        $schema->create('site_tmplvar_access', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tmplvarid');
            $table->integer('documentgroup');
        });
        $schema->create('document_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_group');
            $table->integer('document');
        });
        $schema->create('membergroup_access', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('membergroup');
            $table->integer('documentgroup');
            $table->integer('context')->default(0);
        });
        $schema->create('member_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_group');
            $table->integer('member');
        });

        return $capsule;
    }

    /**
     * @param array $config settings the save reads, e.g. use_udperms, friendly_urls
     * @param string[] $permissions granted manager permissions
     * @param int[] $userGroups document groups the user is a member of
     * @param array $events collects every fired event as [name, params, snapshot]
     * @param callable|null $snapshot called on every event, its result is stored next to the event
     */
    public static function context(
        array $config = [],
        array $permissions = [],
        int $role = 1,
        array $userGroups = [],
        array &$events = [],
        ?callable $snapshot = null,
        int $now = 1_700_000_000,
        ?callable $canCreateIn = null,
    ): DocumentSaveContext {
        return new DocumentSaveContext(
            userId: 7,
            role: $role,
            managerDocgroups: $userGroups,
            now: $now,
            config: fn (string $key) => $config[$key] ?? null,
            permission: fn (string $name) => in_array($name, $permissions, true),
            event: function (string $name, array $params) use (&$events, $snapshot) {
                $events[] = [$name, $params, $snapshot ? $snapshot() : null];
            },
            stripAlias: fn (string $alias) => preg_replace('/[^a-z0-9-]+/', '-', strtolower($alias)),
            toTimestamp: fn (string $date) => (int) strtotime($date),
            // like Core::getParentIds(): the ancestors of $id, not $id itself
            parentIds: function (int $id) {
                $ids = [];
                while ($id > 0) {
                    $id = (int) Capsule::table('site_content')->where('id', $id)->value('parent');
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                return $ids;
            },
            canCreateIn: $canCreateIn ?? fn (int $parent) => true,
            userGroupsLoader: fn () => $userGroups,
            lang: fn (string $key) => $key === 'duplicate_alias_found' ? 'duplicate %s %s' : $key,
        );
    }
}
