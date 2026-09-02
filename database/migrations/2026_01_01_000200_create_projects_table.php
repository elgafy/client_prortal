<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('subtotal'); // entered amount, before discounts
            $table->unsignedBigInteger('discount_total')->default(0); // computed sum of discounts, whole units
            $table->unsignedBigInteger('amount'); // final amount after discounts
            $table->string('currency', 3);
            $table->date('project_date')->nullable();
            $table->string('status')->default('active'); // active|completed|cancelled
            $table->string('link')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
