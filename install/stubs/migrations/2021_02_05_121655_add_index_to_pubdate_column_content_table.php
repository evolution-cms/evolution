<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPubdateColumnContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_content', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->index(['pub_date', 'unpub_date', 'published'], "{$indexPrefix}_pub_unpub_published_idx");
            $table->index(['pub_date', 'unpub_date'], "{$indexPrefix}_pub_unpub_idx");
            $table->index(['unpub_date'], "{$indexPrefix}_unpub_idx");
            $table->index(['pub_date'], "{$indexPrefix}_pub_idx");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_content', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->dropIndex(["{$indexPrefix}_pub_unpub_published_idx", "{$indexPrefix}_pub_unpub_idx",
                "{$indexPrefix}_unpub_idx", "{$indexPrefix}_pub_idx"]);
        });
    }
}
