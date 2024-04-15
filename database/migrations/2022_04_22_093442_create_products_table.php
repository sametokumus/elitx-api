<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('owner_type')->default(1);
            $table->bigInteger('owner_id');
            $table->bigInteger('brand_id')->nullable();
            $table->text('sku');
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('thumbnail')->nullable();
            $table->bigInteger('stock_quantity')->default(0);
            $table->tinyInteger('has_variation')->default(0);
            $table->bigInteger('status_id')->default(1);
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
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
        Schema::dropIfExists('products');
    }
}
