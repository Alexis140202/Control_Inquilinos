<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['numero', 'descripcion', 'precio', 'estado'])]
class Room extends Model
{
    protected $table = 'HABITACIONES';

    protected $primaryKey = 'id_habitacion';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'id_habitacion', 'id_habitacion');
    }
}
