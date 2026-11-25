<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lectura_anterior', 'lectura_actual', 'tarifa_kwh', 'id_factura'])]
class FacturaLuz extends Model
{
    public $timestamps = false;

    protected $table = 'FACTURA_LUZ';

    protected $primaryKey = 'id_facturaluz';

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'id_factura', 'id_factura');
    }
}
