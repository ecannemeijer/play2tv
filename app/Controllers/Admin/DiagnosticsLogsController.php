<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use SplFileObject;

class DiagnosticsLogsController extends Controller
{
    private const MAX_RENDER_LINES = 1200;
    private const MAX_RENDER_BYTES = 512000;

    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $selectedFile = trim((string) $this->request->getGet('file'));
        $query = trim((string) $this->request->getGet('q'));
        $severity = trim((string) $this->request->getGet('severity'));
        $term = trim((string) $this->request->getGet('term'));
        $openPicker = $this->request->getGet('openPicker') === '1';
        $logFiles = $this->getLogFiles($query);
        $activeLog = null;
        $contentLines = [];
        $meta = null;
        $parsedEntries = [];
        $totalParsedEntries = 0;
        $headerLines = [];
        $footerLines = [];
        $truncated = false;

        if ($selectedFile !== '') {
            $activeLog = $this->findLogFile($selectedFile, $logFiles) ?? $this->findLogFile($selectedFile, $this->getLogFiles());

            if ($activeLog === null) {
                throw PageNotFoundException::forPageNotFound('Het gekozen logbestand bestaat niet.');
            }

            [$contentLines, $truncated] = $this->readLogLines($activeLog['path']);
            $meta = $this->extractLogMetadata($contentLines, $activeLog);
            ['header' => $headerLines, 'entries' => $parsedEntries, 'footer' => $footerLines] = $this->parseLogContent($contentLines);
            $totalParsedEntries = count($parsedEntries);
            $parsedEntries = $this->filterParsedEntries($parsedEntries, $severity, $term);
        }

