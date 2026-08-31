<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentSlot: string
{
    case SLOT_DEPOSIT = 'deposit';
    case SLOT_FIRST_INSTALLMENT = 'first';
    case SLOT_SECOND_INSTALLMENT = 'second';
    case SLOT_BALANCE = 'balance';

    public function label(): string
    {
        return match ($this) {
            self::SLOT_DEPOSIT => 'Dépôt',
            self::SLOT_FIRST_INSTALLMENT => '1er versement',
            self::SLOT_SECOND_INSTALLMENT => '2e versement',
            self::SLOT_BALANCE => 'Solde',
        };
    }
}
