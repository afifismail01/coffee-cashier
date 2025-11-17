<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case QRIS = 'qris';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
