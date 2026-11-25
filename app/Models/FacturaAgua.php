<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lectura_anterior', 'lectura_actual', 'tarifa_m3', 'id_factura'])]
class FacturaAgua extends Model
{
    public $timestamps = false;

    protected $table = 'FACTURA_AGUA';

    protected $primaryKey = 'id_facturaagua';

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'id_factura', 'id_factura');
    }
}
