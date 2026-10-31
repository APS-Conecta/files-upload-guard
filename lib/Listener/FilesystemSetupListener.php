<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 APS Conecta
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesUploadGuard\Listener;

use OCA\FilesUploadGuard\Storage\StagingGuard;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use OCP\IL10N;
use OCP\IRequest;

/** @implements IEventListener<BeforeFileSystemSetupEvent> */
final class FilesystemSetupListener implements IEventListener {
    public function __construct(
        private readonly IStorageFactory $factory,
        private readonly IRequest $request,
        private readonly IL10N $l10n,
    ) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof BeforeFileSystemSetupEvent
            || !in_array($this->request->getMethod(), ['PUT', 'MOVE'], true)) {
            return;
        }

        $this->factory->addStorageWrapper(
            'aps_upload_staging',
            fn (string $mountPoint, IStorage $storage): IStorage => new StagingGuard($storage, $this->l10n),
        );
    }
}
