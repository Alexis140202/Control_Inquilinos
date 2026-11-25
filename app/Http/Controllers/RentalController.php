<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\FacturaAgua;
use App\Models\FacturaLuz;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RentalController extends Controller
{
    public function dashboard(): View
    {
        if (request()->user()->rol === 'inquilino') {
            return $this->tenantHome();
        }

        $facturas = Invoice::with('contract.tenant', 'contract.room', 'facturaAgua', 'facturaLuz')
            ->where('estado_pago', 'pendiente')
            ->orderBy('fecha_vencimiento')
            ->take(6)
            ->get();

        return view('rental.dashboard', [
            'stats' => [
                'income' => Payment::where('estado', 'aprobado')->whereMonth('fecha_pago', now()->month)->whereYear('fecha_pago', now()->year)->sum('monto'),
                'debt' => Invoice::where('estado_pago', 'pendiente')->sum('monto_total'),
                'rooms' => Room::count(),
                'occupied' => Room::where('estado', 'ocupada')->count(),
                'available' => Room::where('estado', 'disponible')->count(),
                'tenants' => User::where('rol', 'inquilino')->where('estado', true)->count(),
                'reviews' => Payment::where('estado', 'pendiente')->count(),
            ],
            'invoices' => $facturas,
            'recent' => Payment::with('invoice.contract.tenant')->latest()->take(5)->get(),
        ]);
    }

    private function tenantHome(): View
    {
        $contratoIds = request()->user()->contracts()->pluck('id_contrato');
        $facturas = Invoice::with('contract.room', 'payments', 'facturaAgua', 'facturaLuz')
            ->whereIn('id_contrato', $contratoIds)
            ->latest()
            ->get();

        return view('rental.tenant', [
            'invoices' => $facturas,
            'debt' => $facturas->where('estado_pago', 'pendiente')->sum(fn (Invoice $factura): float => $factura->total),
            'payments' => Payment::with('invoice.facturaAgua', 'invoice.facturaLuz', 'reviewer')
                ->whereHas('invoice', fn ($consulta) => $consulta->whereIn('id_contrato', $contratoIds))
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }

    public function rooms(): View
    {
        return view('rental.rooms', [
            'rooms' => Room::withCount('contracts')->latest()->get(),
            'edit' => request('edit') ? Room::findOrFail(request('edit')) : null,
        ]);
    }

    public function saveRoom(Request $request, ?Room $room = null): RedirectResponse
    {
        $datos = $request->validate([
            'number' => ['required', 'max:10', Rule::unique('HABITACIONES', 'numero')->ignore($room?->id_habitacion, 'id_habitacion')],
            'description' => 'nullable|max:255',
            'rent' => 'required|numeric|min:0',
            'status' => 'required|in:disponible,ocupada,mantenimiento',
        ]);
        abort_if($room && $datos['status'] === 'disponible' && $room->contracts()->where('estado', 'activo')->exists(), 422, 'La habitación tiene un contrato activo y no se puede marcar como disponible.');

        $atributos = [
            'numero' => $datos['number'],
            'descripcion' => $datos['description'] ?? $room?->descripcion,
            'precio' => $datos['rent'],
            'estado' => $datos['status'],
        ];
        $room?->update($atributos) ?? Room::create($atributos);

        return back()->with('success', $room ? 'Habitación actualizada.' : 'Habitación registrada.');
    }

    public function deleteRoom(Room $room): RedirectResponse
    {
        abort_if($room->contracts()->exists(), 422, 'No se puede eliminar: la habitación tiene contratos asociados.');
        $room->delete();

        return back()->with('success', 'Habitación eliminada.');
    }

    public function tenants(): View
    {
        return view('rental.tenants', [
            'tenants' => User::where('rol', 'inquilino')->withCount('contracts')->latest()->get(),
            'edit' => request('edit') ? User::where('rol', 'inquilino')->findOrFail(request('edit')) : null,
        ]);
    }

    public function saveTenant(Request $request, ?User $tenant = null): RedirectResponse
    {
        $datos = $request->validate([
            'name' => 'required|max:200',
            'dni' => ['required', 'digits:8', Rule::unique('USUARIO', 'dni')->ignore($tenant?->id_usuario, 'id_usuario')],
            'phone' => 'nullable|max:30',
            'email' => ['required', 'email', 'max:190', Rule::unique('USUARIO', 'correo')->ignore($tenant?->id_usuario, 'id_usuario')],
            'password' => [$tenant ? 'nullable' : 'required', 'min:8'],
        ]);
        $nombres = preg_split('/\s+/', trim($datos['name']), 2) ?: [];
        $atributos = [
            'nombres' => $nombres[0] ?? '',
            'apellidos' => $nombres[1] ?? '',
            'dni' => $datos['dni'],
            'telefono' => $datos['phone'] ?? null,
            'correo' => $datos['email'],
            'rol' => 'inquilino',
            'estado' => true,
        ];
        if (! empty($datos['password'])) {
            $atributos['contrasena'] = $datos['password'];
        }
        $tenant?->update($atributos) ?? User::create($atributos);

        return redirect()->route('tenants')->with('success', $tenant ? 'Inquilino actualizado.' : 'Inquilino registrado.');
    }

    public function deleteTenant(User $tenant): RedirectResponse
    {
        abort_unless($tenant->rol === 'inquilino', 404);
        abort_if($tenant->contracts()->whereHas('invoices', fn ($consulta) => $consulta->where('estado_pago', 'pendiente'))->exists(), 422, 'No se puede desactivar: el inquilino tiene facturas pendientes.');
        $tenant->update(['estado' => false]);

        return back()->with('success', 'Acceso del inquilino desactivado.');
    }

    public function contracts(): View
    {
        $contratoEdicion = request('edit') ? Contract::findOrFail(request('edit')) : null;

        return view('rental.contracts', [
            'contracts' => Contract::with('tenant', 'room')->latest()->get(),
            'tenants' => User::where('rol', 'inquilino')
                ->where(fn ($consulta) => $consulta->where('estado', true)->when($contratoEdicion, fn ($consulta) => $consulta->orWhereKey($contratoEdicion->id_usuario)))
                ->orderBy('nombres')
                ->get(),
            'rooms' => Room::where('estado', 'disponible')->orderBy('numero')->get(),
            'edit' => $contratoEdicion,
        ]);
    }

    public function saveContract(Request $request, ?Contract $contract = null): RedirectResponse
    {
        $datos = $request->validate([
            'user_id' => 'required|exists:USUARIO,id_usuario',
            'room_id' => 'required|exists:HABITACIONES,id_habitacion',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'rent' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'due_day' => 'nullable|integer|between:1,28',
            'status' => 'required|in:activo,finalizado',
        ]);
        abort_unless(User::whereKey($datos['user_id'])->where('rol', 'inquilino')->exists(), 422);
        $habitacionAnterior = $contract?->room;
        $habitacion = Room::findOrFail($datos['room_id']);
        abort_if($datos['status'] === 'activo' && $habitacion->estado === 'ocupada' && $contract?->id_habitacion !== $habitacion->id_habitacion, 422, 'La habitación ya está ocupada.');

        $atributos = [
            'id_usuario' => $datos['user_id'],
            'id_habitacion' => $datos['room_id'],
            'fecha_inicio' => $datos['starts_at'],
            'fecha_fin' => $datos['ends_at'] ?? null,
            'precio_renta' => $datos['rent'],
            'garantia' => $datos['deposit'] ?? $contract?->garantia ?? 0,
            'dia_vencimiento' => $datos['due_day'] ?? $contract?->dia_vencimiento ?? 1,
            'estado' => $datos['status'],
        ];
        $contract?->update($atributos) ?? Contract::create($atributos);
        if ($habitacionAnterior && $habitacionAnterior->id_habitacion !== $habitacion->id_habitacion) {
            $this->syncRoom($habitacionAnterior->fresh());
        }
        $this->syncRoom($habitacion->fresh());

        return redirect()->route('contracts')->with('success', 'Contrato guardado.');
    }

    public function deleteContract(Contract $contract): RedirectResponse
    {
        abort_if($contract->invoices()->where('estado_pago', 'pendiente')->exists(), 422, 'No se puede eliminar un contrato con facturas pendientes.');
        $habitacion = $contract->room;
        $contract->delete();
        $this->syncRoom($habitacion->fresh());

        return back()->with('success', 'Contrato eliminado.');
    }

    private function syncRoom(Room $room): void
    {
        $ocupada = $room->contracts()->where('estado', 'activo')->exists();
        if ($room->estado !== 'mantenimiento') {
            $room->update(['estado' => $ocupada ? 'ocupada' : 'disponible']);
        }
    }

    public function invoices(): View
    {
        return view('rental.invoices', [
            'invoices' => Invoice::with('contract.tenant', 'contract.room', 'facturaAgua', 'facturaLuz')->latest()->paginate(12),
            'contracts' => Contract::with('tenant', 'room')->where('estado', 'activo')->get(),
            'edit' => request('edit') ? Invoice::with('facturaAgua', 'facturaLuz')->findOrFail(request('edit')) : null,
        ]);
    }

    public function saveInvoice(Request $request, ?Invoice $invoice = null): RedirectResponse
    {
        $datos = $request->validate([
            'contract_id' => 'required|exists:CONTRATOS,id_contrato',
            'type' => 'required|in:alquiler,electricidad,agua',
            'period' => 'required|date_format:Y-m',
            'due_at' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'previous_reading' => 'nullable|numeric|min:0',
            'current_reading' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
        ]);
        $contrato = Contract::findOrFail($datos['contract_id']);
        if ($invoice) {
            abort_unless($invoice->estado_pago === 'pendiente', 422, 'Solo se editan facturas pendientes.');
        }

        if ($datos['type'] === 'alquiler') {
            $monto = $datos['amount'] ?? $contrato->precio_renta;
        } else {
            $lecturaAnterior = (float) ($datos['previous_reading'] ?? 0);
            $lecturaActual = (float) ($datos['current_reading'] ?? 0);
            abort_if($lecturaActual < $lecturaAnterior, 422, 'La lectura actual no puede ser menor a la anterior.');
            $monto = round(($lecturaActual - $lecturaAnterior) * (float) ($datos['rate'] ?? 0), 2);
        }

        $fechaEmision = Carbon::createFromFormat('Y-m', $datos['period'])->startOfMonth()->toDateString();
        $duplicada = Invoice::where('id_contrato', $contrato->id_contrato)
            ->whereDate('fecha_emision', $fechaEmision)
            ->when($invoice, fn ($consulta) => $consulta->where('id_factura', '<>', $invoice->id_factura))
            ->with('facturaAgua', 'facturaLuz')
            ->get()
            ->contains(fn (Invoice $existente): bool => $existente->type === $datos['type']);
        abort_if($duplicada, 422, 'Ya existe una factura de ese tipo para el periodo seleccionado.');

        $atributos = [
            'id_contrato' => $contrato->id_contrato,
            'monto_total' => $monto,
            'fecha_emision' => $fechaEmision,
            'fecha_vencimiento' => $datos['due_at'],
            'estado_pago' => 'pendiente',
        ];
        $factura = DB::transaction(function () use ($invoice, $atributos, $datos): Invoice {
            if ($invoice) {
                $invoice->update($atributos);
                $factura = $invoice->fresh();
            } else {
                $factura = Invoice::create($atributos);
            }

            FacturaAgua::where('id_factura', $factura->id_factura)->delete();
            FacturaLuz::where('id_factura', $factura->id_factura)->delete();

            if ($datos['type'] === 'agua') {
                FacturaAgua::create([
                    'id_factura' => $factura->id_factura,
                    'lectura_anterior' => $datos['previous_reading'] ?? 0,
                    'lectura_actual' => $datos['current_reading'] ?? 0,
                    'tarifa_m3' => $datos['rate'] ?? 0,
                ]);
            } elseif ($datos['type'] === 'electricidad') {
                FacturaLuz::create([
                    'id_factura' => $factura->id_factura,
                    'lectura_anterior' => $datos['previous_reading'] ?? 0,
                    'lectura_actual' => $datos['current_reading'] ?? 0,
                    'tarifa_kwh' => $datos['rate'] ?? 0,
                ]);
            }

            return $factura;
        });

        return redirect()->route('invoices')->with('success', 'Factura generada por S/ '.number_format($factura->monto_total, 2).'.');
    }

    public function voidInvoice(Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->payments()->where('estado', 'aprobado')->exists(), 422, 'No se puede anular una factura con un pago aprobado.');
        abort_if($invoice->estado_pago === 'pagado', 422, 'La factura ya está pagada.');
        $invoice->update(['estado_pago' => 'anulado']);

        return back()->with('success', 'Factura anulada.');
    }

    public function payments(): View
    {
        return view('rental.payments', [
            'payments' => Payment::with('invoice.contract.tenant', 'invoice.contract.room', 'invoice.facturaAgua', 'invoice.facturaLuz', 'reviewer')->latest()->paginate(15),
        ]);
    }

    public function uploadPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->contract->id_usuario === $request->user()->id_usuario, 403);
        abort_unless($invoice->estado_pago === 'pendiente', 422, 'Esta factura no está pendiente.');
        $datos = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'voucher' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $ruta = $datos['voucher']->store('vouchers', 'private');
        Payment::create([
            'id_factura' => $invoice->id_factura,
            'monto' => $datos['amount'],
            'fecha_pago' => $datos['paid_at'],
            'ruta_voucher' => $ruta,
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Comprobante enviado para revisión.');
    }

    public function reviewPayment(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->estado === 'pendiente', 422, 'El comprobante ya fue revisado.');
        $datos = $request->validate([
            'decision' => 'required|in:approve,reject',
            'reason' => 'required_if:decision,reject|nullable|string|max:1000',
        ]);
        $idRevisor = $request->user()->id_usuario;
        DB::transaction(function () use ($payment, $datos, $idRevisor): void {
            $pagoBloqueado = Payment::whereKey($payment->id_pago)->lockForUpdate()->firstOrFail();
            abort_unless($pagoBloqueado->estado === 'pendiente', 422, 'El comprobante ya fue revisado.');
            $factura = $pagoBloqueado->invoice()->lockForUpdate()->firstOrFail();
            if ($datos['decision'] === 'approve') {
                if ($factura->estado_pago !== 'pendiente') {
                    throw ValidationException::withMessages(['decision' => 'La factura ya no está pendiente.']);
                }
                $fechaVencimiento = Carbon::parse($factura->fecha_vencimiento)->startOfDay();
                $fechaPago = Carbon::parse($pagoBloqueado->fecha_pago)->startOfDay();
                $diasVencidos = max(0, $fechaVencimiento->diffInDays($fechaPago, false));
                $montoEsperado = (float) $factura->monto_total + (float) $factura->recargo_mora + min(config('rental.late_fee_max_days'), $diasVencidos) * config('rental.late_fee_per_day');
                if ((float) $pagoBloqueado->monto + 0.001 < $montoEsperado) {
                    throw ValidationException::withMessages(['decision' => 'El monto no cubre la factura y la mora correspondiente a la fecha de pago.']);
                }
            }

            $aprobado = $datos['decision'] === 'approve';
            $pagoBloqueado->update([
                'estado' => $aprobado ? 'aprobado' : 'rechazado',
                'motivo_rechazo' => $aprobado ? null : $datos['reason'],
                'id_revisor' => $idRevisor,
            ]);
            if ($aprobado) {
                $factura->update(['estado_pago' => 'pagado', 'fecha_pago' => $pagoBloqueado->fecha_pago]);
            }
        });

        return back()->with('success', $datos['decision'] === 'approve' ? 'Pago aprobado y factura saldada.' : 'Comprobante rechazado.');
    }

    public function voucher(Payment $payment): Response
    {
        $inquilino = request()->user()->rol === 'inquilino';
        abort_if($inquilino && $payment->invoice->contract->id_usuario !== request()->user()->id_usuario, 403);
        abort_unless(Storage::disk('private')->exists($payment->ruta_voucher), 404);

        return Storage::disk('private')->response($payment->ruta_voucher)->header('X-Content-Type-Options', 'nosniff');
    }

    public function receipt(Payment $payment): View
    {
        abort_unless($payment->estado === 'aprobado', 404);
        abort_if(request()->user()->rol === 'inquilino' && $payment->invoice->contract->id_usuario !== request()->user()->id_usuario, 403);

        return view('rental.receipt', ['payment' => $payment->load('invoice.contract.tenant', 'invoice.contract.room', 'invoice.facturaAgua', 'invoice.facturaLuz')]);
    }

    public function reports(Request $request): View
    {
        $month = $request->input('month', now()->format('Y-m'));
        $from = $month.'-01';
        $to = date('Y-m-t', strtotime($from));
        $income = Payment::with('invoice.contract.tenant', 'invoice.facturaAgua', 'invoice.facturaLuz')
            ->where('estado', 'aprobado')
            ->whereBetween('fecha_pago', [$from, $to])
            ->get();
        $overdue = Invoice::with('contract.tenant', 'contract.room', 'facturaAgua', 'facturaLuz')
            ->where('estado_pago', 'pendiente')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->orderBy('fecha_vencimiento')
            ->get();
        $series = collect(range(5, 0))->map(function (int $mesesAtras): array {
            $fecha = now()->startOfMonth()->subMonths($mesesAtras);

            return [
                'label' => $fecha->translatedFormat('M'),
                'total' => Payment::where('estado', 'aprobado')->whereMonth('fecha_pago', $fecha->month)->whereYear('fecha_pago', $fecha->year)->sum('monto'),
            ];
        });

        return view('rental.reports', compact('month', 'income', 'overdue', 'series'));
    }
}
