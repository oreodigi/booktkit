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
        Schema::create('organizer_ai_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->string('ai_engine', 50);
            $table->bigInteger('ai_token_balance')->default(0);
            $table->bigInteger('ai_image_balance')->default(0);
            $table->bigInteger('total_ai_token_purchased')->default(0);
            $table->bigInteger('total_ai_image_purchased')->default(0);
            $table->bigInteger('total_ai_token_used')->default(0);
            $table->bigInteger('total_ai_image_used')->default(0);
            $table->timestamps();

            $table->unique(['organizer_id', 'ai_engine'], 'organizer_ai_engine_unique');
            $table->index('organizer_id');
            $table->index('ai_engine');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organizer_ai_balances');
    }
};
