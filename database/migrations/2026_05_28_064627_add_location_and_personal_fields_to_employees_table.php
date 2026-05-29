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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('mobile');
            $table->date('date_of_birth')->nullable()->after('gender');
            
            $table->foreignId('country_id')
                  ->nullable()
                  ->after('joining_date')
                  ->constrained('countries')
                  ->onDelete('set null');

            $table->foreignId('state_id')
                  ->nullable()
                  ->after('country_id')
                  ->constrained('states')
                  ->onDelete('set null');

            $table->foreignId('city_id')
                  ->nullable()
                  ->after('state_id')
                  ->constrained('cities')
                  ->onDelete('set null');

            $table->string('address_line')->nullable()->after('city_id');
            $table->string('pincode')->nullable()->after('address_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['employees_country_id_foreign']);
            $table->dropForeign(['employees_state_id_foreign']);
            $table->dropForeign(['employees_city_id_foreign']);

            // Drop columns
            $table->dropColumn([
                'gender',
                'date_of_birth',
                'country_id',
                'state_id',
                'city_id',
                'address_line',
                'pincode'
            ]);
        });
    }
};
