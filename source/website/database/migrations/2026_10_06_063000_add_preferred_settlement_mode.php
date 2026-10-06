<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;use Illuminate\Support\Facades\DB;
return new class extends Migration{public function up():void{
 if(!Schema::hasColumn('organizer_payment_profiles','preferred_settlement_mode')){Schema::table('organizer_payment_profiles',fn(Blueprint $t)=>$t->string('preferred_settlement_mode',32)->default('booktkit_managed')->after('settlement_mode'));DB::table('organizer_payment_profiles')->update(['preferred_settlement_mode'=>DB::raw('settlement_mode')]);}
}public function down():void{if(Schema::hasColumn('organizer_payment_profiles','preferred_settlement_mode'))Schema::table('organizer_payment_profiles',fn(Blueprint $t)=>$t->dropColumn('preferred_settlement_mode'));}};