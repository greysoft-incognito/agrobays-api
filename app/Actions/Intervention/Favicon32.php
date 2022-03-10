<?php

namespace App\Actions\Intervention;

use Intervention\Image\Image;
use Intervention\Image\Filters\FilterInterface;

class Favicon32 implements FilterInterface
{ 
    public function applyFilter(Image $image)
    {
        return $image->fit(32, 32);
    }
}
