<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which template engine a template renders with.
 *
 * A template whose alias matches a file under a view path is rendered from that
 * file, and until now which file won was decided by the order engines happened
 * to register with Laravel's view factory - it prepends, so the last plugin to
 * boot took precedence over every template on the site at once. The engine
 * chosen when the template was saved is stored here instead, so the answer
 * belongs to the template rather than to the boot order.
 *
 * Empty means "decide the old way", which is what every existing template says.
 */
class AddTemplatefileextensionToSiteTemplates extends Migration {
    public function up() {
        if (!Schema::hasTable('site_templates') || Schema::hasColumn('site_templates', 'templatefileextension')) {
            return;
        }

        Schema::table('site_templates', function (Blueprint $table) {
            $table->string('templatefileextension', 20)->default('')->after('templatealias');
        });
    }

    public function down() {
        if (Schema::hasTable('site_templates') && Schema::hasColumn('site_templates', 'templatefileextension')) {
            Schema::table('site_templates', function (Blueprint $table) {
                $table->dropColumn('templatefileextension');
            });
        }
    }
}
