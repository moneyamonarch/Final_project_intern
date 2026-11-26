<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the portfolios table and its search indexes. */
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table): void {
            $table->unsignedInteger('portfolio_id')->primary();
            $table->string('dataset');
            $table->string('code')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedTinyInteger('sdg_num')->nullable();
            $table->string('focus_group');
            $table->string('focus_area');
            $table->string('category');
            $table->string('layer')->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->boolean('is_overview');
            $table->text('document_url')->nullable();

            $table->index('dataset');
            $table->index('sdg_num');
            $table->index('focus_area');
            $table->index('total');
        });
    }

    /** Remove the portfolios table. */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
