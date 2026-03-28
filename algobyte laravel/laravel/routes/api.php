<?php

use App\Http\Controllers\Api\CodeSubmissionController;
use Illuminate\Support\Facades\Route;

Route::post('/code-submissions', [CodeSubmissionController::class, 'store'])
    ->name('api.code-submissions.store');
