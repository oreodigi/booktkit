<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('currencies', function (Blueprint $table) {
      $table->id();
      $table->string('text')->nullable();
      $table->string('symbol')->nullable();
      $table->string('text_position')->nullable();
      $table->string('symbol_position')->nullable();
      $table->double('value', 15, 2)->default(0)->nullable();
      $table->tinyInteger('is_default')->default(0)->nullable();
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
    Schema::dropIfExists('currencies');
  }
};
