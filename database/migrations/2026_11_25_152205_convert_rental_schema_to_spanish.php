<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasTable('USUARIO')) {
            return;
        }

        $this->removeLegacyConstraints();
        $this->convertUsers();
        $this->convertRooms();
        $this->convertContracts();
        $this->convertInvoices();
        $this->convertPayments();
        $this->restoreConstraints();
        $this->ensureSessionsTable();
    }

    public function down(): void
    {
        throw new RuntimeException('La conversión al ERD en español no se puede revertir automáticamente sin riesgo de pérdida de datos.');
    }

    private function removeLegacyConstraints(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['room_id']);
        });
        $this->dropIndexIfExists('contracts', 'contracts_user_id_foreign');
        $this->dropIndexIfExists('contracts', 'contracts_room_id_foreign');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['contract_id']);
            $table->dropUnique('invoices_contract_id_type_period_unique');
            $table->dropIndex('invoices_status_due_at_index');
        });
        $this->dropIndexIfExists('invoices', 'invoices_contract_id_foreign');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex('payments_status_created_at_index');
        });
        $this->dropIndexIfExists('payments', 'payments_invoice_id_foreign');
        $this->dropIndexIfExists('payments', 'payments_reviewed_by_foreign');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_dni_unique');
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropUnique('rooms_number_unique');
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (Schema::hasIndex($table, $index)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
        }
    }

    private function convertUsers(): void
    {
        Schema::rename('users', 'USUARIO');

        Schema::table('USUARIO', function (Blueprint $table): void {
            $table->renameColumn('id', 'id_usuario');
            $table->renameColumn('name', 'nombres');
            $table->renameColumn('email', 'correo');
            $table->renameColumn('email_verified_at', 'correo_verificado_en');
            $table->renameColumn('password', 'contrasena');
            $table->renameColumn('phone', 'telefono');
            $table->renameColumn('role', 'rol');
            $table->renameColumn('active', 'estado');
            $table->renameColumn('remember_token', 'token_recordar');
            $table->renameColumn('created_at', 'creado_en');
            $table->renameColumn('updated_at', 'actualizado_en');
            $table->string('apellidos')->default('');
            $table->string('rol', 20)->default('inquilino')->change();
        });

        foreach (DB::table('USUARIO')->select('id_usuario', 'nombres')->orderBy('id_usuario')->get() as $usuario) {
            $partes = preg_split('/\s+/u', trim((string) $usuario->nombres), 2) ?: [];
            DB::table('USUARIO')->where('id_usuario', $usuario->id_usuario)->update([
                'nombres' => $partes[0] ?? '',
                'apellidos' => $partes[1] ?? '',
            ]);
        }

        DB::table('USUARIO')->where('rol', 'admin')->update(['rol' => 'administrador']);
        DB::table('USUARIO')->where('rol', 'tenant')->update(['rol' => 'inquilino']);

        Schema::table('USUARIO', function (Blueprint $table): void {
            $table->unique('correo', 'usuario_correo_unico');
            $table->unique('dni', 'usuario_dni_unico');
        });
    }

    private function convertRooms(): void
    {
        Schema::rename('rooms', 'HABITACIONES');

        Schema::table('HABITACIONES', function (Blueprint $table): void {
            $table->renameColumn('id', 'id_habitacion');
            $table->renameColumn('number', 'numero');
            $table->renameColumn('description', 'descripcion');
            $table->renameColumn('rent', 'precio');
            $table->renameColumn('status', 'estado');
            $table->renameColumn('created_at', 'creado_en');
            $table->renameColumn('updated_at', 'actualizado_en');
            $table->string('estado', 20)->default('disponible')->change();
        });

        DB::table('HABITACIONES')->where('estado', 'available')->update(['estado' => 'disponible']);
        DB::table('HABITACIONES')->where('estado', 'occupied')->update(['estado' => 'ocupada']);
        DB::table('HABITACIONES')->where('estado', 'maintenance')->update(['estado' => 'mantenimiento']);

        Schema::table('HABITACIONES', function (Blueprint $table): void {
            $table->unique('numero', 'habitaciones_numero_unico');
        });
    }

    private function convertContracts(): void
    {
        Schema::rename('contracts', 'CONTRATOS');

        Schema::table('CONTRATOS', function (Blueprint $table): void {
            $table->renameColumn('id', 'id_contrato');
            $table->renameColumn('user_id', 'id_usuario');
            $table->renameColumn('room_id', 'id_habitacion');
            $table->renameColumn('starts_at', 'fecha_inicio');
            $table->renameColumn('ends_at', 'fecha_fin');
            $table->renameColumn('rent', 'precio_renta');
            $table->renameColumn('deposit', 'garantia');
            $table->renameColumn('due_day', 'dia_vencimiento');
            $table->renameColumn('status', 'estado');
            $table->renameColumn('created_at', 'creado_en');
            $table->renameColumn('updated_at', 'actualizado_en');
            $table->string('estado', 20)->default('activo')->change();
        });

        DB::table('CONTRATOS')->where('estado', 'active')->update(['estado' => 'activo']);
        DB::table('CONTRATOS')->where('estado', 'ended')->update(['estado' => 'finalizado']);
    }

    private function convertInvoices(): void
    {
        Schema::rename('invoices', 'FACTURAS');

        Schema::table('FACTURAS', function (Blueprint $table): void {
            $table->renameColumn('id', 'id_factura');
            $table->renameColumn('contract_id', 'id_contrato');
            $table->renameColumn('amount', 'monto_total');
            $table->renameColumn('due_at', 'fecha_vencimiento');
            $table->renameColumn('paid_at', 'fecha_pago');
            $table->renameColumn('late_fee', 'recargo_mora');
            $table->renameColumn('status', 'estado_pago');
            $table->renameColumn('created_at', 'creado_en');
            $table->renameColumn('updated_at', 'actualizado_en');
            $table->date('fecha_emision')->nullable();
            $table->string('estado_pago', 20)->default('pendiente')->change();
        });

        $this->createServiceInvoiceTables();

        foreach (DB::table('FACTURAS')->get() as $factura) {
            $fechaEmision = $factura->period.'-01';
            DB::table('FACTURAS')->where('id_factura', $factura->id_factura)->update(['fecha_emision' => $fechaEmision]);

            if ($factura->type === 'water') {
                DB::table('FACTURA_AGUA')->insert([
                    'id_factura' => $factura->id_factura,
                    'lectura_anterior' => $factura->previous_reading,
                    'lectura_actual' => $factura->current_reading,
                    'tarifa_m3' => $factura->rate,
                ]);
            } elseif ($factura->type === 'electricity') {
                DB::table('FACTURA_LUZ')->insert([
                    'id_factura' => $factura->id_factura,
                    'lectura_anterior' => $factura->previous_reading,
                    'lectura_actual' => $factura->current_reading,
                    'tarifa_kwh' => $factura->rate,
                ]);
            }
        }

        DB::table('FACTURAS')->where('estado_pago', 'pending')->update(['estado_pago' => 'pendiente']);
        DB::table('FACTURAS')->where('estado_pago', 'paid')->update(['estado_pago' => 'pagado']);
        DB::table('FACTURAS')->where('estado_pago', 'void')->update(['estado_pago' => 'anulado']);

        Schema::table('FACTURAS', function (Blueprint $table): void {
            $table->dropColumn(['type', 'period', 'previous_reading', 'current_reading', 'rate', 'consumption']);
        });

        Schema::table('FACTURAS', function (Blueprint $table): void {
            $table->date('fecha_emision')->nullable(false)->change();
            $table->index(['estado_pago', 'fecha_vencimiento'], 'facturas_estado_vencimiento_indice');
        });
    }

    private function convertPayments(): void
    {
        Schema::rename('payments', 'PAGO');

        Schema::table('PAGO', function (Blueprint $table): void {
            $table->renameColumn('id', 'id_pago');
            $table->renameColumn('invoice_id', 'id_factura');
            $table->renameColumn('amount', 'monto');
            $table->renameColumn('paid_at', 'fecha_pago');
            $table->renameColumn('voucher_path', 'ruta_voucher');
            $table->renameColumn('status', 'estado');
            $table->renameColumn('rejection_reason', 'motivo_rechazo');
            $table->renameColumn('reviewed_by', 'id_revisor');
            $table->renameColumn('created_at', 'creado_en');
            $table->renameColumn('updated_at', 'actualizado_en');
            $table->string('estado', 20)->default('pendiente')->change();
        });

        DB::table('PAGO')->where('estado', 'pending')->update(['estado' => 'pendiente']);
        DB::table('PAGO')->where('estado', 'approved')->update(['estado' => 'aprobado']);
        DB::table('PAGO')->where('estado', 'rejected')->update(['estado' => 'rechazado']);

        Schema::table('PAGO', function (Blueprint $table): void {
            $table->index(['estado', 'creado_en'], 'pago_estado_creado_indice');
        });
    }

    private function createServiceInvoiceTables(): void
    {
        Schema::create('FACTURA_AGUA', function (Blueprint $table): void {
            $table->id('id_facturaagua');
            $table->decimal('lectura_anterior', 12, 3)->nullable();
            $table->decimal('lectura_actual', 12, 3)->nullable();
            $table->decimal('tarifa_m3', 10, 4)->nullable();
            $table->foreignId('id_factura')->unique()->constrained('FACTURAS', 'id_factura')->cascadeOnDelete();
        });

        Schema::create('FACTURA_LUZ', function (Blueprint $table): void {
            $table->id('id_facturaluz');
            $table->decimal('lectura_anterior', 12, 3)->nullable();
            $table->decimal('lectura_actual', 12, 3)->nullable();
            $table->decimal('tarifa_kwh', 10, 4)->nullable();
            $table->foreignId('id_factura')->unique()->constrained('FACTURAS', 'id_factura')->cascadeOnDelete();
        });
    }

    private function restoreConstraints(): void
    {
        Schema::table('CONTRATOS', function (Blueprint $table): void {
            $table->foreign('id_usuario', 'contratos_usuario_fk')->references('id_usuario')->on('USUARIO')->restrictOnDelete();
            $table->foreign('id_habitacion', 'contratos_habitacion_fk')->references('id_habitacion')->on('HABITACIONES')->restrictOnDelete();
        });

        Schema::table('FACTURAS', function (Blueprint $table): void {
            $table->foreign('id_contrato', 'facturas_contrato_fk')->references('id_contrato')->on('CONTRATOS')->restrictOnDelete();
        });

        Schema::table('PAGO', function (Blueprint $table): void {
            $table->foreign('id_factura', 'pago_factura_fk')->references('id_factura')->on('FACTURAS')->restrictOnDelete();
            $table->foreign('id_revisor', 'pago_revisor_fk')->references('id_usuario')->on('USUARIO')->nullOnDelete();
        });
    }

    private function ensureSessionsTable(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
