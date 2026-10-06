<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasTable('payment_settings')) Schema::create('payment_settings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->text('value')->nullable();$t->timestamps();});
  Schema::create('payment_fee_rules',function(Blueprint $t){$t->id();$t->string('name');$t->string('scope_type',24)->default('global')->index();$t->unsignedBigInteger('scope_id')->nullable()->index();$t->string('event_type',32)->nullable()->index();$t->string('sales_channel',24)->nullable()->index();$t->decimal('percentage',8,4)->default(0);$t->unsignedBigInteger('fixed_amount')->default(0);$t->unsignedBigInteger('minimum_fee')->nullable();$t->unsignedBigInteger('maximum_fee')->nullable();$t->string('fee_bearer',16)->default('organizer');$t->boolean('enabled')->default(true)->index();$t->unsignedInteger('priority')->default(0);$t->unsignedInteger('version')->default(1);$t->timestamp('effective_from')->nullable();$t->timestamp('effective_until')->nullable();$t->json('metadata')->nullable();$t->timestamps();});
  Schema::create('event_additional_fees',function(Blueprint $t){$t->id();$t->unsignedBigInteger('event_id')->index();$t->unsignedBigInteger('organizer_id')->nullable()->index();$t->string('code',64);$t->string('name');$t->string('calculation_type',24);$t->decimal('percentage',8,4)->default(0);$t->unsignedBigInteger('fixed_amount')->default(0);$t->string('fee_bearer',16)->default('customer');$t->boolean('mandatory')->default(false);$t->boolean('taxable')->default(false);$t->json('sales_channels')->nullable();$t->boolean('enabled')->default(true);$t->json('metadata')->nullable();$t->timestamps();$t->unique(['event_id','code']);});
  Schema::create('payment_order_fee_lines',function(Blueprint $t){$t->id();$t->unsignedBigInteger('payment_order_id')->index();$t->unsignedBigInteger('fee_rule_id')->nullable()->index();$t->unsignedBigInteger('additional_fee_id')->nullable()->index();$t->string('code',64);$t->string('name');$t->string('category',32);$t->string('calculation_type',24);$t->string('bearer',16);$t->unsignedInteger('quantity')->default(1);$t->unsignedBigInteger('base_amount')->default(0);$t->unsignedBigInteger('amount');$t->json('snapshot')->nullable();$t->timestamps();});
  Schema::table('payment_orders',function(Blueprint $t){$t->string('sales_channel',24)->default('web')->index()->after('gateway');$t->string('event_type',32)->nullable()->index()->after('event_id');$t->unsignedBigInteger('fee_rule_id')->nullable()->index()->after('platform_fee');$t->unsignedInteger('fee_rule_version')->nullable()->after('fee_rule_id');$t->unsignedBigInteger('additional_fee_total')->default(0)->after('platform_fee');$t->unsignedBigInteger('booktkit_revenue')->default(0)->after('organizer_amount');$t->string('settlement_reason')->nullable()->after('settlement_mode');});
  Schema::table('organizer_payment_profiles',function(Blueprint $t){$t->string('preferred_settlement_mode',32)->default('booktkit_managed')->after('settlement_mode');});
  DB::table('organizer_payment_profiles')->update(['preferred_settlement_mode'=>DB::raw('settlement_mode')]);
 }
 public function down(): void {
  Schema::table('organizer_payment_profiles',fn(Blueprint $t)=>$t->dropColumn('preferred_settlement_mode'));
  Schema::table('payment_orders',fn(Blueprint $t)=>$t->dropColumn(['sales_channel','event_type','fee_rule_id','fee_rule_version','additional_fee_total','booktkit_revenue','settlement_reason']));
  Schema::dropIfExists('payment_order_fee_lines');Schema::dropIfExists('event_additional_fees');Schema::dropIfExists('payment_fee_rules');
 }
};