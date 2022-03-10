<?php

namespace App\Actions\Intervention;

use Intervention\Image\Image;
use Intervention\Image\Filters\FilterInterface;

class GalleryPreview implements FilterInterface
{ 
    public function applyFilter(Image $image)
    {
        return $image->fit(480)->crop(480, 320);
    }
}
