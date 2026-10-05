<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id');
            $table->enum('media_type', ['image','video'])->default('image');
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('button_text')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('custom_url')->nullable();
            $table->boolean('open_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('language_id')->references('id')->on('languages')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
            $table->index(['language_id','status','sort_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('hero_slides'); }
};
