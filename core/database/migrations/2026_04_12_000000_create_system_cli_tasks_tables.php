<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemCliTasksTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('system_cli_tasks')) {
            Schema::create('system_cli_tasks', function (Blueprint $table) {
                $indexPrefix = \DB::getTablePrefix() . $table->getTable();

                $table->increments('id');
                $table->string('uuid', 36)->unique("{$indexPrefix}_uuid");
                $table->string('type', 64)->index("{$indexPrefix}_type");
                $table->string('target', 191)->default('')->index("{$indexPrefix}_target");
                $table->string('requested_version', 191)->default('');
                $table->string('status', 32)->default('queued')->index("{$indexPrefix}_status");
                $table->string('step', 64)->default('');
                $table->unsignedSmallInteger('progress')->default(0);
                $table->string('message', 255)->default('');
                $table->longText('payload_json')->nullable();
                $table->longText('result_json')->nullable();
                $table->unsignedInteger('created_by')->nullable()->index("{$indexPrefix}_created_by");
                $table->string('locked_by', 191)->default('');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->dateTime('lease_expires_at')->nullable()->index("{$indexPrefix}_lease_expires_at");
                $table->string('worker_host', 191)->default('');
                $table->integer('worker_pid')->nullable();
                $table->string('error_code', 64)->default('')->index("{$indexPrefix}_error_code");
                $table->string('catalog_snapshot_hash', 64)->default('');
                $table->longText('requested_by_snapshot')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('heartbeat_at')->nullable();
                $table->dateTime('cancellation_requested_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->index(['status', 'created_at'], "{$indexPrefix}_status_created_at");
                $table->index(['type', 'status'], "{$indexPrefix}_type_status");
            });
        }

        if (!Schema::hasTable('system_cli_task_logs')) {
            Schema::create('system_cli_task_logs', function (Blueprint $table) {
                $indexPrefix = \DB::getTablePrefix() . $table->getTable();

                $table->increments('id');
                $table->unsignedInteger('task_id')->index("{$indexPrefix}_task_id");
                $table->unsignedInteger('seq')->default(0);
                $table->string('level', 16)->default('info');
                $table->string('step', 64)->default('');
                $table->text('message');
                $table->longText('context_json')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->index(['task_id', 'seq'], "{$indexPrefix}_task_seq");
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('system_cli_task_logs');
        Schema::dropIfExists('system_cli_tasks');
    }
}
