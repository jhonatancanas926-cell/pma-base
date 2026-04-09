<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(): JsonResponse
    {
        $tests = Test::where('activo', true)
            ->withCount(['categorias', 'preguntas'])
            ->orderBy('nombre')
            ->get();

        return response()->json(['data' => $tests]);
    }

    public function show(Test $test): JsonResponse
    {
        $test->load(['categorias' => function ($q) {
            $q->withCount('preguntas')->orderBy('orden');
        }]);

        return response()->json(['data' => $test]);
    }

    public function preguntas(Test $test, Request $request): JsonResponse
    {
        $request->validate([
            'categoria' => 'nullable|string|exists:categorias,codigo',
            'por_pagina'=> 'nullable|integer|min:5|max:100',
        ]);

        $query = $test->preguntas()
            ->with('opciones')
            ->where('activo', true)
            ->orderBy('categorias.orden')
            ->orderBy('preguntas.orden');

        if ($request->filled('categoria')) {
            $query->whereHas('categoria', fn($q) => $q->where('codigo', $request->categoria));
        }

        $preguntas = $query->paginate($request->integer('por_pagina', 20));

        // Ocultar respuesta correcta al evaluado
        $preguntas->getCollection()->transform(function ($pregunta) {
            return [
                'id'       => $pregunta->id,
                'numero'   => $pregunta->numero,
                'factor'   => $pregunta->categoria->nombre ?? null,
                'enunciado'=> $pregunta->enunciado,
                'tipo'     => $pregunta->tipo,
                'opciones' => $pregunta->opciones->map(fn($o) => [
                    'id'    => $o->id,
                    'letra' => $o->letra,
                    'texto' => $o->texto,
                ]),
            ];
        });

        return response()->json(['data' => $preguntas]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre'       => 'required|string|max:255',
            'codigo'       => 'required|string|max:20|unique:tests',
            'descripcion'  => 'nullable|string',
            'version'      => 'nullable|string|max:10',
            'tiempo_limite'=> 'nullable|integer|min:1',
        ]);

        $test = Test::create($datos);
        return response()->json(['message' => 'Prueba creada.', 'data' => $test], 201);
    }
}