        return view('admin/diagnostics/logs', [
            'title' => 'Diagnostics Logs — Play2TV Admin',
            'logFiles' => $logFiles,
            'selectedFile' => $selectedFile,
            'query' => $query,
            'severity' => $severity,
            'term' => $term,
            'openPicker' => $openPicker,
            'activeLog' => $activeLog,
            'contentLines' => $contentLines,
            'meta' => $meta,
            'headerLines' => $headerLines,
            'parsedEntries' => $parsedEntries,
            'totalParsedEntries' => $totalParsedEntries,
            'footerLines' => $footerLines,
            'truncated' => $truncated,
        ]);
    }

    public function download(): ResponseInterface
    {
        $selectedFile = trim((string) $this->request->getGet('file'));
        $logFile = $this->findLogFile($selectedFile, $this->getLogFiles());

        if ($logFile === null) {
            throw PageNotFoundException::forPageNotFound('Het gekozen logbestand bestaat niet.');
        }

        return $this->response->download($logFile['path'], null)->setFileName($logFile['name']);
    }

    public function delete(): ResponseInterface
    {
        $selectedFile = trim((string) $this->request->getPost('file'));
        $logFile = $this->findLogFile($selectedFile, $this->getLogFiles());
        $query = trim((string) $this->request->getPost('q'));
        $redirectQuery = array_filter([
            'q' => $query,
            'openPicker' => '1',
        ]);

        if ($logFile === null) {
            return redirect()->to(base_url('admin/diagnostics/logs' . ($redirectQuery !== [] ? '?' . http_build_query($redirectQuery) : '')))
                ->with('error', 'Het gekozen logbestand bestaat niet meer.');
        }

        if (! @unlink($logFile['path'])) {
            return redirect()->back()->with('error', 'Logbestand kon niet worden verwijderd.');
        }

        return redirect()->to(base_url('admin/diagnostics/logs' . ($redirectQuery !== [] ? '?' . http_build_query($redirectQuery) : '')))
            ->with('success', 'Logbestand verwijderd.');
    }

    public function deleteAll(): ResponseInterface
    {
        $query = trim((string) $this->request->getPost('q'));
        $failures = 0;

        foreach ($this->getLogFiles() as $logFile) {
            if (! @unlink($logFile['path'])) {
                $failures++;
            }
        }

        if ($failures > 0) {
            return redirect()->back()->with('error', 'Niet alle logbestanden konden worden verwijderd.');
        }

        $redirectQuery = array_filter([
            'q' => $query,
            'openPicker' => '1',
        ]);

        return redirect()->to(base_url('admin/diagnostics/logs' . ($redirectQuery !== [] ? '?' . http_build_query($redirectQuery) : '')))
            ->with('success', 'Alle logbestanden zijn verwijderd.');
    }

    /**
    * @return list<array{name: string, path: string, size: int, modified_at: int, device_id: string, generated_at: string, app_version: string, entry_count: string}>
     */
    private function getLogFiles(string $query = ''): array
    {
        $directory = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logs';

        if (! is_dir($directory)) {
            return [];
        }

        $entries = [];
        $needle = strtolower($query);

        foreach (scandir($directory, SCANDIR_SORT_DESCENDING) ?: [] as $fileName) {
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $fileName;
            if (! is_file($fullPath)) {
                continue;
            }

            if ($needle !== '' && ! str_contains(strtolower($fileName), $needle)) {
                continue;
            }

            $entries[] = [
                'name' => $fileName,
                'path' => $fullPath,
                'size' => (int) filesize($fullPath),
                'modified_at' => (int) filemtime($fullPath),
                'device_id' => $this->inferDeviceId($fileName),
                'generated_at' => '',
                'app_version' => '',
                'entry_count' => '',
            ];
        }

        foreach ($entries as &$entry) {
            $summary = $this->readLogSummary($entry['path']);
            $entry['generated_at'] = $summary['generated_at'];
            $entry['app_version'] = $summary['app_version'];
            $entry['entry_count'] = $summary['entry_count'];
        }
        unset($entry);

        usort($entries, static fn (array $left, array $right): int => $right['modified_at'] <=> $left['modified_at']);

        return $entries;
    }

    /**
    * @param list<array{name: string, path: string, size: int, modified_at: int, device_id: string, generated_at: string, app_version: string, entry_count: string}> $files
    * @return array{name: string, path: string, size: int, modified_at: int, device_id: string, generated_at: string, app_version: string, entry_count: string}|null
     */
    private function findLogFile(string $selectedFile, array $files): ?array
    {
        $safeName = basename($selectedFile);

        foreach ($files as $file) {
            if ($file['name'] === $safeName) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return array{0: list<string>, 1: bool}
     */
    private function readLogLines(string $path): array
    {
        $lines = [];
        $truncated = false;
        $byteCount = 0;
        $file = new SplFileObject($path, 'rb');

        while (! $file->eof()) {
            $line = rtrim((string) $file->fgets(), "\r\n");
            $byteCount += strlen($line);
            $lines[] = $line;

            if (count($lines) >= self::MAX_RENDER_LINES || $byteCount >= self::MAX_RENDER_BYTES) {
                $truncated = ! $file->eof();
                break;
            }
        }

        return [$lines, $truncated];
    }

    /**
     * @param list<string> $lines
    * @param array{name: string, path: string, size: int, modified_at: int, device_id: string, generated_at: string, app_version: string, entry_count: string} $activeLog
     * @return array<string, string>
     */
    private function extractLogMetadata(array $lines, array $activeLog): array
    {
        $metadata = [
            'bestand' => $activeLog['name'],
            'device' => $activeLog['device_id'] !== '' ? $activeLog['device_id'] : 'onbekend',
            'grootte' => $this->formatBytes($activeLog['size']),
            'gewijzigd' => date('d-m-Y H:i:s', $activeLog['modified_at']),
        ];

        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if ($key === '' || $value === '') {
                continue;
            }

            if (in_array($key, ['Generated', 'App version', 'Device ID', 'Current channel', 'Latest summary', 'Debug entry count'], true)) {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }

    private function inferDeviceId(string $fileName): string
    {
        if (preg_match('/^([a-z0-9._-]+)_\d{8}_\d{6}/i', $fileName, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return '';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;

        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return number_format($size, $size < 10 ? 1 : 0, ',', '.') . ' ' . $unit;
            }

            $size /= 1024;
        }

        return $bytes . ' B';
    }

    /**
     * @return array{generated_at: string, app_version: string, entry_count: string}
     */
    private function readLogSummary(string $path): array
    {
        $summary = [
            'generated_at' => '',
            'app_version' => '',
            'entry_count' => '',
        ];

        $file = new SplFileObject($path, 'rb');
        $linesRead = 0;

        while (! $file->eof() && $linesRead < 20) {
            $line = trim((string) $file->fgets());
            $linesRead++;

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));

            if ($key === 'Generated') {
                $summary['generated_at'] = $value;
            } elseif ($key === 'App version') {
                $summary['app_version'] = $value;
            } elseif ($key === 'Debug entry count') {
                $summary['entry_count'] = $value;
            }
        }

        return $summary;
    }

    /**
     * @param list<string> $lines
     * @return array{
     *     header: list<string>,
     *     entries: list<array{
     *         timestamp: string,
     *         event: string,
     *         detail: string,
     *         source: string,
     *         resolved: string,
     *         network: string,
     *         tone: string
     *     }>,
     *     footer: list<string>
     * }
     */
    private function parseLogContent(array $lines): array
    {
        $header = [];
        $entries = [];
        $footer = [];
        $mode = 'header';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^\[(\d+)\]\s+([^:]+)\s+::\s+(.*)$/', $line, $matches) === 1) {
                $entries[] = [
                    'timestamp' => $matches[1],
                    'event' => trim($matches[2]),
                    'detail' => trim($matches[3]),
                    'source' => '-',
                    'resolved' => '-',
                    'network' => 'unknown',
                    'tone' => $this->resolveEntryTone(trim($matches[2]), trim($matches[3])),
                ];
                $mode = 'entries';
                continue;
            }

            if ($entries !== [] && preg_match('/^\s{2}(source|resolved|network)=(.*)$/', $line, $matches) === 1) {
                $lastIndex = array_key_last($entries);
                if ($lastIndex !== null) {
                    $entries[$lastIndex][$matches[1]] = trim($matches[2]) !== '' ? trim($matches[2]) : '-';
                }
                continue;
            }

            if ($mode === 'entries') {
                $footer[] = $trimmed;
            } else {
                $header[] = $trimmed;
            }
        }

        return [
            'header' => $header,
            'entries' => $entries,
            'footer' => $footer,
        ];
    }

    private function resolveEntryTone(string $event, string $detail): string
    {
        $haystack = strtolower($event . ' ' . $detail);

        if (preg_match('/error|failed|exception|fatal|rejected|invalid/', $haystack) === 1) {
            return 'error';
        }

        if (preg_match('/warning|buffer|stall|retry|skip|ignored/', $haystack) === 1) {
            return 'warning';
        }

        if (preg_match('/ready|success|uploaded|complete|first_frame|play/', $haystack) === 1) {
            return 'success';
        }

        if (preg_match('/debug|summary|probe/', $haystack) === 1) {
            return 'debug';
        }

        return 'neutral';
    }

    /**
     * @param list<array{timestamp: string, event: string, detail: string, source: string, resolved: string, network: string, tone: string}> $entries
     * @return list<array{timestamp: string, event: string, detail: string, source: string, resolved: string, network: string, tone: string}>
     */
    private function filterParsedEntries(array $entries, string $severity, string $term): array
    {
        $normalizedSeverity = strtolower($severity);
        $normalizedTerm = strtolower($term);

        return array_values(array_filter($entries, static function (array $entry) use ($normalizedSeverity, $normalizedTerm): bool {
            if ($normalizedSeverity !== '' && $normalizedSeverity !== 'all' && $entry['tone'] !== $normalizedSeverity) {
                return false;
            }

            if ($normalizedTerm === '') {
                return true;
            }

            $haystack = strtolower(implode(' ', [
                $entry['event'],
                $entry['detail'],
                $entry['source'],
                $entry['resolved'],
                $entry['network'],
                $entry['timestamp'],
            ]));

            return str_contains($haystack, $normalizedTerm);
        }));
    }
}