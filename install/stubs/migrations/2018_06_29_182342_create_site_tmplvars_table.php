<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteTmplvarsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('site_tmplvars', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->string('type', 50)->default('');
			$table->string('name', 50)->default('');
			$table->string('caption', 80)->default('');
			$table->string('description')->default('');
			$table->integer('editor_type')->default(0)->comment('0-plain text,1-rich text,2-code editor');
			$table->integer('category')->default(0)->comment('category id');
			$table->boolean('locked')->default(0);
			$table->text('elements', 65535)->nullable();
			$table->integer('rank')->default(0)->index("{$indexPrefix}_indx_rank");
			$table->string('display', 20)->default('')->comment('Display Control');
			$table->text('display_params', 65535)->nullable()->comment('Display Control Properties');
			$table->text('default_text', 65535)->nullable();
			$table->integer('createdon')->default(0);
			$table->integer('editedon')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('site_tmplvars');
	}

}
