<?php

namespace LaravelEnso\ImageTransformer\app\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use LaravelEnso\ImageTransformer\app\Exceptions\ImageTransformerException;
use LaravelEnso\ImageTransformer\app\Exceptions\MissingDependencyException;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ImageTransformer
{
    const SupportedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    private $file;
    private $image;

    public function __construct(UploadedFile $file)
    {
        $this->validate($file);

        $this->file = $file;
    }

    public function optimize()
    {
        ImageOptimizer::optimize($this->file->getRealPath());

        return $this;
    }

    public function resize(int $width, int $height)
    {
        $image = $this->image();

        if ($image->width() > $width || $image->height() > $height) {
            $image->resize($width, $height);
        }

        $image->save($this->file->getRealPath());

        return $this;
    }

    public function width(int $width)
    {
        $image = $this->image();

        if ($image->width() > $width) {
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        $image->save($this->file->getRealPath());

        return $this;
    }

    public function height(int $height)
    {
        $image = $this->image();

        if ($image->height() > $height) {
            $image->resize(null, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        $image->save($this->file->getRealPath());

        return $this;
    }

    private function validate($file)
    {
        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw new ImageTransformerException(__(
                'Invalid file :file',
                ['file' => $file->getClientOriginalName()]
            ));
        }

        $mimes = implode(',', self::SupportedMimeTypes);

        $validator = \Validator::make(
            ['file' => $file],
            ['file' => 'image|mimetypes:'.$mimes]
        );

        if ($validator->fails()) {
            throw new ImageTransformerException(__(
                'File type not supported for :file',
                ['file' => $file->getClientOriginalName()]
            ));
        }
    }

    private function image()
    {
        if (! isset($this->image)) {
            $this->checkIfExtensionIsLoaded();
            $this->image = Image::make($this->file->getRealPath());
        }

        return $this->image;
    }

    private function checkIfExtensionIsLoaded()
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            throw new MissingDependencyException(__(
                'Extension missing. Please install php-gd or php-imagick extension to use the resize function'
            ));
        }
    }
}
