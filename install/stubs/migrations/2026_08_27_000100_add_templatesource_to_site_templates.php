<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records where a template's code lives: the database, or a file.
 *
 * Until now a template whose alias happened to match a file under a view path
 * was rendered from that file whether or not anybody meant it to be, and there
 * was no way to say otherwise. 'db' says so, and costs nothing to check - the
 * lookup is skipped entirely rather than probing every view path for every
 * registered extension on every request.
 *
 * Empty keeps the old behaviour, which is what every existing template says.
 */
class AddTemplatesourceToSiteTemplates extends Migration {
    public function up() {
        if (!Schema::hasTable('site_templates') || Schema::hasColumn('site_templates', 'templatesource')) {
            return;
        }

        Schema::table('site_templates', function (Blueprint $table) {
            $table->string('templatesource', 10)->default('')->after('templatealias');
        });
    }

    public function down() {
        if (Schema::hasTable('site_templates') && Schema::hasColumn('site_templates', 'templatesource')) {
            Schema::table('site_templates', function (Blueprint $table) {
                $table->dropColumn('templatesource');
            });
        }
    }
}
