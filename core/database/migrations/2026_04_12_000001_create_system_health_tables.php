<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemHealthTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('system_scheduler_health')) {
            Schema::create('system_scheduler_health', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->dateTime('last_heartbeat_at')->nullable();
                $table->string('last_heartbeat_host', 191)->default('');
                $table->string('last_heartbeat_mode', 32)->default('');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('system_worker_health')) {
            Schema::create('system_worker_health', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->dateTime('last_worker_run_at')->nullable();
                $table->dateTime('last_worker_pick_at')->nullable();
                $table->dateTime('last_worker_success_at')->nullable();
                $table->dateTime('last_worker_failed_at')->nullable();
                $table->string('last_worker_error_code', 64)->default('');
                $table->string('last_worker_host', 191)->default('');
                $table->integer('last_worker_pid')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('system_worker_health');
        Schema::dropIfExists('system_scheduler_health');
    }
}
