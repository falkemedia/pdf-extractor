<?php

namespace Robin7331\PdfExtractor;

use Intervention\Image\ImageManager;
use Robin7331\PdfExtractor\Exceptions\PageOutOfBounds;
use Robin7331\PdfExtractor\Exceptions\PdfDoesNotExist;
use Smalot\PdfParser\Parser;
use Imagick;

class Extractor
{

    protected $pdfPath;
    protected $parser;
    protected $pdf;
    protected $imagick;
    protected $pageCount;
    protected $resolution = 144;
    protected $maxThumbnailWidth, $maxThumbnailHeight;
    protected $quality = 90;


    /**
     * Parses a PDF file at a given file path.
     *
     * @param String $file
     * @throws PdfDoesNotExist
     */
    public function parse(string $file)
    {

        if (!file_exists($file)) {
            throw new PdfDoesNotExist("File `{$file}` does not exist");
        }

        // Store the filepath. (Will later be used to determine f.ex. a storage path for images)
        $this->pdfPath = $file;

        // Create the imagick instance and ping the file (to gather basic infos about the pdf like the page count)
        $this->imagick = new Imagick();
        $this->imagick->pingImage($file);

        // Store the page count of the current PDF file.
        $this->pageCount = $this->imagick->getNumberImages();

        // Create the PdfParser object and parse the actual PDF. (This might take some time!)
        $this->parser = new Parser();
        $this->pdf = $this->parser->parseFile($file);

        // Make this call chainable.
        return $this;
    }

    /**
     * Sets the max width of the thumbnail.
     * If reached the height will be calculated in respect to the aspect ratio of the image.
     *
     * @param int $width
     * @return $this
     */
    public function setMaxThumbnailWidth(int $width)
    {
        $this->maxThumbnailWidth = $width;
        return $this;
    }

    /**
     * Sets the max height of the thumbnail.
     * If reached the width will be calculated in respect to the aspect ratio of the image.
     *
     * @param int $height
     * @return $this
     */
    public function setMaxThumbnailHeight(int $height)
    {
        $this->maxThumbnailHeight = $height;
        return $this;
    }

    /**
     * Sets the image quality.
     * Being 0 the lowest and 100 the highest quality.
     *
     * @param int $quality
     * @return $this
     */
    public function setQuality(int $quality)
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * Sets the resolution used by ImageMagic.
     * This is not a px resolution like you might think!
     * Details: https://www.php.net/manual/de/imagick.setresolution.php
     *
     * You probably won't need to touch this.
     * If you like to change the resolution of the resulting thumbnail
     * look at setMaxThumbnailHeight() or setMaxThumbnailWidth().
     *
     * @param int $resolution
     * @return $this
     */
    public function setResolution(int $resolution)
    {
        $this->resolution = $resolution;
        return $this;
    }


    /**
     * This generates a thumbnail from a given page.
     *
     * @param int $page
     * @return \Intervention\Image\Image
     * @throws PageOutOfBounds
     * @throws \ImagickException
     */
    public function generateThumbnailFromPage($page = 1)
    {

        if ($page > $this->pageCount) {
            throw new PageOutOfBounds("Page `{$page}` is beyond the last page the end of this PDF. Total page count is `{$this->pageCount}`");
        }

        if ($page < 1) {
            throw new PageOutOfBounds("Page `{$page}` is not a valid page number.");
        }


        // Reinitialize imagick because the target resolution must be set
        // before reading the actual image.
        $this->imagick = new Imagick();
        $this->imagick->setResolution($this->resolution, $this->resolution);

        // Load the page as image.
        $this->imagick->readImage(sprintf('%s[%s]', $this->pdfPath, $page - 1));

        // Flatten the image.
        $this->imagick = $this->imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        // Create an intervention image from the ImageMagic instance.
        // create an image manager instance with favored driver
        $manager = new ImageManager(['driver' => 'imagick']);

        // to finally create image instances
        $img = $manager->make($this->imagick);


        // If we did set at least one constraint (width or height)
        // we resize the image with respect to the aspect ratio.
        if ($this->maxThumbnailWidth != null || $this->maxThumbnailHeight != null) {
            $img->resize($this->maxThumbnailWidth, $this->maxThumbnailHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        return $img;
    }


    /**
     * Saves an image to the disk at a given path.
     *
     * @param \Intervention\Image\Image $image
     * @param int $pageNumber
     * @param String $storagePath
     * @param String $prefix
     * @param String $suffix
     */
    public function storeThumbnail(\Intervention\Image\Image $image, $pageNumber, $storagePath = null, $prefix = null, $suffix = null)
    {

        if ($image == null) {
            return;
        }

        // If no storagePath is provided we need to create our own.
        // By default the folder of the source PDF is used as a base path
        // with /export/ appended to it.
        $storagePath = ($storagePath == null) ? dirname($this->pdfPath) . '/export/' : $storagePath;

        // Does the storage path already exist?
        // If not lets create it..
        $this->checkPath($storagePath);

        $filename = $pageNumber;

        // If present append the suffix.
        if ($suffix != null) {
            $filename = $filename . $suffix;
        }

        // If present prepend the prefix.
        if ($prefix != null) {
            $filename = $prefix . $filename;
        }

        // finally we save the image as a new file
        $image->save($storagePath . $filename . '.jpg', $this->quality);

    }


    /**
     * Generates thumbnail images from individual pages of the loaded pdf document.
     *
     * @param String $storagePath
     * @param String $prefix
     * @param String $suffix
     * @throws PageOutOfBounds
     * @throws \ImagickException
     */
    public
    function generateThumbnails($storagePath = null, $prefix = null, $suffix = null)
    {

        for ($page = 1; $page <= $this->pageCount; $page++) {
            // Generate the thumbnail for this page.
            $thumbnail = $this->generateThumbnailFromPage($page);

            // Store that thumbnail.
            $this->storeThumbnail($thumbnail, $page, $storagePath, $prefix, $suffix);
        }

    }

    /**
     * Creates a directory at the given path if not present.
     *
     * @param $path
     */
    private function checkPath($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }
    }

    // WiP
    public function getPages()
    {
        return $this->pdf->getPages();
    }


}
