<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; }

    .header { background: #2ec4b6; padding: 24px 32px; color: #fff; }
    .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 4px; }
    .header .subtitle { font-size: 13px; opacity: 0.85; }
    .header .meta { margin-top: 12px; font-size: 11px; opacity: 0.8; }

    .section { padding: 20px 32px; }
    .trip-summary { background: #f7fffe; border-left: 4px solid #2ec4b6; padding: 14px 18px; margin-bottom: 24px; border-radius: 0 6px 6px 0; }
    .trip-summary table { width: 100%; border-collapse: collapse; }
    .trip-summary td { padding: 4px 16px 4px 0; font-size: 12px; vertical-align: top; }
    .trip-summary td:first-child { font-weight: bold; color: #555; width: 130px; }

    .day-block { margin-bottom: 20px; }
    .day-header { background: #e8faf8; border-left: 3px solid #2ec4b6; padding: 8px 14px; font-weight: bold; font-size: 13px; color: #1a7a74; border-radius: 0 4px 4px 0; margin-bottom: 8px; }

    .activity { border: 1px solid #e5e5e5; border-radius: 6px; padding: 10px 14px; margin-bottom: 8px; background: #fff; }
    .activity-title { font-weight: bold; font-size: 13px; color: #1a1a2e; margin-bottom: 3px; }
    .activity-meta { font-size: 11px; color: #666; margin-bottom: 4px; }
    .activity-desc { font-size: 11px; color: #444; }
    .badge { display: inline-block; background: #e8faf8; color: #1a7a74; border-radius: 12px; padding: 2px 8px; font-size: 10px; margin-right: 6px; font-weight: bold; }
    .badge-cost { background: #fff5e6; color: #b5620a; }

    .no-activities { color: #aaa; font-style: italic; font-size: 11px; padding: 8px 14px; }

    .footer { border-top: 1px solid #eee; padding: 14px 32px; font-size: 10px; color: #aaa; display: flex; justify-content: space-between; }
    .budget-bar-container { margin-top: 8px; }
    .budget-bar-bg { background: #e5e5e5; border-radius: 6px; height: 7px; width: 100%; }
    .budget-bar-fill { background: #2ec4b6; border-radius: 6px; height: 7px; }
    .budget-bar-fill.warning { background: #f4a261; }
    .budget-bar-fill.danger { background: #e63946; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $trip->title }}</h1>
    <div class="subtitle">{{ $trip->destination }}</div>
    <div class="meta">
        {{ $trip->start_date->format('d M Y') }} — {{ $trip->end_date->format('d M Y') }}
        &nbsp;·&nbsp; {{ $trip->start_date->diffInDays($trip->end_date) + 1 }} días
        &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<div class="section">
    <div class="trip-summary">
        <table>
            <tr>
                <td>Destino</td>
                <td>{{ $trip->destination }}</td>
                <td>Actividades</td>
                <td>{{ $activities->count() }}</td>
            </tr>
            <tr>
                <td>Presupuesto</td>
                <td>${{ number_format($trip->budget, 2) }}</td>
                <td>Total gastos</td>
                <td>${{ number_format($totalExpenses, 2) }}</td>
            </tr>
            @if($trip->budget > 0)
            <tr>
                <td colspan="4">
                    <div class="budget-bar-container">
                        @php
                            $ratio = min($totalExpenses / $trip->budget, 1);
                            $pct = round($ratio * 100);
                            $barClass = $ratio >= 1 ? 'danger' : ($ratio >= 0.7 ? 'warning' : '');
                        @endphp
                        Presupuesto usado: {{ $pct }}%
                        <div class="budget-bar-bg">
                            <div class="budget-bar-fill {{ $barClass }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </td>
            </tr>
            @endif
        </table>
    </div>

    @forelse($grouped as $date => $dayActivities)
        <div class="day-block">
            <div class="day-header">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('l, j \d\e F Y') }}
            </div>
            @foreach($dayActivities as $activity)
                <div class="activity">
                    <div class="activity-title">{{ $activity->title }}</div>
                    <div class="activity-meta">
                        @if($activity->time)
                            <span class="badge">{{ $activity->time }}</span>
                        @endif
                        @if($activity->location)
                            <span class="badge">{{ $activity->location }}</span>
                        @endif
                        @if($activity->cost > 0)
                            <span class="badge badge-cost">${{ number_format($activity->cost, 2) }}</span>
                        @endif
                    </div>
                    @if($activity->description)
                        <div class="activity-desc">{{ $activity->description }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div class="no-activities">No hay actividades registradas en este viaje.</div>
    @endforelse
</div>

<div class="footer">
    <span>VAIA — Tu viaje, organizado</span>
    <span>{{ $trip->title }} · {{ $trip->destination }}</span>
</div>

</body>
</html>
