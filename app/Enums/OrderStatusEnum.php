<?php

namespace App\Enums;

enum OrderStatusEnum:string
{
    case PENDING = "Pending";
    case APPROVED = "Approved";
    case DELIVERY = "Delivery in progress";
    case DELIVERED = "Delivered";
    case FINISHED = "Finished";
    case CANCELED = "Canceled";
}
