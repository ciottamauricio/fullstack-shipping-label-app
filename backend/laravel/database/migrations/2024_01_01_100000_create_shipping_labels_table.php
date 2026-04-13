<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // EasyPost identifiers
            $table->string('easypost_shipment_id')->unique();
            $table->string('tracking_code')->nullable();

            // Carrier / service
            $table->string('carrier')->default('USPS');
            $table->string('service');

            // Label output
            $table->string('label_url');
            $table->enum('label_file_type', ['PDF', 'PNG', 'ZPL'])->default('PDF');

            // Cost
            $table->decimal('rate', 8, 2);

            // From address (US only)
            $table->string('from_name');
            $table->string('from_company')->nullable();
            $table->string('from_street1');
            $table->string('from_street2')->nullable();
            $table->string('from_city');
            $table->string('from_state', 2);
            $table->string('from_zip', 10);

            // To address (US only)
            $table->string('to_name');
            $table->string('to_company')->nullable();
            $table->string('to_street1');
            $table->string('to_street2')->nullable();
            $table->string('to_city');
            $table->string('to_state', 2);
            $table->string('to_zip', 10);

            // Package dimensions (inches) and weight (ounces)
            $table->decimal('weight', 8, 2);
            $table->decimal('length', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);

            $table->string('status')->default('purchased');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_labels');
    }
};
