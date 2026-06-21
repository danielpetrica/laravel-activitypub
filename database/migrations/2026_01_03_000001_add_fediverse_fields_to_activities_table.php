<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(table: 'activities', callback: static function (Blueprint $table): void {
            $table->foreignId(column: 'remote_actor_id')
                ->nullable()
                ->constrained(table: 'remote_actors')
                ->cascadeOnDelete();

            $table->boolean(column: 'is_incoming')->default(value: false);
        });
    }

    public function down(): void
    {
        Schema::table(table: 'activities', callback: static function (Blueprint $table): void {
            $table->dropForeign(['remote_actor_id']);
            $table->dropColumn(['remote_actor_id', 'is_incoming']);
        });
    }
};
