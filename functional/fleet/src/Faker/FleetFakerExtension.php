<?php

namespace Functional\Fleet\Faker;

use Xefi\Faker\Extensions\Extension;

class FleetFakerExtension extends Extension
{
    private const array MACHINE_FAMILIES = [
        'Nacelle',
        'Chariot élévateur',
        'Mini-pelle',
        'Compacteur',
        'Groupe électrogène',
        'Plaque vibrante',
        'Chargeuse',
        'Échafaudage',
    ];

    public function agencyName(): string
    {
        return $this->formatString('Agence {l}{l}{l}-{d}{d}');
    }

    public function machineCategoryName(): string
    {
        return $this->pickArrayRandomElement(self::MACHINE_FAMILIES).' '.$this->formatString('{l}{l}{d}{d}');
    }

    public function machineReference(): string
    {
        return $this->formatString('{l}{l}{l}-{d}{d}{d}{d}');
    }
}
