<?php

namespace falkemedia\PdfExtractor;

use falkemedia\PdfExtractor\Exceptions\BinaryNotFound;
use Intervention\Image\ImageManager;
use falkemedia\PdfExtractor\Exceptions\PageOutOfBounds;
use falkemedia\PdfExtractor\Exceptions\FileDoesNotExist;
use Imagick;
use Spatie\PdfToText\Pdf;
use SQLite3;

class Extractor
{

    protected $pdfPath;
    protected $pdf;
    protected $imagick;
    protected $pageCount;
    protected $resolution = 144;
    protected $maxThumbnailWidth, $maxThumbnailHeight;
    protected $quality = 90;

    private function generatePdfToTextBinPath()
    {
        $path = exec('which pdftotext');

        if (empty($path)) {
            throw new BinaryNotFound("Could not find the `pdftotext` binary on your system.");
        }

        return $path;
    }

    /**
     * Loads a PDF file from a given file path.
     *
     * @param String $file
     * @return Extractor
     * @throws FileDoesNotExist
     */
    public function load(string $file)
    {

        if (!file_exists($file)) {
            throw new FileDoesNotExist("File `{$file}` does not exist");
        }

        // Store the filepath. (Will later be used to determine f.ex. a storage path for images)
        $this->pdfPath = $file;

        // Create the imagick instance and ping the file (to gather basic infos about the pdf like the page count)
        $this->imagick = new Imagick();
        $this->imagick->pingImage($file);

        // Store the page count of the current PDF file.
        $this->pageCount = $this->imagick->getNumberImages();

        // Create the Pdf object
        $this->pdf = new Pdf($this->generatePdfToTextBinPath());
        $this->pdf->setPdf($file);

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

        $storagePath = $this->checkOrCreateStoragePath($storagePath);

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
    public function generateThumbnails($storagePath = null, $prefix = null, $suffix = null)
    {

        for ($page = 1; $page <= $this->pageCount; $page++) {
            // Generate the thumbnail for this page.
            $thumbnail = $this->generateThumbnailFromPage($page);

            // Store that thumbnail.
            $this->storeThumbnail($thumbnail, $page, $storagePath, $prefix, $suffix);
        }

    }


    /**
     * Returns the text of a single page.
     *
     * @param int $page
     * @return String
     * @throws PageOutOfBounds
     */
    public function getTextOfPage($page = 1)
    {

        if ($page > $this->pageCount) {
            throw new PageOutOfBounds("Page `{$page}` is beyond the last page the end of this PDF. Total page count is `{$this->pageCount}`");
        }

        if ($page < 1) {
            throw new PageOutOfBounds("Page `{$page}` is not a valid page number.");
        }

        return $this->pdf->setOptions(["f {$page}", "l {$page}"])->text();
    }


    /**
     * Returns an array containing the text of one page per item.
     *
     * @return Page[]
     * @throws PageOutOfBounds
     */
    public function getTextOfAllPages()
    {
        $pages = [];

        for ($page = 1; $page <= $this->pageCount; $page++) {

            // Get the text for this page.
            $text = $this->getTextOfPage($page);

            $pages[] = new Page($page, $text);
        }

        return $pages;
    }


    /**
     * This generates an optimized SQLite database storage containing the pages and their text contents.
     * It can be used to do an efficient full-text search.
     * It uses an FTS4 table for optimal performance.
     * More info: https://sqlite.org/fts3.html
     *
     * @param String $storagePath
     * @param String $filename
     * @param string $tableName
     * @throws PageOutOfBounds
     */
    public function generateTextDatabase($storagePath = null, $filename = null, $tableName = "pages")
    {

        // Generate a full path with the given storage path and filename.
        $storagePath = $this->checkOrCreateStoragePath($storagePath, $filename ?? "database.sqlite");

        $db = new SQLite3($storagePath);
        $db->enableExceptions(true);

        try {
            $db->exec("DROP TABLE {$tableName}");
        } catch (\Exception $e) {}

        try {
            $db->exec("CREATE VIRTUAL TABLE {$tableName} USING fts4(page, body);");
        } catch (\Exception $e) {}

        // This will return an array full of Page objects.
        $pages = $this->getTextOfAllPages();

        foreach ($pages as $page) {
            try {
                $db->query("INSERT INTO {$tableName}(page, body) VALUES($page->number, '{$page->text}');");
            } catch (\Exception $e) {
                echo "There was a problem storing the contents of page {$page->number}\n";
                echo $e->getMessage();
                echo "\n\n\n";
            }

        }
    }


    /**
     * Checks if a given storage path is valid.
     * If not it generates a path based on the PDF path.
     *
     * @param String $storagePath
     * @param String $filename
     * @return string
     */
    private function checkOrCreateStoragePath($storagePath = null, $filename = null)
    {
        // If no databaseFilepath is provided we need to create our own.
        // By default the folder of the source PDF is used as a base path
        // with /export/ appended to it.
        $storagePath = ($storagePath == null) ? dirname($this->pdfPath) . '/export' : $storagePath;

        // remove any trailing slash if present so we have known string.
        $storagePath = rtrim($storagePath, '/');

        // Now we know we have exactly one trailing slash :)
        $storagePath = $storagePath . "/";

        // Does the storage path already exist?
        // If not lets create it..
        $this->checkPath($storagePath);

        // Did we provide a filename?
        if ($filename != null) {
            $storagePath = $storagePath . $filename;
        }

        return $storagePath;
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

}
