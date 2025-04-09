
# API_GATEWAY для медицинской системы

## Описание проекта
RESTful API для управления маршрутизацией запросов

## Требования
- PHP 8.4.1
- Composer 2.8.3
- SQLite 2.6.0
- Laravel 12.1.1
  
## Установка
```bash
git clone https://github.com/nadezhkinaa/doctor_api_gateway.git
cd doctor_api_gateway
git config core.hooksPath .githooks/
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

# API Endpoints
## 1. Создание слотов
```POST /api/v1/slots/add```

Тело:

```json
{
    "doctor_id": 3,
    "start_time": "2023-11-22 19:00:00",
    "end_time": "2023-11-22 19:20:00"
}
```
Ответ:
```json
{
    "errors": [],
    "data": {
        "doctor_id": 3,
        "start_time": "2024-11-22 19:00:00",
        "end_time": "2024-11-22 19:20:00",
        "is_available": true,
        "updated_at": "2025-04-08T18:13:45.000000Z",
        "created_at": "2025-04-08T18:13:45.000000Z",
        "id": 16
    }
}
```
## 2. Получить информацию о пациенте по ID

```GET /api/v1/slots/free/{id}```

Пример:

```GET /api/v1/slots/free/3```

Ответ:
```json
{
    "errors": [],
    "data": [
        {
            "id": 15,
            "doctor_id": 3,
            "start_time": "2023-11-22 19:00:00",
            "end_time": "2023-11-22 19:20:00",
            "is_available": 1,
            "created_at": "2025-04-07T16:19:30.000000Z",
            "updated_at": "2025-04-07T16:19:30.000000Z"
        },
        {
            "id": 16,
            "doctor_id": 3,
            "start_time": "2024-11-22 19:00:00",
            "end_time": "2024-11-22 19:20:00",
            "is_available": 1,
            "created_at": "2025-04-08T18:13:45.000000Z",
            "updated_at": "2025-04-08T18:13:45.000000Z"
        }
    ]
}
```
## 3. Запись на прием

```POST /api/v1/appointments/book```

Тело:

```json
{
    "schedule_id":15,
    "patient_id": "2"
}
```

Ответ:

```json
{
    "message": "Appointment booked successfully",
    "data": {
        "schedule_id": 15,
        "patient_id": "2",
        "updated_at": "2025-04-08T18:18:35.000000Z",
        "created_at": "2025-04-08T18:18:35.000000Z",
        "id": 8
    }
}
```
## 4. Создать пациента
```POST /api/v1/add-patients```

Тело:

```json
{
    "first_name": "Antonio",
    "last_name": "Banderas",
    "age": 64
}
```
Ответ:
```json
{
    "data": {
        "first_name": "Antonio",
        "last_name": "Banderas",
        "middle_name": null,
        "age": 64,
        "medical_history": null,
        "updated_at": "2025-04-07T13:41:57.000000Z",
        "created_at": "2025-04-07T13:41:57.000000Z",
        "id": 13
    }
}
```
## 5. Получить информацию о пациенте по ID

```GET /api/v1/get-patients/{id}```

Пример:

```GET /api/v1/get-patient/13```

Ответ:
```json
{
    "data": {
        "id": 13,
        "first_name": "Antonio",
        "last_name": "Banderas",
        "middle_name": null,
        "age": 64,
        "medical_history": null,
        "created_at": "2025-04-07T13:41:57.000000Z",
        "updated_at": "2025-04-07T13:41:57.000000Z"
    }
}
```
## 6. Поиск пациентов

```GET /api/v1/find-patients?```

Пример:

```GET /api/v1/find-patients?first_name=Antonio&age=64```

Ответ:

```json
{
    "data": [
        {
            "id": 13,
            "first_name": "Antonio",
            "last_name": "Banderas",
            "middle_name": null,
            "age": 64,
            "medical_history": null,
            "created_at": "2025-04-07T13:41:57.000000Z",
            "updated_at": "2025-04-07T13:41:57.000000Z"
        }
    ]
}
```
## 7. Добавить медзапись

```POST /api/v1/patients/add-data/{id}```

Пример:

```POST /api/v1/patients/add-data/13```

Тело:

```json
{"medical_history": "Small cough."}
```
Ответ:

```json
{
    "data": {
        "id": 13,
        "first_name": "Antonio",
        "last_name": "Banderas",
        "middle_name": null,
        "age": 64,
        "medical_history": "\nSmall cough.",
        "created_at": "2025-04-07T13:41:57.000000Z",
        "updated_at": "2025-04-07T13:44:27.000000Z"
    }
}

```
