<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->string('openai_api_key', 255)->nullable();
            $table->string('openai_text_model', 255)->nullable();
            $table->string('openai_image_model', 255)->nullable();

            $table->string('gemini_api_key', 255)->nullable();
            $table->string('gemini_text_model', 255)->nullable();
            $table->string('gemini_image_model', 255)->nullable();

            $table->string('pollinations_secret_key', 255)->nullable();
            $table->string('pollinations_text_model', 255)->nullable();
            $table->string('pollinations_image_model', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn([
                'openai_api_key',
                'openai_text_model',
                'openai_image_model',
                'gemini_api_key',
                'gemini_text_model',
                'gemini_image_model',
                'pollinations_secret_key',
                'pollinations_text_model',
                'pollinations_image_model',
            ]);
        });
    }
};
