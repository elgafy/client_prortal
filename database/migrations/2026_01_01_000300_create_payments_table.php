<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount'); // whole currency units, no decimals
            $table->string('currency', 3);
            $table->date('payment_date');
            $table->string('method');
            $table->string('received_from')->nullable();
            $table->string('received_by')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('active'); // active|void
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
