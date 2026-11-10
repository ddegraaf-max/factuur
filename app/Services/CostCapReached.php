<?php

namespace App\Services;

/**
 * Een betaalde koppeling is dicht: noodstop, of een plafond bereikt. De
 * melding is voor de gebruiker en noemt bewust geen plafond of bedrag. Het is
 * een DomainException, zodat bestaande afhandeling (melding tonen, de rest van
 * de app gaat door) vanzelf werkt.
 */
class CostCapReached extends \DomainException
{
    public function __construct(public readonly string $service, public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
