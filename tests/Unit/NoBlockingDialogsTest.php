<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

// QA audit P1: "Approve" / "Start & Issue Materials" looked like 60s+ hangs because they used a
// native confirm() — modal to the whole tab and unanswerable by browser automation. Confirmations
// go through the in-page modal instead: <form data-confirm="…"> or confirmAction({...}).
// This keeps them from creeping back into the admin app (layouts.app pages).
class NoBlockingDialogsTest extends TestCase
{
    // Different layouts — they don't load the layouts.app handlers.
    private const EXEMPT = [
        'portal/profile/edit.blade.php'       => 'customer wallet (layouts.portal)',
        'public/booking-confirmed.blade.php'  => 'standalone public page',
        'tenant/invoice-pdf-style/old.blade.php' => 'unrouted legacy copy',
        'layouts/app.blade.php'               => 'defines the replacements (mentions them in comments)',
    ];

    public function test_admin_views_do_not_use_native_confirm_dialogs(): void
    {
        $offenders = [];
        $root      = dirname(__DIR__, 2) . '/resources/views';

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (isset(self::EXEMPT[$rel])) {
                continue;
            }

            foreach (file($file->getPathname()) as $n => $line) {
                // confirm( not preceded by an identifier char / dot — skips confirmAction( and foo.confirm(
                if (preg_match('/(?<![\w.])confirm\s*\(/', $line)) {
                    $offenders[] = "{$rel}:" . ($n + 1) . '  ' . trim($line);
                }
            }
        }

        $this->assertSame([], $offenders, "Native confirm() found — use data-confirm on the form or confirmAction():\n" . implode("\n", $offenders));
    }
}
