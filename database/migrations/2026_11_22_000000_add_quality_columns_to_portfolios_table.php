<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('portfolios', function (Blueprint $table): void { $table->string('title_normalized')->nullable(); $table->boolean('summary_is_template')->default(false); $table->string('amount_unit')->nullable(); $table->index('title_normalized'); }); }
    public function down(): void { Schema::table('portfolios', function (Blueprint $table): void { $table->dropIndex(['title_normalized']); $table->dropColumn(['title_normalized','summary_is_template','amount_unit']); }); }
};
