<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['monto', 'fecha_pago', 'ruta_voucher', 'estado', 'motivo_rechazo', 'id_factura', 'id_revisor'])]
class Payment extends Model
{
    protected $table = 'PAGO';

    protected $primaryKey = 'id_pago';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'id_factura', 'id_factura');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_revisor', 'id_usuario');
    }
}
