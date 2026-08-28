<?php

namespace App\Services\Ponto;

/** Fout van de Ponto/Ibanity-API, met HTTP-status en (indien bekend) de foutcode van Ponto. */
class PontoException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly ?string $pontoCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
