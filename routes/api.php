<?php

use App\Http\Controllers\Api\EmailReplyWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('email/replies', EmailReplyWebhookController::class)->middleware('throttle:30,1');
