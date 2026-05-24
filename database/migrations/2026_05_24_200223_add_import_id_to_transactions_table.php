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
            if (! Schema::hasColumn('transactions', 'import_id')) {
                $table->foreignId('import_id')->nullable()->after('raw_text')->constrained('imports')->nullOnDelete();
            } else {
                $table->foreign('import_id')->references('id')->on('imports')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['import_id']);
            $table->dropColumn('import_id');
        });
    }
};
