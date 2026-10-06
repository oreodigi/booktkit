<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(){Schema::table('access_scans',function(Blueprint $t){$t->string('override_reason')->nullable()->after('reason_code');$t->boolean('is_override')->default(false)->after('result');});}
 public function down(){Schema::table('access_scans',function(Blueprint $t){$t->dropColumn(['override_reason','is_override']);});}
};