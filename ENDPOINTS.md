Lista endpointów

Auth
POST   /api/auth/login
POST   /api/auth/logout

Dentyści
GET    /api/dentists
POST   /api/dentists
GET    /api/dentists/{id}
PUT    /api/dentists/{id}
DELETE /api/dentists/{id}
GET    /api/dentists/{id}/availability?date=2025-03-15&type=1

Typy wizyt
GET    /api/appointment-types
POST   /api/appointment-types
PUT    /api/appointment-types/{id}
DELETE /api/appointment-types/{id}

Pacjenci
GET    /api/patients?search=kowalski
POST   /api/patients
GET    /api/patients/{id}
PUT    /api/patients/{id}
DELETE /api/patients/{id}
GET    /api/patients/{id}/appointments

Wizyty
GET    /api/appointments?date=2025-03-15&dentist_id=1&status=scheduled
POST   /api/appointments
GET    /api/appointments/{id}
PUT    /api/appointments/{id}
POST   /api/appointments/{id}/confirm
POST   /api/appointments/{id}/cancel
POST   /api/appointments/{id}/complete
POST   /api/appointments/{id}/no-show

Zablokowane terminy
GET    /api/dentists/{id}/blocked-slots
POST   /api/dentists/{id}/blocked-slots
DELETE /api/dentists/{id}/blocked-slots/{id}
