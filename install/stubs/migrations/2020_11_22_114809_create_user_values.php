<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_values', function (Blueprint $table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->bigIncrements('id');
            $table->integer('tmplvarid')->default(0)->index("{$indexPrefix}_tmplvarid_idx");
            $table->integer('userid')->default(0)->index("{$indexPrefix}_userid_idx");
            $table->mediumText('value')->nullable();
            $table->unique(['tmplvarid','userid'], "{$indexPrefix}_tmplvarid_userid");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_values');
    }
}
