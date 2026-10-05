<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\RefreshLegacyDatabase;
class ExampleTest extends TestCase
{
    use RefreshLegacyDatabase;
    public function test_legacy_schema_and_isolated_database_are_available(): void
    {
        $this->assertSame('booktkit_test', \Illuminate\Support\Facades\DB::connection()->getDatabaseName());
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('issued_tickets'));
        $this->assertDatabaseCount('customers', 0);
    }
}
