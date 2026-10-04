<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(){
  Schema::create('organizer_payment_profiles', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('organizer_id')->unique();
   $t->string('settlement_mode')->default('booktkit_managed');
   $t->string('razorpay_account_id')->nullable()->unique();
   $t->string('razorpay_status')->default('not_started');
   $t->boolean('gst_verified')->default(false); $t->string('gstin',20)->nullable();
   $t->string('fee_type')->default('percentage'); $t->decimal('fee_value',12,2)->default(0);
   $t->decimal('fee_fixed',12,2)->default(0); $t->string('fee_bearer')->default('included');
   $t->boolean('split_enabled')->default(false); $t->json('metadata')->nullable(); $t->timestamps();
  });
  Schema::create('payment_orders', function(Blueprint $t){
   $t->id(); $t->uuid('uuid')->unique(); $t->unsignedBigInteger('booking_id')->nullable()->index();
   $t->unsignedBigInteger('event_id')->index(); $t->unsignedBigInteger('organizer_id')->nullable()->index();
   $t->string('gateway',30)->default('razorpay'); $t->string('gateway_order_id')->nullable()->unique();
   $t->string('gateway_payment_id')->nullable()->index(); $t->string('currency',3)->default('INR');
   $t->unsignedBigInteger('ticket_amount'); $t->unsignedBigInteger('platform_fee')->default(0);
   $t->unsignedBigInteger('tax_amount')->default(0); $t->unsignedBigInteger('customer_total');
   $t->unsignedBigInteger('organizer_amount')->default(0); $t->string('fee_bearer')->default('included');
   $t->string('settlement_mode')->default('booktkit_managed'); $t->string('status')->default('created');
   $t->string('idempotency_key')->unique(); $t->json('pricing_snapshot')->nullable(); $t->json('customer_snapshot')->nullable(); $t->json('gateway_payload')->nullable();
   $t->timestamp('paid_at')->nullable(); $t->unsignedBigInteger('refunded_amount')->default(0); $t->string('refund_status')->default('none'); $t->timestamp('reconciled_at')->nullable(); $t->timestamps();
  });
  Schema::create('payment_ledger_entries', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('payment_order_id')->index(); $t->string('entry_type',40);
   $t->string('account',40); $t->bigInteger('amount'); $t->string('currency',3)->default('INR');
   $t->string('reference')->nullable()->index(); $t->json('metadata')->nullable(); $t->timestamps();
  });
  Schema::create('payment_transfers', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('payment_order_id')->index(); $t->unsignedBigInteger('organizer_id')->index();
   $t->string('linked_account_id'); $t->string('gateway_transfer_id')->nullable()->unique();
   $t->unsignedBigInteger('amount'); $t->string('currency',3)->default('INR'); $t->string('status')->default('pending');
   $t->json('gateway_payload')->nullable(); $t->text('last_error')->nullable(); $t->unsignedInteger('attempts')->default(0); $t->timestamp('processed_at')->nullable(); $t->timestamps();
  });
  Schema::create('payment_refunds', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('payment_order_id')->index(); $t->string('gateway_refund_id')->nullable()->unique();
   $t->unsignedBigInteger('amount'); $t->string('currency',3)->default('INR'); $t->string('status')->default('created');
   $t->string('reason')->nullable(); $t->json('gateway_payload')->nullable(); $t->timestamp('processed_at')->nullable(); $t->timestamps();
   $t->foreign('payment_order_id')->references('id')->on('payment_orders')->cascadeOnDelete();
  });
  Schema::create('payment_webhook_events', function(Blueprint $t){
   $t->id(); $t->string('gateway',30); $t->string('event_id')->unique(); $t->string('event_type');
   $t->string('status')->default('received'); $t->json('payload'); $t->timestamp('processed_at')->nullable(); $t->text('error')->nullable(); $t->timestamps();
  });
 }
 public function down(){
  Schema::dropIfExists('payment_webhook_events'); Schema::dropIfExists('payment_refunds'); Schema::dropIfExists('payment_transfers');
  Schema::dropIfExists('payment_ledger_entries'); Schema::dropIfExists('payment_orders');
  Schema::dropIfExists('organizer_payment_profiles');
 }
};