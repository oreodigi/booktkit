<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_access_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('credential_mode', 30)->default('ticket_only');
            $table->json('credential_types')->nullable();
            $table->boolean('collection_required')->default(false);
            $table->boolean('allow_ticket_qr_before_assignment')->default(true);
            $table->string('reentry_policy', 20)->default('none');
            $table->unsignedInteger('max_reentries')->nullable();
            $table->boolean('exit_scan_required')->default(false);
            $table->boolean('replacement_allowed')->default(true);
            $table->unsignedInteger('max_replacements')->nullable();
            $table->string('identity_verification_mode', 30)->default('ticket');
            $table->boolean('is_enabled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['organizer_id', 'is_enabled']);
        });

        Schema::create('credential_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('credential_type', 30);
            $table->string('batch_code', 80);
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organizer_id', 'batch_code']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('batch_id')->nullable()->constrained('credential_batches')->nullOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('identifier_hash', 64)->unique();
            $table->string('display_code', 40)->nullable();
            $table->string('status', 20)->default('unassigned');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status']);
            $table->index(['organizer_id', 'event_id', 'status']);
        });

        Schema::create('ticket_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_ticket_id')->constrained('issued_tickets')->cascadeOnDelete();
            $table->foreignId('credential_id')->unique()->constrained('credentials')->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->string('assigned_by_type', 20);
            $table->unsignedBigInteger('assigned_by_id');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason', 80)->nullable();
            $table->timestamps();
            $table->index(['issued_ticket_id', 'status']);
        });

        Schema::create('credential_replacements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('issued_ticket_id')->constrained('issued_tickets')->cascadeOnDelete();
            $table->foreignId('old_credential_id')->constrained('credentials')->restrictOnDelete();
            $table->foreignId('new_credential_id')->constrained('credentials')->restrictOnDelete();
            $table->string('reason', 80);
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id');
            $table->unsignedInteger('charge_amount')->default(0);
            $table->string('charge_currency', 3)->default('INR');
            $table->string('payment_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('new_credential_id');
            $table->index(['issued_ticket_id', 'created_at']);
        });

        Schema::create('event_access_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 60);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });

        Schema::create('event_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('event_access_zones')->nullOnDelete();
            $table->string('name', 100);
            $table->string('code', 60);
            $table->string('mode', 20)->default('entry_exit');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });

        Schema::create('access_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('issued_ticket_id')->nullable()->constrained('issued_tickets')->nullOnDelete();
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete();
            $table->foreignId('gate_id')->nullable()->constrained('event_gates')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('event_access_zones')->nullOnDelete();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 20);
            $table->string('result', 30);
            $table->string('reason_code', 60)->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('state_snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event_id', 'created_at']);
            $table->index(['issued_ticket_id', 'created_at']);
            $table->index(['credential_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('access_scans');
        Schema::dropIfExists('event_gates');
        Schema::dropIfExists('event_access_zones');
        Schema::dropIfExists('credential_replacements');
        Schema::dropIfExists('ticket_credentials');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('credential_batches');
        Schema::dropIfExists('event_access_policies');
    }
};
