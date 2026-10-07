<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloaded_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tweet_id');
            $table->string('variant');
            $table->string('disk')->default('downloads');
            $table->string('path')->nullable();
            $table->string('extension', 12)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('download_name');
            $table->timestamp('expires_at');
            $table->timestamp('ready_at')->nullable();
            $table->string('batch_id')->nullable();
            $table->timestamps();

            $table->unique(['tweet_id', 'variant']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloaded_files');
    }
};
