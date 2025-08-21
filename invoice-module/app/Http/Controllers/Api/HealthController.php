<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"Health"},
     *     summary="Vérification de l'état de l'API",
     *     description="Endpoint de santé pour vérifier que l'API fonctionne correctement",
     *     @OA\Response(
     *         response=200,
     *         description="API opérationnelle",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="OK"),
     *             @OA\Property(property="message", type="string", example="API Module de Facturation opérationnelle"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
     *             @OA\Property(property="version", type="string", example="1.0.0")
     *         )
     *     )
     * )
     * Vérification de l'état de l'API
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'API Module de Facturation opérationnelle',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    }
}