<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            $table->bigInteger('product_id');
            $table->bigInteger('variation_id');
            $table->bigInteger('status_id')->default(1);
            $table->string('name');
            $table->string('sku');
            $table->decimal('regular_price');
            $table->decimal('regular_tax');
            $table->decimal('discounted_price');
            $table->decimal('discounted_tax');
            $table->decimal('discount_rate');
            $table->decimal('tax_rate');
            $table->decimal('user_discount');
            $table->integer('quantity');
            $table->decimal('total');
            $table->decimal('commission_rate')->default(0);
            $table->decimal('commission_total')->default(0);
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
        Schema::dropIfExists('order_products');
    }
}
