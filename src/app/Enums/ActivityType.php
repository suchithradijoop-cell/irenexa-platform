<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Task = 'task';
}
