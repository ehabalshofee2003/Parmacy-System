<?php

namespace App\Enums;

enum NotificationType: string
{
    case EXPIRY_SOON = 'expiry_soon';
    case EXPIRED_DRUG = 'expired_drug';
    case STOCK_LOW = 'stock_low';
    case STOCK_OUT = 'stock_out';
    case INVOICE_NOT_SENT = 'invoice_not_sent';
}
/*
    'type' => NotificationType::EXPIRED_DRUG->value,

*/
