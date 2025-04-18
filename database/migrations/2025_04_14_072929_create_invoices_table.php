<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('invoices', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->integer('course_id')->default(0);
            $table->integer('student_id')->default(0);
            $table->integer('teacher_id')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('transaction_id')->default('')->comment('omise transaction id');
            $table->tinyInteger('status')->comment('0:pending 1:sent 2:paid success 3:paid faild')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->timestamp('updated_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'))->nullable(false);
            $table->index('teacher_id');
            $table->index('student_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
