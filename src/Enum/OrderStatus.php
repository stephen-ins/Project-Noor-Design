<?php

namespace App\Enum;

enum OrderStatus: string
{
    case EN_ATTENTE = 'en attente';
    case CONFIRMEE = 'confirmée';
    case EXPEDIEE = 'expédiée';
    case LIVREE = 'livrée';
    case ANNULEE = 'annulée';

    public function getLabel(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRMEE => 'Confirmée',
            self::EXPEDIEE => 'Expédiée',
            self::LIVREE => 'Livrée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'bg-gray-200',
            self::CONFIRMEE => 'bg-blue-200',
            self::EXPEDIEE => 'bg-yellow-200',
            self::LIVREE => 'bg-green-200',
            self::ANNULEE => 'bg-red-200',
        };
    }
}
