<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;

final readonly class ApplicationLogProcessor
{
    public function __construct(
        private string $applicationName,
        private string $environment,
        private string $workerName = '',
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = [
            'application' => $this->applicationName,
            'environment' => $this->environment,
            'hostname' => gethostname() ?: null,
        ];

        if ($this->workerName !== '') {
            $extra['worker'] = $this->workerName;
        }

        return $record->with(extra: array_replace($extra, $record->extra));
    }
}
