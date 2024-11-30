<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_reference');
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->bigInteger('number');
            $table->string('address');
            $table->float('location_latitude');
            $table->float('location_longitude');
            $table->string('status');
            $table->integer('secret');
            $table->string('deliveryman_mail')->nullable();
            $table->string('deliveryman_name')->nullable();
            $table->string('deliveryman_surname')->nullable();
            $table->bigInteger('deliveryman_number')->nullable();
            $table->unsignedBigInteger('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
