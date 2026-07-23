<?php

namespace App\Enums;

enum SurveyInvitationStatus: string
{
    case Scheduled = 'scheduled';
    case Sent = 'sent';
    case Used = 'used';
    case Revoked = 'revoked';
}
