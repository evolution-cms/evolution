<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives the password-recovery token in `users.cachepwd` an expiry.
 *
 * Without it the token is a permanent login link: anybody holding an old recovery
 * mail can still use it years later.
 */
class AddCachepwdExpiryToUsers extends Migration {
    public function up() {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'cachepwd_valid_to')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('cachepwd_valid_to')->nullable()->after('cachepwd');
        });

        // From here on an empty deadline means "never expires" (pwd_repair_minutes = 0).
        // Tokens that already exist have no deadline recorded and would silently become
        // eternal, so they are cleared: their owners simply request a new link.
        DB::table('users')->where('cachepwd', '<>', '')->update([
            'cachepwd' => '',
            'cachepwd_valid_to' => null,
        ]);
    }

    public function down() {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'cachepwd_valid_to')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cachepwd_valid_to');
            });
        }
    }
}
