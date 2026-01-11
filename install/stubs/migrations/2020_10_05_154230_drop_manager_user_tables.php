<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropManagerUserTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_attributes');
        Schema::dropIfExists('manager_users');
        Schema::dropIfExists('web_user_settings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('user_attributes', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('id', true);
            $table->integer('internalKey')->default(0)->index("{$indexPrefix}_userid");
            $table->string('fullname', 100)->default('');
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
            $table->string('country', 5)->default('');
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

        Schema::create('manager_users', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('id', true);
            $table->string('username', 100)->default('')->unique("{$indexPrefix}_username_index");
            $table->string('password', 100)->default('');
        });

        Schema::create('web_user_settings', function(Blueprint $table)
        {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('webuser')->index('webuserid');
            $table->string('setting_name', 50)->default('')->index("{$indexPrefix}_setting_name_index");
            $table->text('setting_value', 65535)->nullable();
            $table->primary(['webuser','setting_name']);
        });
    }
}
