<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakRequestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_correction_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('break_time_id')->nullable()->constrained()->cascadeOnDelete();
            $table->datetime('after_start')->nullable();
            $table->datetime('after_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_request_details');
    }
}
