<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'language',
    'code',
    'status',
    'stdout',
    'stderr',
    'compile_output',
    'exit_code',
    'execution_time_ms',
    'runtime',
    'runtime_version',
    'signal',
])]
class CodeSubmission extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exit_code' => 'integer',
            'execution_time_ms' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
