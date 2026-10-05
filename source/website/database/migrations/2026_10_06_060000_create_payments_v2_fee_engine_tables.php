<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope')->default('global'); // global, event_type, organizer, event
            $table->string('event_type')->nullable();
            $table->string('sales_channel')->default('any'); // any, web, mobile, pos, admin
            $table->unsignedBigInteger('organizer_id')->nullable()->index();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->string('fee_type')->default('percentage'); // percentage, fixed, hybrid
            $table->decimal('percentage', 8, 4)->default(0);
            $table->unsignedBigInteger('fixed_amount')->default(0); // paise
            $table->unsignedBigInteger('per_ticket_amount')->default(0); // paise
            $table->unsignedBigInteger('minimum_amount')->nullable();
            $table->unsignedBigInteger('maximum_amount')->nullable();
            $table->string('fee_bearer')->default('included'); // included, additional
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['scope', 'event_type', 'sales_channel', 'is_active'], 'payment_fee_rules_lookup');
        });

        Schema::create('event_additional_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->index();
            $table->string('code', 60);
            $table->string('name');
            $table->string('calculation_type')->default('fixed'); // fixed, percentage
            $table->unsignedBigInteger('fixed_amount')->default(0); // paise
            $table->decimal('percentage', 8, 4)->default(0);
            $table->string('application')->default('per_order'); // per_order, per_ticket
            $table->string('fee_bearer')->default('customer'); // customer, organizer
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });

        Schema::create('payment_order_fee_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_order_id')->index();
            $table->unsignedBigInteger('fee_rule_id')->nullable()->index();
            $table->unsignedBigInteger('additional_fee_id')->nullable()->index();
            $table->string('code', 60);
            $table->string('name');
            $table->string('category', 40); // platform, pos, additional
            $table->string('bearer', 30);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('amount');
            $table->json('calculation_snapshot')->nullable();
            $table->timestamps();
            $table->foreign('payment_order_id')->references('id')->on('payment_orders')->cascadeOnDelete();
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('sales_channel', 30)->default('web')->after('gateway');
            $table->string('event_type', 30)->nullable()->after('sales_channel');
            $table->unsignedBigInteger('additional_fee_amount')->default(0)->after('platform_fee');
            $table->unsignedBigInteger('pos_fee')->default(0)->after('additional_fee_amount');
            $table->string('settlement_reason')->nullable()->after('settlement_mode');
        });
    }

    public function down()
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn(['sales_channel', 'event_type', 'additional_fee_amount', 'pos_fee', 'settlement_reason']);
        });
        Schema::dropIfExists('payment_order_fee_lines');
        Schema::dropIfExists('event_additional_fees');
        Schema::dropIfExists('payment_fee_rules');
    }
};
