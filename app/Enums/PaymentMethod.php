<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case PAYMENT_CASH = 'cash';
    case PAYMENT_DEBIT = 'debit';
    case PAYMENT_VIREMENT = 'virement';
    case PAYMENT_CHEQUE = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT_CASH => 'Espèces',
            self::PAYMENT_DEBIT => 'Débit',
            self::PAYMENT_VIREMENT => 'Virement',
            self::PAYMENT_CHEQUE => 'Chèque',
        };
    }
}
