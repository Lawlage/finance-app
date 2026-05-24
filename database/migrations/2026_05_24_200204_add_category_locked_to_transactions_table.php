<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->boolean('category_locked')->default(false)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('category_locked');
        });
    }
};
