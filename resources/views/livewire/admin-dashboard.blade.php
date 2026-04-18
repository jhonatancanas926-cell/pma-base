<div>
    <!-- Stats Cards -->
    <div class="grid-3 mb-3">
        <div class="card card-sm" style="border-left:4px solid #2e75b6;text-align:center">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Total Evaluados</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $totalUsuarios }}</div>
        </div>
        <div class="card card-sm" style="border-left:4px solid #107c10;text-align:center">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Sesiones Iniciadas</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $totalSesiones }}</div>
        </div>
        <div class="card card-sm" style="border-left:4px solid #e8a020;text-align:center">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Completadas</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $completadas }}</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2">
        <div class="card" wire:ignore>
            <h2 class="serif mb-2" style="font-size:1.3rem;color:#0f1f3d">Promedio Rendimiento por Factor</h2>
            <div style="position: relative; height:300px; width:100%">
                <canvas id="factoresChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h2 class="serif mb-2" style="font-size:1.3rem;color:#0f1f3d">Resumen de Factores</h2>
            <div style="max-height: 300px; overflow-y: auto;">
                @foreach($promediosPorFactor as $pf)
                <div style="padding:0.7rem;border-bottom:1px solid #eef1f5;display:flex;align-items:center;gap:1rem">
                    <div style="width:36px;height:36px;background:#1a3a6b;border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:0.9rem;flex-shrink:0">
                        {{ substr($pf['categoria']['codigo'] ?? 'F', -1) }}
                    </div>
                    <div style="flex:1">
                        <div style="font-weight:600;color:#0f1f3d;font-size:0.9rem;margin-bottom:2px">{{ $pf['categoria']['nombre'] ?? 'Factor' }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#0f1f3d;font-size:0.95rem">{{ round($pf['promedio_puntaje'], 1) }} pt</div>
                    </div>
                </div>
                @endforeach
                @if(empty($promediosPorFactor))
                <div class="text-center text-muted" style="padding:1rem">No hay datos suficientes aún.</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const data = @json($promediosPorFactor);
            const labels = data.map(item => item.categoria.codigo + ' - ' + item.categoria.nombre);
            const scores = data.map(item => item.promedio_puntaje);

            const ctx = document.getElementById('factoresChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Puntaje Promedio',
                            data: scores,
                            backgroundColor: '#2e75b6',
                            borderRadius: 6,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: '#eef1f5' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</div>
