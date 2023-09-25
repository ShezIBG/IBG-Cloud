<?php

require_once 'lib/include.php';

/**
 * The library supports the following barcode types:
 *
 * CODE_39 = 'C39'
 * CODE_39_CHECKSUM = 'C39+'
 * CODE_39E = 'C39E'
 * CODE_39E_CHECKSUM = 'C39E+'
 * CODE_93 = 'C93'
 * STANDARD_2_5 = 'S25'
 * STANDARD_2_5_CHECKSUM = 'S25+'
 * INTERLEAVED_2_5 = 'I25'
 * INTERLEAVED_2_5_CHECKSUM = 'I25+'
 * CODE_128 = 'C128'
 * CODE_128_A = 'C128A'
 * CODE_128_B = 'C128B'
 * CODE_128_C = 'C128C'
 * EAN_2 = 'EAN2'
 * EAN_5 = 'EAN5'
 * EAN_8 = 'EAN8'
 * EAN_13 = 'EAN13'
 * UPC_A = 'UPCA'
 * UPC_E = 'UPCE'
 * MSI = 'MSI'
 * MSI_CHECKSUM = 'MSI+'
 * POSTNET = 'POSTNET'
 * PLANET = 'PLANET'
 * RMS4CC = 'RMS4CC'
 * KIX = 'KIX'
 * IMB = 'IMB'
 * CODABAR = 'CODABAR'
 * CODE_11 = 'CODE11'
 * PHARMA_CODE = 'PHARMA'
 * PHARMA_CODE_TWO_TRACKS = 'PHARMA2T'
 *
 * Works with mixed case alphanumeric codes: C128, C128B
 * Works with uppercase alphanumeric codes: C39, C39+, C39E, C39E+, C93, C128A
 * Works with alphanumeric, but not readable by our device: RMS4CC, KIX
 * Not working at all: C128C, IMB
 *
 * Valid outputs: svg, png, jpeg, html
 */

$output = App::get('output', '');
$code = App::get('code', '');
$type = App::get('type', 'C128');
$width_factor = App::get('wf', 2);
$height = App::get('h', 30);

$upcase = ['C39', 'C39+', 'C39E', 'C39E+', 'C93', 'C128A'];
$nums_only = ['S25', 'S25+', 'I25', 'I25+', 'EAN2', 'EAN5', 'EAN8', 'EAN13', 'UPCA', 'UPCE', 'MSI', 'MSI+', 'POSTNET', 'PLANET', 'CODABAR', 'CODE11', 'PHARMA', 'PHARMA2T'];

switch($output) {
	case 'svg':
		header('Content-Type: image/svg+xml');
		$generator = new Picqer\Barcode\BarcodeGeneratorSVG();
		break;

	case 'png':
		header('Content-Type: image/png');
		$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
		break;

	case 'jpeg':
		header('Content-Type: image/jpeg');
		$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
		break;

	default: // html
		header('Content-Type: text/html');
		$generator = new Picqer\Barcode\BarcodeGeneratorHTML();
		break;
}

echo $generator->getBarcode($code, $type, $width_factor, $height);
