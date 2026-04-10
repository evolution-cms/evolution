<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileGroupsTable extends Migration {
    public function up() {
        if (Schema::hasTable('file_groups')) {
            return;
        }

        Schema::create('file_groups', function(Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('id', true);
            $table->integer('document_group')->default(0)->index("{$indexPrefix}_document_group");
            $table->string('file', 255)->default('')->index("{$indexPrefix}_file");
            $table->unique(['document_group', 'file'], "{$indexPrefix}_ix_fg_id");
        });
    }
    public function down() {
        Schema::dropIfExists('file_groups');
    }
}
