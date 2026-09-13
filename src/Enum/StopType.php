<?php

namespace App\Enum;

enum StopType: string
{
    case START = "start";
    CASE INTERMEDIATE = "intermediate";
    case SPLIT = "split";
    CASE END = "end";
}
