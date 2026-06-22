<?php
/**
 * Standalone unit-test bootstrap for the module.
 *
 * A Magento module repository has no application context, so the DI-generated
 * `*Factory`, `*CollectionFactory` and extension-attribute classes that the unit
 * tests mock do not exist on disk. Magento ships dedicated test-framework
 * autoloaders that generate those classes on demand; we register them here the
 * same way Magento's own `dev/tests/unit/framework/bootstrap.php` does, so the
 * suite is runnable without a full Magento install.
 */

declare(strict_types=1);

use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesInterfaceGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\FactoryGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader;

require __DIR__ . '/../../vendor/autoload.php';

$generationDirectory = sys_get_temp_dir() . '/commerceleague-activecampaign-generated';
if (!is_dir($generationDirectory)) {
    mkdir($generationDirectory, 0775, true);
}

$generatedCodeAutoloader = new GeneratedClassesAutoloader(
    [
        new FactoryGenerator(),
        new ExtensionAttributesGenerator(),
        new ExtensionAttributesInterfaceGenerator(),
    ],
    new Io(new File(), $generationDirectory)
);

spl_autoload_register([$generatedCodeAutoloader, 'load']);
