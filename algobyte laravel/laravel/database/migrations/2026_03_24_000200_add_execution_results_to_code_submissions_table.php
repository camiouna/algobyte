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
        Schema::table('code_submissions', function (Blueprint $table) {
            $table->longText('stdout')->nullable()->after('status');
            $table->longText('stderr')->nullable()->after('stdout');
            $table->longText('compile_output')->nullable()->after('stderr');
            $table->integer('exit_code')->nullable()->after('compile_output');
            $table->unsignedInteger('execution_time_ms')->nullable()->after('exit_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('code_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'stdout',
                'stderr',
                'compile_output',
                'exit_code',
                'execution_time_ms',
            ]);
        });
    }
};
