<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Models\Photo;

class PhotoTemporaryUrl
{
    public function for(Photo $photo, string $conversion): string
    {
        $file = $photo->getFirstMedia(Photo::COLLECTION);

        if ($file === null) {
            return '';
        }

        $availableConversion = $file->hasGeneratedConversion($conversion) ? $conversion : '';

        return $file->getTemporaryUrl(now()->addMinutes(config()->integer('inspection.photo_url_lifetime_minutes')), $availableConversion);
    }
}
