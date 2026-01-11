<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentgroupNamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('documentgroup_names', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->string('name', 245)->default('')->unique("{$indexPrefix}_name");
			$table->integer('private_memgroup')->nullable()->default(0)->comment('determine whether the document group is private to manager users');
			$table->integer('private_webgroup')->nullable()->default(0)->comment('determines whether the document is private to web users');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('documentgroup_names');
	}

}
