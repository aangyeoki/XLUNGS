<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXrayDiagnoses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xray_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('image_path');
            $table->json('ai_result')->nullable();
            $table->string('diagnosis');
            $table->decimal('confidence', 5, 2)->default(0);
            $table->text('explanation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['diagnosis']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xray_diagnoses');
    }
}
