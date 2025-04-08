<?php

namespace Tests\Feature;

use App\Http\Controllers\ApiGatewayController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiGatewayControllerTest extends TestCase
{
    private $slotsServiceUrl = 'http://slots-service.test';
    private $patientsServiceUrl = 'http://patients-service.test';
    private $controller;
    private $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the environment variables
        app()->instance('env', function ($key, $default = null) {
            return match ($key) {
                'APPOINTMENT_SERVICE_URL' => $this->slotsServiceUrl,
                'PATIENT_SERVICE_URL' => $this->patientsServiceUrl,
                default => $default,
            };
        });

        // Create a mock handler for Guzzle client
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        // Create controller instance with mocked client
        $this->controller = new ApiGatewayController();
        $this->setProtectedProperty($this->controller, 'httpClient', $client);
        $this->setProtectedProperty($this->controller, 'slotsServiceUrl', $this->slotsServiceUrl);
        $this->setProtectedProperty($this->controller, 'patientsServiceUrl', $this->patientsServiceUrl);
    }

    private function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function test_add_slot_successfully_forwards_request()
    {
        $mockResponse = ['success' => true];
        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $request = new HttpRequest([], [], [], [], [], [], json_encode([
            'doctor_id' => 1,
            'start_time' => '2023-01-01 10:00:00',
            'end_time' => '2023-01-01 11:00:00'
        ]));

        $response = $this->controller->addSlot($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_get_free_slots_successfully_forwards_request()
    {
        $doctorId = 1;
        $mockResponse = ['slots' => ['2023-01-01 10:00:00', '2023-01-01 11:00:00']];
        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->controller->getFreeSlots($doctorId);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_get_free_slots_returns_validation_error_for_invalid_doctor_id()
    {
        $doctorId = 0; // Invalid ID

        $response = $this->controller->getFreeSlots($doctorId);
        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('validation_failed', $data['errors'][0]['code']);
        $this->assertArrayHasKey('doctor_id', $data['errors'][0]['meta']);
    }

    public function test_book_appointment_successfully_forwards_request()
    {
        $mockResponse = ['appointment_id' => 123];
        $this->mockHandler->append(new Response(201, [], json_encode($mockResponse)));

        $request = new HttpRequest([], [], [], [], [], [], json_encode([
            'slot_id' => 1,
            'patient_id' => 1,
            'notes' => 'Test appointment'
        ]));

        $response = $this->controller->bookAppointment($request);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_get_patient_successfully_forwards_request()
    {
        $patientId = 1;
        $mockResponse = ['id' => $patientId, 'name' => 'John Doe'];
        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $response = $this->controller->getPatient($patientId);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_find_patients_successfully_forwards_request()
    {
        $mockResponse = ['patients' => [['id' => 1, 'name' => 'John Doe']]];
        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $request = new HttpRequest(['query' => 'John']);
        $response = $this->controller->findPatients($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_add_patient_successfully_forwards_request()
    {
        $mockResponse = ['id' => 1, 'name' => 'New Patient'];
        $this->mockHandler->append(new Response(201, [], json_encode($mockResponse)));

        $request = new HttpRequest([], [], [], [], [], [], json_encode([
            'name' => 'New Patient',
            'email' => 'patient@example.com'
        ]));

        $response = $this->controller->addPatient($request);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_add_patient_data_successfully_forwards_request()
    {
        $patientId = 1;
        $mockResponse = ['success' => true];
        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $request = new HttpRequest([], [], [], [], [], [], json_encode([
            'medical_history' => 'Some history',
            'allergies' => 'None'
        ]));

        $response = $this->controller->addPatientData($request, $patientId);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, $response->getData(true));
    }

    public function test_handle_exception_returns_service_unavailable_for_guzzle_exception()
    {
        $this->mockHandler->append(new ClientException(
            'Service unavailable',
            new Request('POST', 'test'),
            new Response(503)
        ));

        $request = new HttpRequest();
        $response = $this->controller->addSlot($request);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Service unavailable',
            'message' => 'Service unavailable',
        ], $response->getData(true));
    }

    public function test_handle_exception_returns_500_for_generic_exception()
    {
        $this->mockHandler->append(new \Exception('Generic error'));

        $request = new HttpRequest();
        $response = $this->controller->addSlot($request);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Service unavailable',
            'message' => 'Generic error',
        ], $response->getData(true));
    }
}