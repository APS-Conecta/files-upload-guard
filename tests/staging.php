<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 APS Conecta
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$root = getenv('NEXTCLOUD_ROOT');
require $root . '/3rdparty/autoload.php';
require $root . '/lib/composer/autoload.php';
require __DIR__ . '/../lib/Storage/StagingGuard.php';

use OC\Files\Storage\Wrapper\Wrapper;
use OCA\FilesUploadGuard\Storage\StagingGuard;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\IL10N;

$l10n = new class implements IL10N {
    public function t(string $text, $parameters = []): string { return $text; }
    public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string {
        return $count === 1 ? $text_singular : $text_plural;
    }
    public function l(string $type, $data, array $options = []) { return $data; }
    public function getLanguageCode(): string { return 'es'; }
    public function getLocaleCode(): string { return 'es_CL'; }
};
$stage = str_repeat('a', 32) . '.ocTransferId42.part';
$ordinaryDirectory = 'reports.ocTransferId42.part';
$storage = new class($ordinaryDirectory, $stage) extends Wrapper {
    public array $directories;
    public array $existing;
    public function __construct(string $ordinaryDirectory, string $stage) {
        $this->directories = ['', 'files', $ordinaryDirectory, 'nested/' . $stage];
        $this->existing = [...$this->directories, 'files/previous.txt', 'occupied/' . $stage];
    }
    public function getId(): string { return 'upload-fixture'; }
    public function file_exists(string $path): bool { return in_array($path, $this->existing, true); }
    public function is_dir(string $path): bool { return in_array($path, $this->directories, true); }
    public function isCreatable(string $path): bool { return $this->is_dir($path); }
    public function isUpdatable(string $path): bool { return $path === 'files/previous.txt'; }
};
$guard = new StagingGuard($storage, $l10n);
$failed = 0;
$checks = 0;
function check(string $name, callable $case): void {
    global $failed, $checks;
    $checks++;
    try {
        if ($case() !== true) {
            throw new RuntimeException('unexpected result');
        }
    } catch (Throwable $error) {
        $failed++;
        echo 'FAIL: ' . $name . ' (' . get_class($error) . ')' . PHP_EOL;
    }
}
function refuses(callable $case): bool {
    try {
        $case();
        return false;
    } catch (NotPermittedException $error) {
        return str_contains($error->getMessage(), 'administración');
    }
}
check('new staging in a writable parent', fn () => $guard->isCreatable('files/' . $stage));
check('root staging keeps its empty storage path', fn () => $guard->isCreatable($stage));
check('denied parent refuses direct-write fallback', fn () => refuses(fn () => $guard->isCreatable('denied/' . $stage)));
check('occupied staging refuses replacement', fn () => refuses(fn () => $guard->isCreatable('occupied/' . $stage)));
check('ordinary directories retain CREATE', fn () => $guard->isCreatable($ordinaryDirectory));
check('existing directories matching the complete convention retain CREATE', fn () => $guard->isCreatable('nested/' . $stage));
check('ordinary file probes stay non-creatable', fn () => $guard->isCreatable('files/new.txt') === false);
check('directory probes delegate', fn () => $guard->isCreatable('files'));
check('target UPDATE permission remains denied', fn () => $guard->isUpdatable('denied.txt') === false);
check('target UPDATE permission remains allowed', fn () => $guard->isUpdatable('files/previous.txt'));
check('native storage traversal retains its identity', fn () => $guard->getWrapperStorage() === $storage);
check('native locking and stream capabilities remain present', fn () => $guard instanceof ILockingStorage && $guard instanceof IWriteStreamStorage);
printf('staging checks: %d, failed: %d\n', $checks, $failed);
exit($failed === 0 ? 0 : 1);
