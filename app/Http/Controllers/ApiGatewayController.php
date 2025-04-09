<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiGatewayController extends Controller
{
    private const ERROR_CODES = [
        'VALIDATION_FAILED' => 'validation_failed',
        'DOCTOR_ID_REQUIRED' => 'doctor_id_required',
        'SERVICE_UNAVAILABLE' => 'service_unavailable',
    ];

    private $slotsServiceUrl;

    private $patientsServiceUrl;

    private $httpClient;

    public function __construct()
    {
        // @phpstan-ignore-next-line
        $this->slotsServiceUrl = env('APPOINTMENT_SERVICE_URL', 'http://localhost:1234');
        // @phpstan-ignore-next-line
        $this->patientsServiceUrl = env('PATIENT_SERVICE_URL', 'http://192.168.0.100:8000');
        $this->httpClient = new Client([
            'timeout' => 2.0,
        ]);
    }

    // Методы для работы со слотами

    public function addSlot(Request $request)
    {
        try {
            $response = $this->httpClient->post("{$this->slotsServiceUrl}/api/v1/add-slots", [
                'json' => $request->all(),
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    public function getFreeSlots($doctor_id)
    {
        try {

            $validator = Validator::make(['doctor_id' => $doctor_id], [
                'doctor_id' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => [[
                        'code' => self::ERROR_CODES['VALIDATION_FAILED'],
                        'message' => 'Validation failed',
                        'meta' => $validator->errors()->toArray(),
                    ]],
                    'data' => null,
                ], 400);
            }

            $response = $this->httpClient->get("{$this->slotsServiceUrl}/api/v1/free-slots/{$doctor_id}");

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function bookAppointment(Request $request)
    {
        try {
            $response = $this->httpClient->post("{$this->slotsServiceUrl}/api/v1/book-appointments", [
                'json' => $request->all(),
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    // Методы для работы с пациентами

    public function getPatient($id)
    {
        try {
            $response = $this->httpClient->get("{$this->patientsServiceUrl}/api/v1/patients/{$id}");

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    public function findPatients(Request $request)
    {
        try {
            $response = $this->httpClient->get("{$this->patientsServiceUrl}/api/v1/search-patients", [
                'query' => $request->query(),
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    public function addPatient(Request $request)
    {
        try {
            $response = $this->httpClient->post("{$this->patientsServiceUrl}/api/v1/add-patients", [
                'json' => $request->all(),
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    public function addPatientData(Request $request, $patientId)
    {
        try {
            $response = $this->httpClient->post("{$this->patientsServiceUrl} /api/v1/patients/add-medical-history/{$patientId}", [
                'json' => $request->all(),
            ]);

            return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    private function handleException(\Exception $e)
    {
        $statusCode = 500;
        $message = $e->getMessage();

        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            $statusCode = $e->getResponse()->getStatusCode();

            try {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                if (isset($responseBody['errors']) && is_array($responseBody['errors']) && !empty($responseBody['errors'])) {
                    $message = $responseBody['errors'][0]['message'] ?? $message;
                }
            } catch (\Exception $jsonException) {
                // В случае ошибки парсинга JSON
            }
        }

        return response()->json([
            'error' => 'Service unavailable',
            'message' => $message,
        ], $statusCode);
    }
}
