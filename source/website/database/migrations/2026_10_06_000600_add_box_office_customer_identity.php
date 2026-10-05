<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(){Schema::table('bookings',function(Blueprint $t){$t->unsignedTinyInteger('customer_age')->nullable()->after('phone');$t->text('aadhaar_number_encrypted')->nullable()->after('customer_age');$t->string('aadhaar_last4',4)->nullable()->after('aadhaar_number_encrypted');$t->string('aadhaar_document')->nullable()->after('aadhaar_last4');$t->string('customer_photo')->nullable()->after('aadhaar_document');});}
 public function down(){Schema::table('bookings',function(Blueprint $t){$t->dropColumn(['customer_age','aadhaar_number_encrypted','aadhaar_last4','aadhaar_document','customer_photo']);});}
};