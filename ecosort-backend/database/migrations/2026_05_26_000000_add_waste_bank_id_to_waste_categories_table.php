<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_categories', function (Blueprint $table) {
            $table->foreignId('waste_bank_id')->nullable()->after('id')->constrained('waste_banks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('waste_categories', function (Blueprint $table) {
            $table->dropForeign(['waste_bank_id']);
            $table->dropColumn('waste_bank_id');
        });
    }
};
