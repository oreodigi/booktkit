<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Normalize legacy Box Office events that were stored as venue + flag.
        DB::table('events')
            ->where('event_type', 'venue')
            ->where('box_office_enabled', 1)
            ->update(['event_type' => 'box_office']);
    }

    public function down()
    {
        DB::table('events')
            ->where('event_type', 'box_office')
            ->where('box_office_enabled', 1)
            ->update(['event_type' => 'venue']);
    }
};
