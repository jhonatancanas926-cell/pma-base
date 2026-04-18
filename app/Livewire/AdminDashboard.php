<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SesionPrueba;
use App\Models\User;
use App\Models\Resultado;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Component
{
    public $totalSesiones;
    public $completadas;
    public $totalUsuarios;
    public $promediosPorFactor;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->totalSesiones = SesionPrueba::whereHas('user', fn($q) => $q->where('role', 'evaluado'))->count();
        $this->completadas   = SesionPrueba::where('estado', 'completada')->whereHas('user', fn($q) => $q->where('role', 'evaluado'))->count();
        $this->totalUsuarios = User::where('role', 'evaluado')->count();
        
        $this->promediosPorFactor = Resultado::with('categoria:id,nombre,codigo')
            ->whereHas('sesion.user', fn($q) => $q->where('role', 'evaluado'))
            ->select('categoria_id',
                DB::raw('AVG(correctas) as promedio_correctas'),
                DB::raw('AVG(puntaje_bruto) as promedio_puntaje'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('categoria_id')
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin-dashboard');
    }
}
