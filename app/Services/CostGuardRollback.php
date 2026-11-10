<?php

namespace App\Services;

/** @internal CostGuard: rolt de transactie terug als één teller vol is. */
final class CostGuardRollback extends \RuntimeException {}
