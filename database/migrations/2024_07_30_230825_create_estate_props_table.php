<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstatePropsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estate_props', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('estate_id');
            $table->bigInteger('estate_type')->nullable();
            $table->bigInteger('room_id')->nullable();
            $table->text('size')->nullable();
            $table->integer('building_age')->nullable();
            $table->bigInteger('floor_id')->nullable();
            $table->bigInteger('warming_id')->nullable();
            $table->tinyInteger('balcony')->default(0);
            $table->tinyInteger('furnished')->default(0);
            $table->decimal('dues')->nullable();
            $table->string('dues_currency')->nullable();
            $table->bigInteger('condition_id')->nullable();
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
        Schema::dropIfExists('estate_props');
    }
}
