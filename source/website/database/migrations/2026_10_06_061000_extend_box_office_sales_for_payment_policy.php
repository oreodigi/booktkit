<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{Schema::table('box_office_sales',function(Blueprint $t){$t->unsignedBigInteger('fee_rule_id')->nullable()->index()->after('platform_fee');$t->unsignedInteger('fee_rule_version')->nullable()->after('fee_rule_id');$t->string('fee_bearer',16)->nullable()->after('fee_rule_version');});}
 public function down():void{Schema::table('box_office_sales',fn(Blueprint $t)=>$t->dropColumn(['fee_rule_id','fee_rule_version','fee_bearer']));}
};