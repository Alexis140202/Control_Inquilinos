<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('HABITACIONES', function (Blueprint $table) {
            $table->id('id_habitacion');
            $table->string('numero', 10)->unique();
            $table->string('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->string('estado', 20)->default('disponible');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('CONTRATOS', function (Blueprint $table) {
            $table->id('id_contrato');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('precio_renta', 10, 2);
            $table->decimal('garantia', 10, 2)->default(0);
            $table->unsignedTinyInteger('dia_vencimiento')->default(1);
            $table->string('estado', 20)->default('activo');
            $table->foreignId('id_usuario')->constrained('USUARIO', 'id_usuario')->restrictOnDelete();
            $table->foreignId('id_habitacion')->constrained('HABITACIONES', 'id_habitacion')->restrictOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('FACTURAS', function (Blueprint $table) {
            $table->id('id_factura');
            $table->decimal('monto_total', 10, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_emision');
            $table->date('fecha_pago')->nullable();
            $table->string('estado_pago', 20)->default('pendiente');
            $table->decimal('recargo_mora', 10, 2)->default(0);
            $table->foreignId('id_contrato')->constrained('CONTRATOS', 'id_contrato')->restrictOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->index(['estado_pago', 'fecha_vencimiento'], 'facturas_estado_vencimiento_indice');
        });

        Schema::create('FACTURA_AGUA', function (Blueprint $table) {
            $table->id('id_facturaagua');
            $table->decimal('lectura_anterior', 12, 3)->nullable();
            $table->decimal('lectura_actual', 12, 3)->nullable();
            $table->decimal('tarifa_m3', 10, 4)->nullable();
            $table->foreignId('id_factura')->unique()->constrained('FACTURAS', 'id_factura')->cascadeOnDelete();
        });

        Schema::create('FACTURA_LUZ', function (Blueprint $table) {
            $table->id('id_facturaluz');
            $table->decimal('lectura_anterior', 12, 3)->nullable();
            $table->decimal('lectura_actual', 12, 3)->nullable();
            $table->decimal('tarifa_kwh', 10, 4)->nullable();
            $table->foreignId('id_factura')->unique()->constrained('FACTURAS', 'id_factura')->cascadeOnDelete();
        });

        Schema::create('PAGO', function (Blueprint $table) {
            $table->id('id_pago');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('ruta_voucher');
            $table->string('estado', 20)->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('id_factura')->constrained('FACTURAS', 'id_factura')->restrictOnDelete();
            $table->foreignId('id_revisor')->nullable()->constrained('USUARIO', 'id_usuario')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->index(['estado', 'creado_en'], 'pago_estado_creado_indice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PAGO');
        Schema::dropIfExists('FACTURA_LUZ');
        Schema::dropIfExists('FACTURA_AGUA');
        Schema::dropIfExists('FACTURAS');
        Schema::dropIfExists('CONTRATOS');
        Schema::dropIfExists('HABITACIONES');
    }
};
