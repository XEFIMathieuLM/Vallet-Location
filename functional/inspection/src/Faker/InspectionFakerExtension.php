<?php

namespace Functional\Inspection\Faker;

use Xefi\Faker\Extensions\Extension;

class InspectionFakerExtension extends Extension
{
    private const array VIEW_NAMES = [
        'Avant',
        'Arrière',
        'Côté gauche',
        'Côté droit',
        'Compteur d\'heures',
        'Godet',
        'Bras',
        'Flèche',
        'Panier',
        'Chenilles',
        'Cabine',
        'Stabilisateurs',
        'Fourches',
        'Plateforme',
    ];

    public function inspectionViewName(): string
    {
        return $this->pickArrayRandomElement(self::VIEW_NAMES);
    }

    public function inspectionNumberedViewName(): string
    {
        return $this->inspectionViewName().' '.$this->formatString('{d}{d}{d}{d}');
    }

    public function inspectionTokenHash(): string
    {
        return $this->randomizer->getBytesFromString('0123456789abcdef', 64);
    }
}
