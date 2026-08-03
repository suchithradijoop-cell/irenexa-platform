<?php

declare(strict_types=1);

namespace App\Enums;

enum DealStage: string
{
    case Prospecting = 'prospecting';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
}
