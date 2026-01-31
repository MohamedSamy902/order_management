<?php

namespace App\Transformers\API\V1;

use App\Transformers\API\ImageTransformer;
use App\Transformers\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    public function transform($data): array
    {
        return [
            'id'      => $data->id,
            'name'    => $data->name,
            'email'   => $data->email,
            'image' => (new ImageTransformer())->transform($data),
        ];
    }
}
