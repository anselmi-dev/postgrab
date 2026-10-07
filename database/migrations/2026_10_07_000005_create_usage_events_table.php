<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('tweet_id')->nullable();
            $table->string('driver')->nullable();
            $table->boolean('success')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('units')->default(1);
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index('tweet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
