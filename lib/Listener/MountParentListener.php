<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 APS Conecta
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesUploadGuard\Listener;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;
use Sabre\HTTP\RequestInterface;

/** @implements IEventListener<SabrePluginAddEvent> */
final class MountParentListener implements IEventListener {
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $session,
    ) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof SabrePluginAddEvent) {
            return;
        }
        $server = $event->getServer();
        // Authenticate first, then initialize parents before DAV resolves the target.
        $server->on('beforeMethod:*', function (RequestInterface $request) use ($server): void {
            $user = $this->session->getUser();
            if ($user === null) {
                return;
            }
            $paths = [$server->getRequestUri()];
            $destination = $request->getHeader('Destination');
            if ($request->getMethod() === 'MOVE' && $destination !== null && $destination !== '') {
                $paths[] = $server->calculateUri($destination);
            }
            $prefix = 'files/' . $user->getUID() . '/';
            foreach ($paths as $path) {
                if (!str_starts_with($path, $prefix)) {
                    continue;
                }
                $parts = explode('/', substr($path, strlen($prefix)));
                if (count($parts) < 2 || in_array($parts[0], ['', '.', '..'], true)) {
                    continue;
                }
                try {
                    $folder = $this->rootFolder->getUserFolder($user->getUID());
                    if (!$folder->nodeExists($parts[0])) {
                        // Native listing creates the missing parents of nested mounts.
                        $folder->getDirectoryListing();
                        return;
                    }
                } catch (InvalidPathException | NotPermittedException) {
                    return;
                }
            }
        }, 90);
    }
}
