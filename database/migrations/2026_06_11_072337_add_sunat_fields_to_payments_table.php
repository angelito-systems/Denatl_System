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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('billing_name')->nullable();
            $table->string('billing_document')->nullable();
            $table->string('sunat_serie')->nullable();
            $table->string('sunat_correlativo')->nullable();
            $table->string('sunat_status')->nullable();
            $table->string('sunat_hash')->nullable();
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->text('sunat_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'billing_name',
                'billing_document',
                'sunat_serie',
                'sunat_correlativo',
                'sunat_status',
                'sunat_hash',
                'sunat_xml_path',
                'sunat_cdr_path',
                'sunat_message'
            ]);
        });
    }
};
