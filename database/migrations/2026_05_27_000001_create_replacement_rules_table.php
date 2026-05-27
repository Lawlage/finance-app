<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replacement_rules', function (Blueprint $table): void {
            $table->id();
            // Encrypted at rest (Laravel `encrypted` cast). Holds the literal
            // account number or personal name to find in transaction text.
            $table->text('value');
            // Friendly replacement shown to Claude, e.g. "Joint Savings".
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replacement_rules');
    }
};
