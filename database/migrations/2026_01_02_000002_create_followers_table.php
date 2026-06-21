<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(table: 'followers', callback: static function (Blueprint $table): void {
            $table->id();

            $table->foreignId(column: 'actor_id')
                ->constrained(table: 'actors')
                ->cascadeOnDelete();

            $table->foreignId(column: 'remote_actor_id')
                ->constrained(table: 'remote_actors')
                ->cascadeOnDelete();

            $table->string(column: 'status')->default(value: 'accepted');

            $table->timestamps();

            $table->unique(columns: ['actor_id', 'remote_actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(table: 'followers');
    }
};
