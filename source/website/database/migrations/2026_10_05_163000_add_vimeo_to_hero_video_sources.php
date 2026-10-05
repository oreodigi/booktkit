<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE hero_slides MODIFY video_source ENUM('upload','youtube','vimeo','url') NULL");
    }

    public function down(): void
    {
        DB::table('hero_slides')->where('video_source', 'vimeo')->update(['video_source' => 'url']);
        DB::statement("ALTER TABLE hero_slides MODIFY video_source ENUM('upload','youtube','url') NULL");
    }
};
