<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_orders', 'finalization_error')) {
                $table->text('finalization_error')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payment_orders', 'finalization_attempts')) {
                $table->unsignedInteger('finalization_attempts')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            foreach (['finalization_error', 'finalization_attempts'] as $column) {
                if (Schema::hasColumn('payment_orders', $column)) $table->dropColumn($column);
            }
        });
    }
};
