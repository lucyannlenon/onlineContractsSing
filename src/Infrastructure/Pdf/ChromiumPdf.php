<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use Symfony\Component\Process\Process;

/**
 * Generates PDFs from HTML using headless Chromium.
 */
class ChromiumPdf
{
    public function __construct(
        private readonly string $binary = '/usr/bin/chromium',
        private readonly string $tempDir = '/tmp',
        private readonly int $timeout = 60,
    ) {
    }

    public function getOutputFromHtml(string $html): string
    {
        $outputFile = $this->tempFile('pdf');

        try {
            $this->render($html, $outputFile);
            $pdf = file_get_contents($outputFile);

            if ($pdf === false) {
                throw new \RuntimeException('[ChromiumPdf] Generated PDF could not be read');
            }

            return $pdf;
        } finally {
            $this->removeFileIfExists($outputFile);
        }
    }

    public function generateFromHtml(string $html, string $outputPath): void
    {
        $this->render($html, $outputPath);
    }

    private function render(string $html, string $outputPath): void
    {
        $inputFile = $this->tempFile('html');
        $userDataDir = $this->tempFile('profile');
        file_put_contents($inputFile, $html);

        $process = new Process([
            $this->binary,
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--disable-crash-reporter',
            '--no-zygote',
            '--user-data-dir=' . $userDataDir,
            '--no-pdf-header-footer',
            '--print-to-pdf=' . $outputPath,
            'file://' . $inputFile,
        ]);
        $process->setTimeout($this->timeout);
        $process->setEnv(['HOME' => $this->tempDir]);

        try {
            $process->run();
        } finally {
            $this->removeFileIfExists($inputFile);
            $this->removeDirectory($userDataDir);
        }

        if (!$process->isSuccessful() || !file_exists($outputPath)) {
            throw new \RuntimeException(sprintf(
                '[ChromiumPdf] PDF generation failed: %s',
                $process->getErrorOutput() ?: $process->getOutput()
            ));
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    private function tempFile(string $extension): string
    {
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0775, true);
        }

        return $this->tempDir . '/' . uniqid('chromium_pdf_', true) . '.' . $extension;
    }

    private function removeFileIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
