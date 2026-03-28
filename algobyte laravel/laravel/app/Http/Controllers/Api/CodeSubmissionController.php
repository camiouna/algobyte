<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCodeSubmissionRequest;
use App\Models\CodeSubmission;
use App\Services\Piston\PistonExecutionService;
use Illuminate\Http\JsonResponse;

class CodeSubmissionController extends Controller
{
    /**
     * Store, submit to Piston, and return the execution result.
     */
    public function store(StoreCodeSubmissionRequest $request, PistonExecutionService $piston): JsonResponse
    {
        $language = $request->string('language')->toString();
        $code = $request->string('code')->toString();
        $result = $piston->execute($language, $code);

        $submission = CodeSubmission::create([
            'language' => $language,
            'code' => $code,
            ...$result->toDatabaseAttributes(),
        ]);

        return response()->json([
            'message' => $result->message,
            'data' => [
                'id' => $submission->id,
                'language' => $submission->language,
                'code' => $submission->code,
                'status' => $submission->status,
                'stdout' => $submission->stdout,
                'stderr' => $submission->stderr,
                'compile_output' => $submission->compile_output,
                'exit_code' => $submission->exit_code,
                'execution_time_ms' => $submission->execution_time_ms,
                'runtime' => $submission->runtime,
                'runtime_version' => $submission->runtime_version,
                'signal' => $submission->signal,
                'received_at' => $submission->created_at?->toISOString(),
            ],
        ], 201);
    }
}
