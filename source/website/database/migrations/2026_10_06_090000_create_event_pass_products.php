<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(){
  Schema::create('event_pass_products',function(Blueprint $t){$t->id();$t->uuid('uuid')->unique();$t->foreignId('event_id')->constrained('events')->cascadeOnDelete();$t->foreignId('organizer_id')->nullable()->constrained('organizers')->cascadeOnDelete();$t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();$t->string('code',80);$t->string('name',160);$t->string('pass_type',30);$t->string('selection_mode',30)->default('fixed_dates');$t->unsignedSmallInteger('choose_count')->nullable();$t->unsignedInteger('price');$t->string('inventory_type',20)->default('unlimited');$t->unsignedInteger('inventory_quantity')->nullable();$t->unsignedInteger('sold_quantity')->default(0);$t->unsignedSmallInteger('max_per_order')->default(10);$t->unsignedSmallInteger('admissions_per_holder')->default(1);$t->json('sales_channels')->nullable();$t->json('credential_types')->nullable();$t->boolean('active')->default(true);$t->unsignedSmallInteger('sort_order')->default(0);$t->json('metadata')->nullable();$t->timestamps();$t->unique(['event_id','code']);$t->index(['event_id','active','sort_order']);});
  Schema::create('event_pass_dates',function(Blueprint $t){$t->id();$t->foreignId('pass_product_id')->constrained('event_pass_products')->cascadeOnDelete();$t->foreignId('event_date_id')->constrained('event_dates')->cascadeOnDelete();$t->timestamps();$t->unique(['pass_product_id','event_date_id']);});
  Schema::table('issued_tickets',function(Blueprint $t){$t->foreignId('pass_product_id')->nullable()->after('ticket_type_id')->constrained('event_pass_products')->nullOnDelete();});
  Schema::create('pass_entitlements',function(Blueprint $t){$t->id();$t->foreignId('issued_ticket_id')->constrained('issued_tickets')->cascadeOnDelete();$t->foreignId('pass_product_id')->constrained('event_pass_products')->cascadeOnDelete();$t->foreignId('event_date_id')->constrained('event_dates')->cascadeOnDelete();$t->string('status',20)->default('active');$t->timestamps();$t->unique(['issued_ticket_id','event_date_id']);$t->index(['pass_product_id','event_date_id','status']);});
 }
 public function down(){Schema::dropIfExists('pass_entitlements');Schema::table('issued_tickets',function(Blueprint $t){$t->dropConstrainedForeignId('pass_product_id');});Schema::dropIfExists('event_pass_dates');Schema::dropIfExists('event_pass_products');}
};
