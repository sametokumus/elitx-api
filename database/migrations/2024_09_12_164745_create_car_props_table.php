<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarPropsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_props', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('car_id');
            $table->bigInteger('category_id');
            $table->bigInteger('brand_id');
            $table->bigInteger('serie_id');
            $table->bigInteger('model_id');
            $table->integer('year')->nullable();
            $table->bigInteger('fuel_id')->nullable();
            $table->bigInteger('gear_id')->nullable();
            $table->bigInteger('condition_id')->nullable();
            $table->bigInteger('body_type_id')->nullable();
            $table->bigInteger('traction_id')->nullable();
            $table->bigInteger('door_id')->nullable();
            $table->integer('km')->nullable();
            $table->integer('hp')->nullable();
            $table->integer('cc')->nullable();
            $table->text('color')->nullable();
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
        Schema::dropIfExists('car_props');
    }
}
