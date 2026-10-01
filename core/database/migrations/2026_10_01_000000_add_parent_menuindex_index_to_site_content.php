<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes the children of a document in their menu order.
 *
 * Child listings (getDocumentChildren, getActiveChildren, the document tree)
 * filter by parent and sort by menuindex. With an index on parent alone the
 * database reads every child and sorts them before applying a limit; with
 * (parent, menuindex) it reads the first rows of the index in order.
 */
class AddParentMenuindexIndexToSiteContent extends Migration {
    public function up() {
        if (!Schema::hasTable('site_content') || Schema::hasIndex('site_content', ['parent', 'menuindex'])) {
            return;
        }

        Schema::table('site_content', function (Blueprint $table) {
            $table->index(['parent', 'menuindex'], \DB::getTablePrefix() . $table->getTable() . '_parent_menuindex');
        });
    }

    public function down() {
        if (Schema::hasTable('site_content') && Schema::hasIndex('site_content', ['parent', 'menuindex'])) {
            Schema::table('site_content', function (Blueprint $table) {
                $table->dropIndex(\DB::getTablePrefix() . $table->getTable() . '_parent_menuindex');
            });
        }
    }
}
