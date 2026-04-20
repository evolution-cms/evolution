<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebUserAttributesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('web_user_attributes', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->integer('internalKey')->default(0)->index("{$indexPrefix}_internalKey");
			$table->string('fullname', 100)->default();
			$table->integer('role')->default(0);
			$table->string('email', 100)->default('');
			$table->string('phone', 100)->default('');
			$table->string('mobilephone', 100)->default('');
			$table->integer('blocked')->default(0);
			$table->integer('blockeduntil')->default(0);
			$table->integer('blockedafter')->default(0);
			$table->integer('logincount')->default(0);
			$table->integer('lastlogin')->default(0);
			$table->integer('thislogin')->default(0);
			$table->integer('failedlogincount')->default(0);
			$table->string('sessionid', 100)->default('');
			$table->integer('dob')->default(0);
			$table->integer('gender')->default(0)->comment('0 - unknown, 1 - Male 2 - female');
			$table->string('country', 25)->default('');
			$table->string('street')->default('');
			$table->string('city')->default('');
			$table->string('state', 25)->default('');
			$table->string('zip', 25)->default('');
			$table->string('fax', 100)->default('');
			$table->string('photo')->default('')->comment('link to photo');
			$table->text('comment', 65535)->nullable();
			$table->integer('createdon')->default(0);
			$table->integer('editedon')->default(0);
			$table->tinyInteger('verified')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('web_user_attributes');
	}

}
