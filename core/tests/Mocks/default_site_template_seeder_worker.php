<?php

namespace Illuminate\Database {
    class Seeder
    {
    }
}

namespace Illuminate\Support\Facades {
    final class Schema
    {
        public static function hasTable(string $table): bool
        {
            return $table === 'site_templates';
        }
    }

    final class DB
    {
        public static array $inserted = [];

        public static function table(string $table): FakeTable
        {
            return new FakeTable($table);
        }
    }

    final class FakeTable
    {
        public function __construct(private string $name)
        {
        }

        public function count(): int
        {
            return 0;
        }

        public function delete(): int
        {
            DB::$inserted[$this->name] = [];

            return 0;
        }

        public function insert(array $rows): void
        {
            DB::$inserted[$this->name] = $rows;
        }
    }
}

namespace {
    require $argv[1];

    $seederClass = $argv[2];
    (new $seederClass())->run();

    echo json_encode(
        \Illuminate\Support\Facades\DB::$inserted['site_templates'][0] ?? [],
        JSON_THROW_ON_ERROR
    );
}
