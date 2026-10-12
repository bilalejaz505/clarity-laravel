<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\SnapshotRequest;
use App\Models\Workspace;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function get(Request $request): JsonResponse
    {
        return response()->json(['store' => Workspace::where('user_id', $request->user()->id)->first()?->data]);
    }

    public function save(SnapshotRequest $request, EntitlementService $access): JsonResponse
    {
        $access->requireProduct($request, $request->input('product', 'chat'));
        abort_if(strlen($request->getContent()) > 10485760, 413, 'Snapshots are limited to 10 MB.');
        Workspace::updateOrCreate(['user_id' => $request->user()->id], ['data' => $request->validated('store')]);

        return response()->json(['ok' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        Workspace::where('user_id', $request->user()->id)->delete();

        return response()->json(['ok' => true]);
    }
}
