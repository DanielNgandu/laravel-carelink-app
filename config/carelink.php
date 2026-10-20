<?php
// Assessment-only tokens. Replace with central identity/OIDC, short-lived sessions, TLS and audited RBAC in production.
return ['tokens' => [
    env('CLINIC_TOKEN', 'demo-clinician-0101') => ['role' => 'CLINICIAN', 'facility_id' => 'FAC-0101'],
    env('DISTRICT_TOKEN', 'demo-district') => ['role' => 'DISTRICT_OFFICER', 'facility_id' => null],
]];
