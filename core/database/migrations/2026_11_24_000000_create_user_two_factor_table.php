<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-factor authentication: what each user has set up, and the event plugins add methods on.
 *
 * Not user_settings: those are merged into the configuration (so a template could print a secret),
 * and are rewritten as a whole by every save of the user form.
 *
 * The event name is added to a site that already has its event names; a new site gets it from the
 * seeder. Anonymous, so that it takes no autoloader entry.
 */
return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('user_two_factor')) {
            Schema::create('user_two_factor', function (Blueprint $table) {
                $table->comment('Two-factor authentication - the methods set up by users, recovery codes and per-user policy');
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('method', 32)->comment('Method key, @policy or @recovery');
                $table->text('data');
                $table->unsignedInteger('confirmed_at')->default(0)->comment('When an answer was first accepted, 0 - not yet');
                $table->unique(['user_id', 'method'], 'user_two_factor_user_method');
            });
        }

        if (Schema::hasTable('system_eventnames')
            && DB::table('system_eventnames')->exists()
            && !DB::table('system_eventnames')->where('name', 'OnTwoFactorMethods')->exists()) {
            DB::table('system_eventnames')->insert([
                'name' => 'OnTwoFactorMethods',
                'service' => 1,
                'groupname' => 'Users',
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_two_factor');

        if (Schema::hasTable('system_eventnames')) {
            DB::table('system_eventnames')->where('name', 'OnTwoFactorMethods')->delete();
        }
    }
};
