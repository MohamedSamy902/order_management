<?php

namespace App\Traits\Model;

trait HasImage
{
    protected $defaultImageColumns = [
        'image',
    ];

    /**
     * Generate image URL with size prefix
     */
    protected function getImageUrl($imagePath, $size = null)
    {
        if (!$imagePath) {
            return null;
        }

        $prefix = $this->getSizePrefix($size);

        if (!empty($prefix)) {
            $imagePath = $this->addSizePrefix($imagePath, $prefix);
        }

        $image = asset('storage/' . $imagePath);
        return $image;
    }

    /**
     * Get size prefix based on size parameter
     */
    protected function getSizePrefix($size)
    {
        // ✅ مبدأ OCP: لإضافة حجم جديد، عدّل هنا فقط
        return match (strtolower($size)) {
            'large' => 'thumb_large_',
            'medium' => 'thumb_medium_',
            'small' => 'thumb_small_',
            default => ''
        };
    }

    /**
     * Add size prefix to filename in path
     */
    protected function addSizePrefix($path, $prefix)
    {
        $pathParts = explode('/', $path);
        $fileName = end($pathParts);
        $pathParts[count($pathParts) - 1] = $prefix . $fileName;
        return implode('/', $pathParts);
    }


    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // الأعمدة المتاحة
        $availableColumns = property_exists($this, 'imageColumns')
            ? $this->imageColumns
            : $this->defaultImageColumns;

        // ✅ 1) لو المفتاح مباشرةً من الأعمدة (زي image أو flag)
        if (in_array($key, $availableColumns)) {
            return $this->getImageUrl($value);
        }

        // ✅ 2) لو المفتاح بصيغة image_small أو flag_large
        if (preg_match('/^(.*)_(small|medium|large)$/i', $key, $matches)) {
            $baseKey = $matches[1];
            $size = $matches[2];

            if (in_array($baseKey, $availableColumns)) {
                $baseValue = parent::getAttribute($baseKey);
                return $this->getImageUrl($baseValue, $size);
            }
        }

        return $value;
    }

    public function getImageOriginalAttribute()
    {
        return parent::getAttribute('image');
    }
}
