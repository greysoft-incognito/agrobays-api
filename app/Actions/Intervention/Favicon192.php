<?php

namespace App\Actions\Intervention;

use Intervention\Image\Image;
use Intervention\Image\Filters\FilterInterface;

class Favicon192 implements FilterInterface
{ 
    public function applyFilter(Image $image)
    {
        return $image->fit(192, 192);
    }
}
