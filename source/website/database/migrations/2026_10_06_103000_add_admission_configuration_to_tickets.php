<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('admission_pass_type', 30)->default('mobile_qr')->after('event_type');
            $table->boolean('collection_required')->default(false)->after('admission_pass_type');
            $table->boolean('allow_mobile_qr_before_assignment')->default(true)->after('collection_required');
            $table->boolean('exit_scan_required')->default(false)->after('allow_mobile_qr_before_assignment');
            $table->string('reentry_policy', 20)->default('none')->after('exit_scan_required');
            $table->unsignedInteger('max_reentries')->nullable()->after('reentry_policy');
            $table->boolean('replacement_allowed')->default(false)->after('max_reentries');
            $table->unsignedInteger('max_replacements')->nullable()->after('replacement_allowed');
            $table->unsignedInteger('replacement_fee_paise')->default(0)->after('max_replacements');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['admission_pass_type','collection_required','allow_mobile_qr_before_assignment','exit_scan_required','reentry_policy','max_reentries','replacement_allowed','max_replacements','replacement_fee_paise']);
        });
    }
};
