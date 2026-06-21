<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(table: 'activities', callback: static function (Blueprint $table): void {
            $table->id();

            $table->foreignId(column: 'actor_id')
                ->constrained(table: 'actors')
                ->cascadeOnDelete();

            $table->string(column: 'type');
            $table->string(column: 'object_type')->nullable();
            $table->string(column: 'object_id')->nullable();
            $table->json(column: 'payload');
            $table->string(column: 'status')->default(value: 'pending');
            $table->timestamp(column: 'delivered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(table: 'activities');
    }
};
