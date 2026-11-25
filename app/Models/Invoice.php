<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable(['monto_total', 'fecha_vencimiento', 'fecha_emision', 'fecha_pago', 'estado_pago', 'recargo_mora', 'id_contrato'])]
class Invoice extends Model
{
    protected $table = 'FACTURAS';

    protected $primaryKey = 'id_factura';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'id_contrato', 'id_contrato');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'id_factura', 'id_factura');
    }

    public function facturaAgua(): HasOne
    {
        return $this->hasOne(FacturaAgua::class, 'id_factura', 'id_factura');
    }

    public function facturaLuz(): HasOne
    {
        return $this->hasOne(FacturaLuz::class, 'id_factura', 'id_factura');
    }

    public function getTypeAttribute(): string
    {
        if ($this->facturaLuz()->exists()) {
            return 'electricidad';
        }

        if ($this->facturaAgua()->exists()) {
            return 'agua';
        }

        return 'alquiler';
    }

    public function getPeriodAttribute(): string
    {
        return Carbon::parse($this->fecha_emision)->format('Y-m');
    }

    public function getPreviousReadingAttribute(): ?float
    {
        return $this->detalleServicio('lectura_anterior');
    }

    public function getCurrentReadingAttribute(): ?float
    {
        return $this->detalleServicio('lectura_actual');
    }

    public function getRateAttribute(): ?float
    {
        $detalle = $this->detalleAguaOPrecio();

        return $detalle === null ? null : (float) $detalle;
    }

    public function getConsumptionAttribute(): ?float
    {
        $lecturaAnterior = $this->previous_reading;
        $lecturaActual = $this->current_reading;

        return $lecturaAnterior === null || $lecturaActual === null
            ? null
            : $lecturaActual - $lecturaAnterior;
    }

    public function getTotalAttribute(): float
    {
        $diasVencidos = max(0, Carbon::parse($this->fecha_vencimiento)->startOfDay()->diffInDays(now()->startOfDay(), false));
        $moraEstimada = $this->estado_pago === 'pendiente'
            ? min(config('rental.late_fee_max_days'), $diasVencidos) * config('rental.late_fee_per_day')
            : 0;

        return (float) $this->monto_total + (float) $this->recargo_mora + $moraEstimada;
    }

    private function detalleServicio(string $campo): ?float
    {
        $detalle = $this->facturaLuz()->first() ?? $this->facturaAgua()->first();

        return $detalle === null || $detalle->{$campo} === null ? null : (float) $detalle->{$campo};
    }

    private function detalleAguaOPrecio(): int|float|null
    {
        $detalleLuz = $this->facturaLuz()->first();
        if ($detalleLuz !== null) {
            return $detalleLuz->tarifa_kwh;
        }

        return $this->facturaAgua()->first()?->tarifa_m3;
    }
}
