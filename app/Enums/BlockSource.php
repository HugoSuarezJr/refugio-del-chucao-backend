<?php

namespace App\Enums;

enum BlockSource: string
{
    case Manual = 'manual';
    case Maintenance = 'maintenance';
    case Owner = 'owner';
    case Calendar = 'calendar';
    case System = 'system';
}
