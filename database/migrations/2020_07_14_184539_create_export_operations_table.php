<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->bigInteger('export_id');
            $table->bigInteger('ad_id');
            $table->longText('state_from');
            $table->longText('state_to');
            $table->string('status');
            $table->longText('error')->nullable();
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
        Schema::dropIfExists('export_operations');
    }
}
