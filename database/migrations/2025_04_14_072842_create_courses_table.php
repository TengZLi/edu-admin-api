<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('courses', function (Blueprint $table) {

            $table->integerIncrements('id');
            $table->string('name', 255)->default('')->nullable(false);
            $table->smallInteger('year_month' )->default(0)->nullable(false); // 格式 YYYY-MM
            $table->decimal('fee', 10, 2)->default(0)->nullable(false);
            $table->integer('teacher_id')->default(0)->nullable(false);
            $table->timestamp('created_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->timestamp('updated_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->index('teacher_id');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
