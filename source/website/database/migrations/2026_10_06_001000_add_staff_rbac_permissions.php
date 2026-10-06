<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up(){if(Schema::hasTable('organizer_staff')&&!Schema::hasColumn('organizer_staff','permissions'))Schema::table('organizer_staff',function(Blueprint $t){$t->json('permissions')->nullable()->after('role');});}
 public function down(){if(Schema::hasTable('organizer_staff')&&Schema::hasColumn('organizer_staff','permissions'))Schema::table('organizer_staff',function(Blueprint $t){$t->dropColumn('permissions');});}
};