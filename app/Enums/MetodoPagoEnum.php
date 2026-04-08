<?php

namespace App\Enums;

enum MetodoPagoEnum: string
{
    case Efectivo = 'EFECTIVO';
    case Tarjeta = 'TARJETA';
    case Transferencia = 'TRANSFERENCIA';
    case Otro = 'OTRO';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Tarjeta => 'Tarjeta',
            self::Transferencia => 'Transferencia',
            self::Otro => 'Otro',
        };
    }
}
