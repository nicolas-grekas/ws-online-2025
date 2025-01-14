<?php

namespace App\Calculation;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag()]
interface CalculationInterface
{
    public function calculate(): int;
}