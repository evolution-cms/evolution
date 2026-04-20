<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('member_groups', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
			$table->integer('id', true);
			$table->integer('user_group')->default(0);
			$table->integer('member')->default(0);
			$table->unique(['user_group','member'], "{$indexPrefix}_ix_group_member");
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('member_groups');
	}

}
