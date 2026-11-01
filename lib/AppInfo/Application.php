<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 APS Conecta
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesUploadGuard\AppInfo;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\FilesUploadGuard\Listener\FilesystemSetupListener;
use OCA\FilesUploadGuard\Listener\MountParentListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\BeforeFileSystemSetupEvent;

final class Application extends App implements IBootstrap {
    public function __construct(array $urlParams = []) {
        parent::__construct('files_upload_guard', $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(BeforeFileSystemSetupEvent::class, FilesystemSetupListener::class);
        $context->registerEventListener(SabrePluginAddEvent::class, MountParentListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
