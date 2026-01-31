<?php

namespace App\Transformers\API;

use App\Transformers\BaseTransformer;

class ImageTransformer extends BaseTransformer
{
    public function transform($data): array
    {
        return [
            'default' => $data->image,
            'small'   => $data->image_small,
            'medium'  => $data->image_medium,
            'large'   => $data->image_large,
        ];
    }
}
