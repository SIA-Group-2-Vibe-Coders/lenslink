<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Make gallery_id nullable to support direct user-to-user chat
            $table->foreignId('gallery_id')->nullable()->change();
            
            // Add receiver_id for direct chat
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('gallery_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('receiver_id');
        });
    }
};
