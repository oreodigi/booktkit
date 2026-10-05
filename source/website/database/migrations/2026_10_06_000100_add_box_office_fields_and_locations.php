<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('box_office_enabled')->default(false)->after('event_type');
            $table->string('reentry_policy', 20)->default('none')->after('box_office_enabled');
            $table->unsignedInteger('max_reentries')->nullable()->after('reentry_policy');
        });

        Schema::create('box_office_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'name']);
            $table->index(['event_id', 'active']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('box_office_locations');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['box_office_enabled', 'reentry_policy', 'max_reentries']);
        });
    }
};
