<?php

namespace Functional\Inspection\Http\Controllers;

use Functional\Inspection\Models\Photo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoFileController
{
    public function __invoke(Request $request, Photo $photo, string $conversion): StreamedResponse
    {
        abort_unless(in_array($conversion, [Photo::THUMB, Photo::DISPLAY], true), 404);

        $file = $photo->getFirstMedia(Photo::COLLECTION) ?? abort(404);

        return $file->toInlineResponse($request, $file->hasGeneratedConversion($conversion) ? $conversion : '');
    }
}
