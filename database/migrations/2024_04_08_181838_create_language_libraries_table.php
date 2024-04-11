<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguageLibrariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_libraries', function (Blueprint $table) {
            $table->id();
            $table->text('tr')->nullable();
            $table->text('en')->nullable();
            $table->text('de')->nullable();
            $table->text('platform')->nullable();
            $table->text('page')->nullable();
            $table->tinyInteger('placeholder')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('language_libraries');
    }
}
