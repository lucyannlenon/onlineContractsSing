<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Pdf\ChromiumPdf;

$binary = getenv('CHROMIUM_PATH') ?: '/usr/bin/chromium';
$pdf = new ChromiumPdf($binary, sys_get_temp_dir());
$output = $pdf->getOutputFromHtml('<!doctype html><html><body><h1>Contrato Online</h1><p>Smoke test Chromium PDF</p></body></html>');

if (!str_starts_with($output, '%PDF')) {
    fwrite(STDERR, "Chromium did not return PDF bytes.\n");
    exit(1);
}

printf("OK chromium pdf bytes=%d\n", strlen($output));
