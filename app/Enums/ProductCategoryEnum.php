<?php

namespace App\Enums;

enum ProductCategoryEnum: string
{
    case KOPI = 'kopi';
    case NON_KOPI = 'non-kopi';
    case MAKANAN = 'makanan';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
