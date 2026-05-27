<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_access_logs', function (Blueprint $table): void {
            $table->id();
            // 'resource' | 'tool' | 'prompt'
            $table->string('primitive');
            // Resource URI or tool/prompt name.
            $table->string('name');
            // The exact sanitized payload that left the box.
            $table->longText('payload');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_access_logs');
    }
};
