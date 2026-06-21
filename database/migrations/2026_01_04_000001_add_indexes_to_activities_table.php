<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(table: 'activities', callback: static function (Blueprint $table): void {
            $table->index(columns: 'remote_actor_id');
            $table->index(columns: 'is_incoming');
            $table->index(columns: 'type');
            $table->index(columns: 'status');
            $table->index(columns: ['actor_id', 'is_incoming']);
        });
    }

    public function down(): void
    {
        Schema::table(table: 'activities', callback: static function (Blueprint $table): void {
            $table->dropIndex(index: 'activities_remote_actor_id_index');
            $table->dropIndex(index: 'activities_is_incoming_index');
            $table->dropIndex(index: 'activities_type_index');
            $table->dropIndex(index: 'activities_status_index');
            $table->dropIndex(index: 'activities_actor_id_is_incoming_index');
        });
    }
};
