<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('category');
            $table->string('pattern');
            $table->timestamps();

            $table->unique(['category', 'pattern']);
            $table->index('pattern');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_rules');
    }
};
