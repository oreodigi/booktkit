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
    Schema::create('organizer_token_purchases', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('organizer_id');
      $table->unsignedBigInteger('ai_token_package_id');
      $table->string('invoice_no')->nullable();
      $table->string('ai_engine', 50);
      $table->unsignedBigInteger('ai_token_limit')->default(0);
      $table->unsignedInteger('ai_image_limit')->default(0);
      $table->string('status', 20)->default('pending');
      $table->decimal('price', 16, 2)->default(0);
      $table->string('payment_method')->nullable();
      $table->string('payment_status', 20)->default('pending');
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
    Schema::dropIfExists('organizer_token_purchases');
  }
};
