<?php

namespace Functional\Inspection\History;

enum InspectionHistoryEvent: string
{
    case PhotoSessionOpened = 'photo_session.opened';
    case PhotoSessionRevoked = 'photo_session.revoked';
    case PhotoReceived = 'photo.received';
    case PhotoDeleted = 'photo.deleted';
    case DamageReported = 'damage.reported';
    case DamageResolved = 'damage.resolved';
}
