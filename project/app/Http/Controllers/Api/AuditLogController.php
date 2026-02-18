<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuditLogController extends Controller
{
    #[OA\Get(
        path: '/audit-logs',
        summary: 'List audit logs',
        description: 'Get paginated audit logs with filters (Admin only)',
        security: [['sanctum' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'action_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['create', 'update', 'delete'])),
            new OA\Parameter(name: 'entity_type', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Audit logs retrieved'),
            new OA\Response(response: 403, description: 'Forbidden (Admin only)')
        ]
    )]
    public function index(Request $request)
    {
        $logs = AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }
}
