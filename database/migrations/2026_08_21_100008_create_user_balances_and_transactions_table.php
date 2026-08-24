<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'outlet_id']);
        });

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // topup, deduction
            $table->unsignedInteger('amount');
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('outlet_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('balance')->default(0)->after('requested_role');
        });

        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('user_balances');
    }
};
