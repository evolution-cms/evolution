<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembergroupNamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('membergroup_names', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->string('name', 245)->default('')->unique("{$indexPrefix}_name");
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('membergroup_names');
	}

}
