<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('tenancy.database.central_connection'))->create('support_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained('support_requests')->cascadeOnDelete();
            $table->string('responder_name');
            $table->string('responder_email');
            $table->text('message');
            $table->boolean('sent_via_email')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection(config('tenancy.database.central_connection'))->dropIfExists('support_responses');
    }
};
