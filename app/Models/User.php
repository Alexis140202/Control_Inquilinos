<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['correo', 'contrasena', 'rol', 'nombres', 'apellidos', 'telefono', 'dni', 'estado', 'correo_verificado_en', 'token_recordar'])]
#[Hidden(['contrasena', 'token_recordar'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'USUARIO';

    protected $primaryKey = 'id_usuario';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'id_usuario', 'id_usuario');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordar';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
            'estado' => 'boolean',
            'correo_verificado_en' => 'datetime',
        ];
    }
}
