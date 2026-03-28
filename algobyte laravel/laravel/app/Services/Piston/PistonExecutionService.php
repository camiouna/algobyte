<?php

namespace App\Services\Piston;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PistonExecutionService
{
    /**
     * Submit source code to Piston and return the compile/run result.
     */
    public function execute(string $language, string $code): PistonExecutionResult
    {
        $runtime = config("piston.runtimes.$language");

        if (! is_array($runtime)) {
            return $this->result(   
                status: 'runner_unavailable',
                message: 'Piston is not configured for the selected language.',
            );
        }

        $response = $this->sendRequest(fn () => $this->client()->post('/execute', $this->executePayload($runtime, $code)));

        if ($response instanceof PistonExecutionResult) {
            return $response;
        }

        if (! $response->successful()) {
            return $this->result(
                status: 'runner_unavailable',
                message: $this->extractPistonError($response, 'Piston rejected the execution request.'),
            );
        }

        return $this->mapExecution($response->json(), $runtime);
    }

    /**
     * Build the Piston execute payload.
     *
     * @param  array<string, mixed>  $runtime
     * @return array<string, mixed>
     */
    private function executePayload(array $runtime, string $code): array
    {
        return [
            'language' => (string) $runtime['language'],
            'version' => (string) $runtime['version'],
            'files' => [
                [
                    'content' => $code,
                ],
            ],
            'stdin' => '',
            'args' => [],
        ];
    }

    /**
     * Map a Piston execute response into the API response contract.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $runtime
     */
    private function mapExecution(array $payload, array $runtime): PistonExecutionResult
    {
        $compileSignal = $this->normalizeText(data_get($payload, 'compile.signal'));
        $compileCode = $this->toNullableInt(data_get($payload, 'compile.code'));
        $compileStderr = $this->normalizeText(data_get($payload, 'compile.stderr'));

        $runSignal = $this->normalizeText(data_get($payload, 'run.signal'));
        $runCode = $this->toNullableInt(data_get($payload, 'run.code'));
        $runStderr = $this->normalizeText(data_get($payload, 'run.stderr'));
        $runStdout = $this->normalizeText(data_get($payload, 'run.stdout'));
        
        // Ensure version is picked up from response to be accurate
        $resolvedVersion = (string) data_get($payload, 'version', $runtime['version']);
        $resolvedLanguage = (string) data_get($payload, 'language', $runtime['language']);

        // Piston includes a 'compile' section if the language requires compilation.
        if (isset($payload['compile']) && $compileCode !== null && $compileCode !== 0) {
            return $this->result(
                status: 'compile_error',
                message: 'Code compilation failed in Piston.',
                stdout: null,
                stderr: $compileStderr,
                compileOutput: $this->normalizeText(data_get($payload, 'compile.output')),
                exitCode: $compileCode,
                executionTimeMs: null,
                runtime: $resolvedLanguage,
                runtimeVersion: $resolvedVersion,
                signal: $compileSignal,
            );
        }

        if ($runCode === 0 && empty($runSignal)) {
            return $this->result(
                status: 'completed',
                message: 'Code executed successfully through Piston.',
                stdout: $runStdout,
                stderr: $runStderr,
                compileOutput: null,
                exitCode: $runCode,
                executionTimeMs: null, // Piston API doesn't guarantee cpuTime without specific setup
                runtime: $resolvedLanguage,
                runtimeVersion: $resolvedVersion,
                signal: null,
            );
        }

        return $this->result(
            status: 'runtime_error',
            message: 'Code executed with an error in Piston.',
            stdout: $runStdout,
            stderr: $runStderr,
            compileOutput: null,
            exitCode: $runCode,
            executionTimeMs: null,
            runtime: $resolvedLanguage,
            runtimeVersion: $resolvedVersion,
            signal: $runSignal,
        );
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->baseUrl((string) config('piston.base_url'))
            ->timeout((int) config('piston.http_timeout_seconds', 10));
    }

    /**
     * Execute a Piston HTTP request and convert connection failures into API-safe results.
     *
     * @param  callable(): Response  $callback
     */
    private function sendRequest(callable $callback): Response|PistonExecutionResult
    {
        try {
            return $callback();
        } catch (ConnectionException $exception) {
            $message = $exception->getMessage();

            return $this->result(
                status: 'runner_unavailable',
                message: 'Piston is unreachable at '.config('piston.base_url').'. Check your local Piston container status and try again.',
                stderr: $message,
            );
        }
    }

    private function extractPistonError(Response $response, string $fallback): string
    {
        $message = $response->json('message');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $fallback;
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strtolower($value) === 'none') {
            return null;
        }

        return rtrim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function result(
        string $status,
        string $message,
        ?string $stdout = null,
        ?string $stderr = null,
        ?string $compileOutput = null,
        ?int $exitCode = null,
        ?int $executionTimeMs = null,
        ?string $runtime = null,
        ?string $runtimeVersion = null,
        ?string $signal = null,
    ): PistonExecutionResult {
        return new PistonExecutionResult(
            status: $status,
            message: $message,
            stdout: $stdout,
            stderr: $stderr,
            compileOutput: $compileOutput,
            exitCode: $exitCode,
            executionTimeMs: $executionTimeMs,
            runtime: $runtime,
            runtimeVersion: $runtimeVersion,
            signal: $signal,
        );
    }
}
