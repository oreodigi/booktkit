<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('organizer_payment_profiles',function(Blueprint $t){
   $t->boolean('split_suspended')->default(false);
   $t->timestamp('route_terms_accepted_at')->nullable();
  });
  Schema::table('payment_transfers',function(Blueprint $t){
   $t->unique('payment_order_id');
   $t->boolean('on_hold')->default(false);
   $t->timestamp('hold_release_at')->nullable()->index();
   $t->unsignedBigInteger('reversed_amount')->default(0);
   $t->timestamp('released_at')->nullable();
  });
  Schema::create('payment_settings',function(Blueprint $t){
   $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->timestamps();
  });
  DB::table('payment_settings')->insert(['key'=>'transfer_hold_days','value'=>'2','created_at'=>now(),'updated_at'=>now()]);
 }
 public function down(): void {
  Schema::dropIfExists('payment_settings');
  Schema::table('payment_transfers',function(Blueprint $t){$t->dropUnique(['payment_order_id']); $t->dropColumn(['on_hold','hold_release_at','reversed_amount','released_at']);});
  Schema::table('organizer_payment_profiles',function(Blueprint $t){$t->dropColumn(['split_suspended','route_terms_accepted_at']);});
 }
};