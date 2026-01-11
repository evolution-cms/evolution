<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropWebuserGroupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('webgroup_access');
        Schema::dropIfExists('webgroup_names');
        Schema::dropIfExists('web_groups');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('web_groups', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('id', true);
            $table->integer('webgroup')->default(0);
            $table->integer('webuser')->default(0);
            $table->unique(['webgroup','webuser'], "{$indexPrefix}_ix_group_user");
        });

        Schema::create('webgroup_access', function(Blueprint $table)
        {
            $table->integer('id', true);
            $table->integer('webgroup')->default(0);
            $table->integer('documentgroup')->default(0);
        });

        Schema::create('webgroup_names', function(Blueprint $table)
        {
            $table->integer('id', true);
            $table->string('name', 245)->default('')->unique();
        });
    }
}
