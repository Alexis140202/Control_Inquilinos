<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_usuario', 'id_habitacion', 'fecha_inicio', 'fecha_fin', 'precio_renta', 'garantia', 'dia_vencimiento', 'estado'])]
class Contract extends Model
{
    protected $table = 'CONTRATOS';

    protected $primaryKey = 'id_contrato';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'id_habitacion', 'id_habitacion');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'id_contrato', 'id_contrato');
    }
}
