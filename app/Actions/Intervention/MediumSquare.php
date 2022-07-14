<?php

namespace App\Actions\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class MediumSquare implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(240, 240);
    }
}
