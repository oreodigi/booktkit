<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(){
  if(!Schema::hasTable('organizer_staff')) Schema::create('organizer_staff',function(Blueprint $t){$t->id();$t->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();$t->string('name');$t->string('username');$t->string('email')->nullable();$t->string('phone')->nullable();$t->string('password');$t->string('role',40);$t->boolean('active')->default(true);$t->boolean('must_change_password')->default(true);$t->timestamp('last_login_at')->nullable();$t->timestamps();$t->unique(['organizer_id','username'],'org_staff_username_unique');$t->unique(['organizer_id','email'],'org_staff_email_unique');$t->index(['organizer_id','active'],'org_staff_active_idx');});
  if(!Schema::hasTable('organizer_staff_assignments')) Schema::create('organizer_staff_assignments',function(Blueprint $t){$t->id();$t->foreignId('staff_id')->constrained('organizer_staff')->cascadeOnDelete();$t->foreignId('event_id')->constrained('events')->cascadeOnDelete();$t->foreignId('box_office_location_id')->nullable()->constrained('box_office_locations')->cascadeOnDelete();$t->timestamps();$t->unique(['staff_id','event_id','box_office_location_id'],'staff_event_location_unique');$t->index(['event_id','box_office_location_id'],'staff_event_location_idx');});
 }
 public function down(){Schema::dropIfExists('organizer_staff_assignments');Schema::dropIfExists('organizer_staff');}
};