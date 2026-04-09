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
                        gold: {
                            400: '#e8c96a',
                            500: '#D6B35B',
                            600: '#b8943a',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#0f0f0d] text-white min-h-screen">

    <!-- Nav -->
    <nav class="fixed top-0 w-full z-50 bg-[#0f0f0d]/80 backdrop-blur-md border-b border-white/10">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="text-gold-500 text-2xl font-bold tracking-tight">VAIA</span>
            <a href="/admin" class="text-sm text-gray-400 hover:text-white transition-colors">
                Panel Admin →
            </a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="pt-32 pb-24 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 bg-yellow-500/10 border border-yellow-500/30 rounded-full px-4 py-1.5 text-yellow-400 text-sm font-medium mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse inline-block"></span>
                API activa en Railway
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-6 leading-tight">
                Tu compañero de<br>
                <span class="text-yellow-400">viaje inteligente</span>
            </h1>

            <p class="text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                VAIA te ayuda a planificar viajes, gestionar gastos y organizar cada detalle
                con inteligencia artificial — todo desde tu celular.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/admin"
                   class="bg-yellow-500 hover:bg-yellow-600 text-black font-semibold px-8 py-3.5 rounded-lg transition-colors">
                    Acceder al panel
                </a>
                <a href="https://github.com/yachedev/vaia-android"
                   class="bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium px-8 py-3.5 rounded-lg transition-colors">
                    App Android
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-20 px-6 border-t border-white/5">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-center text-2xl font-semibold text-gray-300 mb-12">Todo lo que necesitás para viajar</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                @php
                    $features = [
                        ['icon' => '✈️', 'title' => 'Planificación de viajes', 'desc' => 'Creá y organizá tus viajes con fechas, destinos y presupuesto en un solo lugar.'],
                        ['icon' => '💸', 'title' => 'Control de gastos', 'desc' => 'Registrá y categorizá cada gasto al instante. Nunca pierdas el hilo de tu presupuesto.'],
                        ['icon' => '🗓️', 'title' => 'Actividades', 'desc' => 'Planificá día a día tus actividades con horarios, ubicaciones y notas.'],
                        ['icon' => '🧳', 'title' => 'Lista de equipaje IA', 'desc' => 'La IA genera automáticamente qué llevarte según el destino, clima y duración del viaje.'],
                        ['icon' => '📄', 'title' => 'Documentos', 'desc' => 'Guardá pasaportes, reservas y vouchers digitales accesibles en todo momento.'],
                        ['icon' => '🔔', 'title' => 'Notificaciones', 'desc' => 'Recordatorios y alertas para que no te pierdas ningún detalle de tu itinerario.'],
                    ];
                @endphp

                @foreach($features as $feature)
                <div class="bg-white/[0.03] border border-white/[0.08] rounded-xl p-6 hover:bg-white/[0.05] transition-colors">
                    <div class="text-3xl mb-4">{{ $feature['icon'] }}</div>
                    <h3 class="font-semibold text-white mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- API Endpoints -->
    <section class="py-16 px-6 border-t border-white/5">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-center text-xl font-semibold text-gray-300 mb-8">Estado de la API</h2>
            <div class="bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden">
                @php
                    $endpoints = [
                        ['method' => 'POST', 'path' => '/api/register', 'desc' => 'Registro de usuario'],
                        ['method' => 'POST', 'path' => '/api/login', 'desc' => 'Inicio de sesión'],
                        ['method' => 'GET',  'path' => '/api/trips', 'desc' => 'Listar viajes'],
                        ['method' => 'POST', 'path' => '/api/trips', 'desc' => 'Crear viaje'],
                        ['method' => 'GET',  'path' => '/api/trips/{id}/expenses', 'desc' => 'Gastos del viaje'],
                        ['method' => 'GET',  'path' => '/api/trips/{id}/activities', 'desc' => 'Actividades del viaje'],
                    ];
                    $methodColors = ['POST' => 'text-emerald-400', 'GET' => 'text-blue-400', 'DELETE' => 'text-red-400', 'PUT' => 'text-yellow-400'];
                @endphp
                @foreach($endpoints as $i => $ep)
                <div class="flex items-center gap-4 px-5 py-3.5 {{ $i < count($endpoints) - 1 ? 'border-b border-white/5' : '' }}">
                    <span class="text-xs font-bold font-mono w-12 {{ $methodColors[$ep['method']] ?? 'text-gray-400' }}">
                        {{ $ep['method'] }}
                    </span>
                    <span class="text-sm font-mono text-gray-200 flex-1">{{ $ep['path'] }}</span>
                    <span class="text-xs text-gray-500 hidden sm:block">{{ $ep['desc'] }}</span>
                </div>
                @endforeach
            </div>
            <p class="text-center text-xs text-gray-600 mt-4">
                Autenticación vía Laravel Sanctum — Bearer token requerido en endpoints protegidos
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/5 py-10 px-6">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-yellow-500 font-bold text-lg">VAIA</span>
            <p class="text-xs text-gray-600">
                Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }} · {{ now()->year }}
            </p>
            <a href="/admin" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">
                Admin Panel →
            </a>
        </div>
    </footer>

</body>
</html>
