<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['correo' => 'admin@alquiler.test'],
            [
                'nombres' => 'Administrador',
                'apellidos' => 'Demo',
                'dni' => '70000001',
                'telefono' => '999 111 111',
                'rol' => 'administrador',
                'estado' => true,
                'contrasena' => 'Admin123!',
            ],
        );

        $inquilinos = [];
        foreach ([
            ['Ana', 'Torres', '70000002', 'ana@alquiler.test', '988 200 301'],
            ['Luis', 'Ramírez', '70000003', 'luis@alquiler.test', '988 200 302'],
            ['María', 'Flores', '70000004', 'maria@alquiler.test', '988 200 303'],
        ] as [$nombres, $apellidos, $dni, $correo, $telefono]) {
            $inquilinos[] = User::updateOrCreate(
                ['correo' => $correo],
                [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'dni' => $dni,
                    'telefono' => $telefono,
                    'rol' => 'inquilino',
                    'estado' => true,
                    'contrasena' => 'Inquilino123!',
                ],
            );
        }

        $habitaciones = [];
        foreach ([
            ['A-101', 'Habitación con baño privado', 850, 'ocupada'],
            ['A-102', 'Habitación amoblada', 720, 'ocupada'],
            ['B-201', 'Suite con kitchenette', 1100, 'ocupada'],
            ['B-202', 'Habitación iluminada', 680, 'disponible'],
            ['C-301', 'Estudio independiente', 1300, 'mantenimiento'],
        ] as [$numero, $descripcion, $precio, $estado]) {
            $habitaciones[] = Room::updateOrCreate(
                ['numero' => $numero],
                ['descripcion' => $descripcion, 'precio' => $precio, 'estado' => $estado],
            );
        }

        foreach (array_slice($inquilinos, 0, 3) as $indice => $inquilino) {
            $contrato = Contract::updateOrCreate(
                [
                    'id_usuario' => $inquilino->id_usuario,
                    'id_habitacion' => $habitaciones[$indice]->id_habitacion,
                    'estado' => 'activo',
                ],
                [
                    'fecha_inicio' => now()->subMonths(5),
                    'fecha_fin' => now()->addMonths(7),
                    'precio_renta' => $habitaciones[$indice]->precio,
                    'garantia' => $habitaciones[$indice]->precio,
                    'dia_vencimiento' => 5,
                ],
            );

            foreach ([now()->subMonth(), now()] as $fecha) {
                $vencimiento = $fecha->copy()->day(5);
                Invoice::firstOrCreate(
                    [
                        'id_contrato' => $contrato->id_contrato,
                        'fecha_vencimiento' => $vencimiento->toDateString(),
                    ],
                    [
                        'monto_total' => $contrato->precio_renta,
                        'fecha_emision' => $fecha->copy()->startOfMonth()->toDateString(),
                        'estado_pago' => 'pendiente',
                        'fecha_pago' => null,
                    ],
                );
            }
        }
    }
}
