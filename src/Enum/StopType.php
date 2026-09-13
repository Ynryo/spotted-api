<?php

namespace App\Enum;

enum StopType: string
{
    case START = "START";
    CASE INTERMEDIATE = "INTERMEDIATE";
    case SPLIT = "SPLIT";
    CASE END = "END";
}
