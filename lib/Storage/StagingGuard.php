<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 APS Conecta
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesUploadGuard\Storage;

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;

final class StagingGuard extends Wrapper {
    public function __construct(IStorage $storage, private readonly IL10N $l10n) {
        parent::__construct(['storage' => $storage]);
    }

    public function isCreatable(string $path): bool {
        if (!preg_match('~(?:^|/)[a-f0-9]{32}\.ocTransferId[0-9]+\.part\z~', $path)) {
            return parent::isCreatable($path);
        }
        if ($this->storage->is_dir($path) && parent::isCreatable($path)) {
            return true;
        }

        $parent = dirname($path);
        if ($parent === '.') {
            $parent = '';
        }
        if ($this->storage->file_exists($path) || !$this->storage->isCreatable($parent)) {
            throw new NotPermittedException($this->l10n->t(
                'No se pudo preparar la carga. Contacte a la administración.'
            ));
        }

        return true;
    }
}
