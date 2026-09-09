<?php

namespace App\Services;

class ImageUploadService
{
    public function upload($file): string
    {
        $newImgName = uniqid() . '.' .
            $file->getClientOriginalExtension();

        $file->move(
            storage_path('app/public'),
            $newImgName
        );

        return $newImgName;
    }
}