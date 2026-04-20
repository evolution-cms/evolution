<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('web_users', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->string('username', 100)->default('')->unique("{$indexPrefix}_username_index");
			$table->string('password', 100)->default('');
			$table->string('cachepwd', 100)->default('')->comment('Store new unconfirmed password');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('web_users');
	}

}
