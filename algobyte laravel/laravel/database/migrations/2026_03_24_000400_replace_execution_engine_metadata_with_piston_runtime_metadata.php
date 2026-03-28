<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('code_submissions', 'memory_kb') ? 'memory_kb' : null,
            Schema::hasColumn('code_submissions', 'judge0_token') ? 'judge0_token' : null,
            Schema::hasColumn('code_submissions', 'judge0_language_id') ? 'judge0_language_id' : null,
            Schema::hasColumn('code_submissions', 'judge0_status_id') ? 'judge0_status_id' : null,
            Schema::hasColumn('code_submissions', 'judge0_status_description') ? 'judge0_status_description' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('code_submissions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::table('code_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('code_submissions', 'runtime')) {
                $table->string('runtime')->nullable()->after('execution_time_ms');
            }

            if (! Schema::hasColumn('code_submissions', 'runtime_version')) {
                $table->string('runtime_version')->nullable()->after('runtime');
            }

            if (! Schema::hasColumn('code_submissions', 'signal')) {
                $table->string('signal')->nullable()->after('runtime_version');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('code_submissions', 'runtime') ? 'runtime' : null,
            Schema::hasColumn('code_submissions', 'runtime_version') ? 'runtime_version' : null,
            Schema::hasColumn('code_submissions', 'signal') ? 'signal' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('code_submissions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
