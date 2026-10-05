<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('issued_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('organizer_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('ticket_type_id')->nullable();
            $table->string('legacy_unique_id')->nullable();
            $table->string('ticket_name')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->string('checked_in_by_type', 20)->nullable();
            $table->unsignedBigInteger('checked_in_by_id')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'legacy_unique_id']);
            $table->index(['event_id', 'status']);
            $table->index(['organizer_id', 'event_id']);
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        Schema::create('ticket_admission_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issued_ticket_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('event_id');
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id');
            $table->string('result', 30);
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['issued_ticket_id', 'created_at']);
            $table->foreign('issued_ticket_id')->references('id')->on('issued_tickets')->cascadeOnDelete();
            $table->foreign('booking_id')->references('id')->on('event_bookings')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_admission_logs');
        Schema::dropIfExists('issued_tickets');
    }
};
