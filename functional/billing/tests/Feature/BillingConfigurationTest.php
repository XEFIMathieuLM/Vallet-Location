<?php

namespace Functional\Billing\Tests\Feature;

use Tests\TestCase;

class BillingConfigurationTest extends TestCase
{
    public function test_every_filesystem_value_the_billing_layer_declares_is_the_value_the_application_reads(): void
    {
        $declaredFilesystems = require base_path('functional/billing/config/filesystems.php');
        $loadedFilesystems = config()->array('filesystems');

        $this->assertSame(serialize($loadedFilesystems), serialize(array_replace_recursive($loadedFilesystems, $declaredFilesystems)));
        $this->assertSame(storage_path('app/private/billing-exports'), config('filesystems.disks.billing-exports.root'));
    }

    public function test_the_export_disk_is_no_longer_declared_at_the_project_root(): void
    {
        $rootFilesystems = require base_path('config/filesystems.php');

        $this->assertArrayNotHasKey('billing-exports', $rootFilesystems['disks']);
    }
}
