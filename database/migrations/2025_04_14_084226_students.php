<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('username', 20)->default('')->nullable(false);
            $table->string('password', 64)->default('')->nullable(true);
            $table->string('name', 20)->default('')->nullable();
            $table->integer('teacher_id')->default(0)->nullable(false);
            $table->string('remember_token', 100)->nullable(true);
            $table->smallInteger('status')->default(1)->comment('1: enable 0: disable');
            $table->timestamp('created_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->timestamp('updated_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->index('username');
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
