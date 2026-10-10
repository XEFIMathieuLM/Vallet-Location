<?php

namespace Functional\Billing\Enums;

enum FakeGatewayMode: string
{
    case Accept = 'accept';
    case Unreachable = 'unreachable';
}
