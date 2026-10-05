<?php
namespace Tests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
trait RefreshLegacyDatabase
{
    use RefreshDatabase;
    public function refreshDatabase()
    {
        if (config('database.connections.mysql.database') !== 'booktkit_test' || !app()->environment('testing')) {
            throw new \RuntimeException('Schema restoration is restricted to booktkit_test.');
        }
        if (!RefreshDatabaseState::$migrated) {
            DB::unprepared(file_get_contents(database_path('schema/mysql-schema.sql')));
            $baseline = json_decode(file_get_contents(database_path('schema/mysql-baseline.json')), true);
            foreach ($baseline as $migration) DB::table('migrations')->insert(['migration'=>$migration,'batch'=>1]);
            $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        $this->beginDatabaseTransaction();
    }
}
