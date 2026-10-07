<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'gateway_payment_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('gateway_payment_id', 100)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'gateway_payment_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropUnique(['gateway_payment_id']);
                $table->dropColumn('gateway_payment_id');
            });
        }
    }
};
