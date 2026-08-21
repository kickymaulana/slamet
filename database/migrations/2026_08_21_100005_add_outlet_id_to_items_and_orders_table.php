<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('outlet_id')->default(1)->after('category_id')->constrained();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('outlet_id')->default(1)->after('user_id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });
    }
};
