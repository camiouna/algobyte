<?php

namespace App\Services\Piston;

final readonly class PistonExecutionResult
{
    public function __construct(
        public string $status,
        public string $message,
        public ?string $stdout,
        public ?string $stderr,
        public ?string $compileOutput,
        public ?int $exitCode,
        public ?int $executionTimeMs,
        public ?string $runtime,
        public ?string $runtimeVersion,
        public ?string $signal,
    ) {
    }

    /**
     * Transform the Piston result into persisted submission attributes.
     *
     * @return array<string, int|string|null>
     */
    public function toDatabaseAttributes(): array
    {
        return [
            'status' => $this->status,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
            'compile_output' => $this->compileOutput,
            'exit_code' => $this->exitCode,
            'execution_time_ms' => $this->executionTimeMs,
            'runtime' => $this->runtime,
            'runtime_version' => $this->runtimeVersion,
            'signal' => $this->signal,
        ];
    }
}
