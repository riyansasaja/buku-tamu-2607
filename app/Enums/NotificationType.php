<?php

namespace App\Enums;

enum NotificationType: string
{
    case EmployeeArrival = 'employee_arrival';
    case ReceptionAccepted = 'reception_accepted';
    case ReceptionRejected = 'reception_rejected';
    case GuestSurvey = 'guest_survey';
}
