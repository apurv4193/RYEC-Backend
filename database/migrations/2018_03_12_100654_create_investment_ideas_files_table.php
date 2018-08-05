<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvestmentIdeasFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investment_ideas_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('investment_id');
            $table->tinyInteger('file_type')->default(1)->comment('1 - Photos, 2 - Videos, 3 - Documents (PDF Docx, ppt, xls, txt)');
            $table->string('file_name', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('investment_ideas_files');
    }
}
