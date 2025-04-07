<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiGatewayController;



Route::post('/v1/slots/add', [ApiGatewayController::class, 'addSlot']);
Route::get('/v1/slots/free/{id}', [ApiGatewayController::class, 'getFreeSlots']);
Route::post('/v1/appointments/book', [ApiGatewayController::class, 'bookAppointment']);

Route::get('/v1/get-patients/{id}', [ApiGatewayController::class, 'getPatient']);
Route::get('/v1/find-patients', [ApiGatewayController::class, 'findPatients']);
Route::post('/v1/add-patients', [ApiGatewayController::class, 'addPatient']);
Route::post('/v1/patients/{patientId}/add-data', [ApiGatewayController::class, 'addPatientData']);
