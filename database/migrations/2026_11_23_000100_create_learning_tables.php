<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommendation_runs', function (Blueprint $t): void {
            $t->id();
            $t->string('algorithm_version');
            $t->json('weights');
            $t->json('profile')->nullable();
            $t->json('results');
            $t->string('share_token', 40)->nullable()->unique();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('feedback_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('run_id')->constrained('recommendation_runs')->cascadeOnDelete();
            $t->unsignedInteger('portfolio_id');
            $t->string('event'); // impression|click|save|thumbs_up|thumbs_down|dismiss
            $t->unsignedSmallInteger('rank')->nullable();
            $t->boolean('is_synthetic')->default(false);
            $t->timestamps();
            $t->index(['run_id', 'portfolio_id']);
        });
        Schema::create('recommender_weights', function (Blueprint $t): void {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->json('weights');
            $t->unsignedInteger('trained_on');
            $t->json('metrics')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommender_weights');
        Schema::dropIfExists('feedback_events');
        Schema::dropIfExists('recommendation_runs');
    }
};
