<?php

namespace App\Actions\Intervention;

use Intervention\Image\Image;
use Intervention\Image\Filters\FilterInterface;

class SmallSquare implements FilterInterface
{ 
    public function applyFilter(Image $image)
    {
        return $image->fit(120, 120);
    }
}
