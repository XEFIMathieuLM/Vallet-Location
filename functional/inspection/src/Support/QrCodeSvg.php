<?php

namespace Functional\Inspection\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeSvg
{
    public function for(string $url): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd)))->writeString($url);

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
