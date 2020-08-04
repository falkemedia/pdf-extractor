<?php

namespace Robin7331\PdfExtractor\Examples;

use Robin7331\PdfExtractor\Extractor;

require 'vendor/autoload.php';

// Load PDF
$extractor = new Extractor();
$extractor->load('/path/to/a/pdf/file.pdf');

// Generate Thumbnails
$extractor
    ->setMaxThumbnailHeight(600)
    ->setMaxThumbnailWidth(480)
    ->setQuality(75)
    ->generateThumbnails();

// Store Fulltext infos
$extractor->generateTextDatabase();

