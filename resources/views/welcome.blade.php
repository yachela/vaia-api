<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VAIA — Tu compañero de viaje inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#F4F7FF',
                            100: '#E3F2FD',
                            200: '#BBDEFB',
                            400: '#42A5F5',
                            500: '#1E88E5',
                            600: '#1565C0',
                            700: '#0D47A1',
                        },
                        ink: {
                            primary: '#171A1D',
                            muted:   '#596066',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0&display=block" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
        .material-symbols-rounded { font-size: 24px; line-height: 1; user-select: none; }
        .icon-sm  { font-size: 18px; }
        .icon-md  { font-size: 24px; }
        .icon-lg  { font-size: 32px; }
    </style>
</head>
<body class="bg-brand-50 text-ink-primary min-h-screen">

    <!-- Nav -->
    <nav class="fixed top-0 w-full z-50 bg-brand-50/90 backdrop-blur-md border-b border-brand-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                    <span class="material-symbols-rounded text-white icon-sm">flight</span>
                </div>
                <span class="text-brand-600 text-xl font-bold tracking-tight">VAIA</span>
            </div>
            <a href="/admin"
               class="text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors bg-brand-100 hover:bg-brand-200 px-4 py-2 rounded-full inline-flex items-center gap-1.5">
                <span class="material-symbols-rounded icon-sm">dashboard</span>
                Panel Admin
            </a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="pt-32 pb-24 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 bg-brand-100 border border-brand-200 rounded-full px-4 py-1.5 text-brand-600 text-sm font-medium mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse inline-block"></span>
                API activa en Railway
            </div>

            <h1 class="text-5xl md:text-6xl font-bold tracking-tight mb-6 leading-tight text-ink-primary">
                Tu compañero de<br>
                <span class="text-brand-600">viaje inteligente</span>
            </h1>

            <p class="text-lg text-ink-muted max-w-2xl mx-auto mb-10 leading-relaxed">
                VAIA te ayuda a planificar viajes, gestionar gastos y organizar cada detalle
                con inteligencia artificial — todo desde tu celular.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/admin"
                   class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-8 py-3.5 rounded-full transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-rounded icon-sm">login</span>
                    Acceder al panel
                </a>
                <a href="https://github.com/yachedev/vaia-android"
                   class="bg-white hover:bg-brand-50 border border-brand-200 text-ink-primary font-medium px-8 py-3.5 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-rounded icon-sm">phone_android</span>
                    App Android
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-20 px-6 border-t border-brand-100">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-center text-2xl font-semibold text-ink-primary mb-2">Todo lo que necesitás para viajar</h2>
            <p class="text-center text-ink-muted mb-12">Planificá, organizá y disfrutá sin preocupaciones.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                @php
                    $features = [
                        ['icon' => 'flight',            'title' => 'Planificación de viajes',  'desc' => 'Creá y organizá tus viajes con fechas, destinos y presupuesto en un solo lugar.'],
                        ['icon' => 'payments',          'title' => 'Control de gastos',         'desc' => 'Registrá y categorizá cada gasto al instante. Nunca pierdas el hilo de tu presupuesto.'],
                        ['icon' => 'calendar_today',    'title' => 'Actividades',               'desc' => 'Planificá día a día tus actividades con horarios, ubicaciones y notas.'],
                        ['icon' => 'luggage',           'title' => 'Lista de equipaje IA',      'desc' => 'La IA genera automáticamente qué llevarte según el destino, clima y duración del viaje.'],
                        ['icon' => 'description',       'title' => 'Documentos',                'desc' => 'Guardá pasaportes, reservas y vouchers digitales accesibles en todo momento.'],
                        ['icon' => 'notifications',     'title' => 'Notificaciones',            'desc' => 'Recordatorios y alertas para que no te pierdas ningún detalle de tu itinerario.'],
                    ];
                @endphp

                @foreach($features as $feature)
                <div class="bg-white border border-brand-100 rounded-2xl p-6 hover:shadow-md hover:border-brand-200 transition-all">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center mb-4">
                        <span class="material-symbols-rounded text-brand-600 icon-md">{{ $feature['icon'] }}</span>
                    </div>
                    <h3 class="font-semibold text-ink-primary mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-ink-muted leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- API Endpoints -->
    <section class="py-16 px-6 border-t border-brand-100">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-center text-xl font-semibold text-ink-primary mb-2">Estado de la API</h2>
            <p class="text-center text-sm text-ink-muted mb-8">Autenticación vía Sanctum — Bearer token requerido en endpoints protegidos.</p>
            <div class="bg-white border border-brand-100 rounded-2xl overflow-hidden shadow-sm">
                @php
                    $endpoints = [
                        ['method' => 'POST', 'path' => '/api/register',              'desc' => 'Registro de usuario'],
                        ['method' => 'POST', 'path' => '/api/login',                 'desc' => 'Inicio de sesión'],
                        ['method' => 'GET',  'path' => '/api/trips',                 'desc' => 'Listar viajes'],
                        ['method' => 'POST', 'path' => '/api/trips',                 'desc' => 'Crear viaje'],
                        ['method' => 'GET',  'path' => '/api/trips/{id}/expenses',   'desc' => 'Gastos del viaje'],
                        ['method' => 'GET',  'path' => '/api/trips/{id}/activities', 'desc' => 'Actividades del viaje'],
                    ];
                    $methodColors = [
                        'POST'   => 'bg-green-50 text-green-700',
                        'GET'    => 'bg-brand-100 text-brand-700',
                        'DELETE' => 'bg-red-50 text-red-700',
                        'PUT'    => 'bg-amber-50 text-amber-700',
                    ];
                @endphp
                @foreach($endpoints as $i => $ep)
                <div class="flex items-center gap-4 px-5 py-3.5 {{ $i < count($endpoints) - 1 ? 'border-b border-brand-50' : '' }}">
                    <span class="text-xs font-bold font-mono px-2 py-0.5 rounded {{ $methodColors[$ep['method']] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $ep['method'] }}
                    </span>
                    <span class="text-sm font-mono text-ink-primary flex-1">{{ $ep['path'] }}</span>
                    <span class="text-xs text-ink-muted hidden sm:block">{{ $ep['desc'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-brand-100 py-10 px-6 bg-white">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center">
                    <span class="material-symbols-rounded text-white" style="font-size:14px">flight</span>
                </div>
                <span class="text-brand-600 font-bold">VAIA</span>
            </div>
            <p class="text-xs text-ink-muted">
                © {{ now()->year }} VAIA · Todos los derechos reservados
            </p>
            <a href="/admin" class="text-xs text-brand-600 hover:text-brand-700 transition-colors font-medium inline-flex items-center gap-1">
                <span class="material-symbols-rounded" style="font-size:14px">dashboard</span>
                Admin Panel
            </a>
        </div>
    </footer>

</body>
</html>
