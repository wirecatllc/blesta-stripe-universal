<?php
namespace Blesta\Composer\Installer\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Blesta\Composer\Installer\BlestaInstaller;

/**
 * @coversDefaultClass \Blesta\Composer\Installer\BlestaInstaller
 */
class BlestaInstallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getLocations
     */
    public function testGetLocations()
    {
        $expectedLocations = array(
            'plugin' => 'plugins/',
            'gateway-merchant' => 'components/gateways/merchant/',
            'gateway-nonmerchant' => 'components/gateways/nonmerchant/',
            'module' => 'components/modules/',
            'messenger' => 'components/messenger/',
            'invoice-template' => 'components/invoice_templates/',
            'report' => 'components/reports/',
        );
        $installer = new BlestaInstaller();
        $locations = $installer->getLocations();

        foreach ($expectedLocations as $key => $loc) {
            $this->assertArrayHasKey($key, $locations);
            $this->assertStringStartsWith($loc, $locations[$key]);
        }
    }
}
