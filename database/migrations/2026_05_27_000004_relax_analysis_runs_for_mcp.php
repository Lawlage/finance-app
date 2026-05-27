<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table): void {
            // Analyses are now authored by the user's Claude client via MCP,
            // so the app no longer builds a prompt or knows the exact model.
            $table->text('prompt_used')->nullable()->change();
            $table->string('model')->default('claude (mcp)')->change();
        });
    }

    public function down(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table): void {
            $table->text('prompt_used')->nullable(false)->change();
            $table->string('model')->default(null)->change();
        });
    }
};
