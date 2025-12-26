<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->nullOnDelete();

            $table->string('user_name', 70);
            $table->string('email', 255);
            $table->string('home_page', 255)->nullable();

            // Sanitized XHTML (allowlist)
            $table->longText('text_html');
            $table->longText('text_raw')->nullable();

            $table->enum('attachment_type', ['image', 'text'])->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();

            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['parent_id', 'created_at']);
            $table->index('created_at');
            $table->index('user_name');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
