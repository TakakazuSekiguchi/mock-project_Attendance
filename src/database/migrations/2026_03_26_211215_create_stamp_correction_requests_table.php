<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStampCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->text('reason');
            $table->foreignId('attendance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->datetime('after_clock_in')->nullable();
            $table->datetime('after_clock_out')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
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
        Schema::dropIfExists('stamp_correction_requests');
    }
}
