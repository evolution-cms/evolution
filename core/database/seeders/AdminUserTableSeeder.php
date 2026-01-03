<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the initial administrator user (if missing).
 *
 * Safety:
 * - Does not delete or overwrite existing users.
 * - Creates the user only if a user with the configured username does not exist.
 * - Creates `user_attributes` only if missing for the user.
 */
class AdminUserTableSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     *
     * Creates the admin user (and its attributes) only if the user does not already exist.
     */
    public function run(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('user_attributes')) {
            return;
        }

        $username = (string) env('EVO_ADMIN_USERNAME', 'admin');
        $email = (string) env('EVO_ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('EVO_ADMIN_PASSWORD', 'admin');

        $roleId = 1;
        if (Schema::hasTable('user_roles')) {
            $adminRoleId = DB::table('user_roles')->where('name', 'Administrator')->value('id');
            if ($adminRoleId !== null) {
                $roleId = (int) $adminRoleId;
            } else {
                $minRoleId = DB::table('user_roles')->min('id');
                if ($minRoleId !== null) {
                    $roleId = (int) $minRoleId;
                }
            }
        }

        $now = time();

        DB::transaction(function () use ($username, $email, $password, $roleId, $now): void {
            $existingId = DB::table('users')->where('username', $username)->value('id');

            $usersData = [
                'username' => $username,
                'password' => md5($password),
                'cachepwd' => '',
                'refresh_token' => null,
                'access_token' => null,
                'valid_to' => null,
                'verified_key' => null,
            ];

            $userId = null;
            if ($existingId !== null) {
                $userId = (int) $existingId;
            } else {
                $usersColumns = Schema::getColumnListing('users');
                $usersInsert = array_intersect_key($usersData, array_flip($usersColumns));
                $userId = (int) DB::table('users')->insertGetId($usersInsert);
            }

            if ($userId <= 0) {
                return;
            }

            $attributesData = [
                'internalKey' => $userId,
                'fullname' => $username,
                'first_name' => null,
                'last_name' => null,
                'middle_name' => null,
                'role' => $roleId,
                'email' => $email,
                'phone' => '',
                'mobilephone' => '',
                'blocked' => 0,
                'blockeduntil' => 0,
                'blockedafter' => 0,
                'logincount' => 0,
                'lastlogin' => 0,
                'thislogin' => 0,
                'failedlogincount' => 0,
                'sessionid' => '',
                'dob' => null,
                'gender' => 0,
                'country' => '',
                'street' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'fax' => '',
                'photo' => '',
                'comment' => null,
                'createdon' => $now,
                'editedon' => $now,
                'verified' => 1,
            ];

            $attributesColumns = Schema::getColumnListing('user_attributes');
            $attributesInsert = array_intersect_key($attributesData, array_flip($attributesColumns));

            $attributesExists = DB::table('user_attributes')->where('internalKey', $userId)->exists();
            if (!$attributesExists) {
                DB::table('user_attributes')->insert($attributesInsert);
            }
        });
    }
}
