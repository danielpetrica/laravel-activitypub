<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(table: 'remote_actors', callback: static function (Blueprint $table): void {
            $table->string(column: 'shared_inbox_url', length: 255)->nullable()->after(column: 'inbox_url');
        });
    }

    public function down(): void
    {
        Schema::table(table: 'remote_actors', callback: static function (Blueprint $table): void {
            $table->dropColumn(columns: 'shared_inbox_url');
        });
    }
};
