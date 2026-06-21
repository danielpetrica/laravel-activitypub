<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(table: 'remote_actors', callback: static function (Blueprint $table): void {
            $table->id();

            $table->string(column: 'actor_url')->unique();
            $table->string(column: 'inbox_url');
            $table->text(column: 'public_key_pem')->nullable();
            $table->string(column: 'username');
            $table->string(column: 'domain');
            $table->string(column: 'name')->nullable();
            $table->string(column: 'icon_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(table: 'remote_actors');
    }
};
