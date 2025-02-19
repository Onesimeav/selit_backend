<?php

namespace App\Enums;

enum StatsPeriodEnum: string
{
    case DAILY = "Daily";
    case WEEKLY = "Weekly";
    case MONTHLY = "Monthly";
    case YEARLY = "Yearly";
}
