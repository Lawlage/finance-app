<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->string('category')->nullable();
            $table->string('account');
            $table->text('raw_text');
            $table->timestamps();

            $table->index('date');
            $table->index('category');
            $table->index('account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
