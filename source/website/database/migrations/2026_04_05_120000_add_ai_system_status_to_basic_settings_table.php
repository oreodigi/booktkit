<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('basic_settings', 'ai_system_status')) {
                $table->tinyInteger('ai_system_status')->default(1)->after('pollinations_image_model');
            }
        });

        DB::table('basic_settings')
            ->whereNull('ai_system_status')
            ->update(['ai_system_status' => 1]);
    }

    public function down(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            if (Schema::hasColumn('basic_settings', 'ai_system_status')) {
                $table->dropColumn('ai_system_status');
            }
        });
    }
};

