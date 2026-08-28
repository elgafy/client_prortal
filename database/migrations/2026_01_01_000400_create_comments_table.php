<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // author
            $table->morphs('commentable'); // project|payment
            $table->text('body');
            $table->boolean('is_internal')->default(false); // internal notes are hidden from the portal
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
