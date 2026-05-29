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
        Schema::create('countries', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('short_name')->nullable();

            $table->string('iso2')->nullable();

            $table->string('iso3')->nullable();

            $table->string('phone_code')->nullable();

            $table->string('currency')->nullable();

            $table->string('currency_symbol')->nullable();

            $table->string('capital')->nullable();

            $table->string('nationality')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};