<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tweet_id');
            $table->string('url');
            $table->string('author_handle');
            $table->string('text_excerpt', 280);
            $table->text('thumbnail_url')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tweet_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_requests');
    }
};
