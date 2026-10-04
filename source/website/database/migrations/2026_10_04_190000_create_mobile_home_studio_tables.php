<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('mobile_home_templates', function (Blueprint $table) {
      $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('template_key')->default('modern');
      $table->string('thumbnail')->nullable(); $table->boolean('is_active')->default(true); $table->json('design')->nullable(); $table->timestamps();
    });
    Schema::create('mobile_home_campaigns', function (Blueprint $table) {
      $table->id(); $table->foreignId('template_id')->constrained('mobile_home_templates')->cascadeOnDelete();
      $table->string('name'); $table->string('status')->default('draft'); $table->integer('priority')->default(0);
      $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable(); $table->json('targeting')->nullable();
      $table->boolean('is_default')->default(false); $table->timestamps();
    });
    Schema::create('mobile_home_sections', function (Blueprint $table) {
      $table->id(); $table->foreignId('campaign_id')->constrained('mobile_home_campaigns')->cascadeOnDelete();
      $table->string('type'); $table->string('title')->nullable(); $table->boolean('enabled')->default(true); $table->unsignedInteger('position')->default(0);
      $table->json('settings')->nullable(); $table->timestamps();
    });
    Schema::create('mobile_home_versions', function (Blueprint $table) {
      $table->id(); $table->foreignId('campaign_id')->constrained('mobile_home_campaigns')->cascadeOnDelete();
      $table->unsignedInteger('version'); $table->json('snapshot'); $table->foreignId('published_by')->nullable()->constrained('admins')->nullOnDelete();
      $table->timestamp('published_at')->nullable(); $table->timestamps(); $table->unique(['campaign_id','version']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('mobile_home_versions'); Schema::dropIfExists('mobile_home_sections'); Schema::dropIfExists('mobile_home_campaigns'); Schema::dropIfExists('mobile_home_templates');
  }
};
