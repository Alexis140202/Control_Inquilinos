<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ?? 'Panel' }} · Casa Clara</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="min-h-screen">
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white px-4 transition-transform duration-200 lg:translate-x-0">
        <a href="{{ route('dashboard') }}" class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-xl text-white">⌂</span>
            <span><span class="block text-sm font-extrabold tracking-tight text-slate-900">casa clara</span><span class="text-[10px] font-semibold uppercase tracking-[.17em] text-slate-400">control de alquileres</span></span>
        </a>
        <div class="mt-7 px-3 text-[10px] font-bold uppercase tracking-[.17em] text-slate-400">{{ auth()->user()->rol==='administrador'?'Administración':'Mi espacio' }}</div>
        <nav class="mt-3 space-y-1">
            <a class="nav-link {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><span>▦</span> Resumen</a>
            @if(auth()->user()->rol==='administrador')
                <div class="px-3 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[.17em] text-slate-400">Gestión inmobiliaria</div>
                <a class="nav-link {{ request()->routeIs('rooms')?'active':'' }}" href="{{ route('rooms') }}"><span>⌂</span> Habitaciones</a>
                <a class="nav-link {{ request()->routeIs('tenants')?'active':'' }}" href="{{ route('tenants') }}"><span>♙</span> Inquilinos</a>
                <a class="nav-link {{ request()->routeIs('contracts')?'active':'' }}" href="{{ route('contracts') }}"><span>▤</span> Contratos</a>
                <div class="px-3 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[.17em] text-slate-400">Finanzas</div>
                <a class="nav-link {{ request()->routeIs('invoices')?'active':'' }}" href="{{ route('invoices') }}"><span>▧</span> Facturas</a>
                <a class="nav-link {{ request()->routeIs('payments')?'active':'' }}" href="{{ route('payments') }}"><span>⇄</span> Comprobantes @php($pending=\App\Models\Payment::where('estado','pendiente')->count()) @if($pending)<span class="ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">{{ $pending }}</span>@endif</a>
                <a class="nav-link {{ request()->routeIs('reports')?'active':'' }}" href="{{ route('reports') }}"><span>▥</span> Reportes</a>
            @else
                <a class="nav-link" href="#estado"><span>▧</span> Mis facturas</a>
                <a class="nav-link" href="#historial"><span>◷</span> Historial</a>
            @endif
        </nav>
        <div class="mt-auto mb-4 rounded-xl bg-slate-50 p-4"><div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ mb_substr(auth()->user()->nombre_completo,0,1) }}</div><div class="min-w-0 flex-1"><div class="truncate text-xs font-bold text-slate-800">{{ auth()->user()->nombre_completo }}</div><div class="mt-0.5 text-[10px] text-slate-500">{{ auth()->user()->rol==='administrador'?'Administrador':'Inquilino' }}</div></div></div><form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cerrar sesión <span class="ml-1">↗</span></button></form></div>
    </aside>
    <div id="overlay" class="fixed inset-0 z-20 hidden bg-slate-900/30 lg:hidden"></div>
    <div class="lg:pl-64"><header class="sticky top-0 z-10 flex h-[76px] items-center justify-between border-b border-slate-200 bg-white/90 px-5 backdrop-blur md:px-8"><div class="flex items-center gap-3"><button id="menuButton" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 lg:hidden">☰</button><div><div class="text-[10px] font-semibold uppercase tracking-[.15em] text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</div><h1 class="mt-0.5 text-sm font-bold text-slate-800">{{ $title ?? 'Panel de control' }}</h1></div></div></header>
    <main class="mx-auto max-w-[1400px] p-5 md:p-8">
        @if(session('success'))<div class="mb-5 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span>✓</span>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><div class="font-semibold">Revisa estos datos</div><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
        <footer class="mt-10 border-t border-slate-200 py-5 text-center text-[11px] text-slate-400">Casa Clara · Administración transparente, hogar tranquilo</footer>
    </main></div>
</div>
<script>const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('overlay');document.getElementById('menuButton')?.addEventListener('click',()=>{sidebar.classList.remove('-translate-x-full');overlay.classList.remove('hidden')});overlay?.addEventListener('click',()=>{sidebar.classList.add('-translate-x-full');overlay.classList.add('hidden')});</script>
</body></html>
