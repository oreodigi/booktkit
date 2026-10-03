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
    Schema::create('ai_token_packages', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->string('ai_engine', 50);
      $table->unsignedBigInteger('ai_token_limit')->default(0);
      $table->unsignedInteger('ai_image_limit')->default(0);
      $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
      $table->decimal('price', 16, 2)->default(0);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('ai_token_packages');
  }
};
