<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nexpush_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 512)->unique();
            $table->enum('platform', ['android', 'ios', 'web'])->default('android');
            $table->string('app_version', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'active']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexpush_tokens');
    }
};
