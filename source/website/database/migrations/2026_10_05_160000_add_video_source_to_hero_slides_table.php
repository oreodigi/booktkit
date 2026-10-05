<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->enum('video_source', ['upload','youtube','url'])->nullable()->after('video');
            $table->text('video_url')->nullable()->after('video_source');
        });
        DB::table('hero_slides')->where('media_type', 'video')->whereNotNull('video')->update(['video_source' => 'upload']);
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn(['video_source','video_url']);
        });
    }
};
