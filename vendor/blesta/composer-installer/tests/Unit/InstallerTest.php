<?php
namespace Blesta\Composer\Installer\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Blesta\Composer\Installer\Installer;
use Composer\Composer;
use Composer\Config;

/**
 * @coversDefaultClass \Blesta\Composer\Installer\Installer
 */
class InstallerTest extends PHPUnit_Framework_TestCase
{
    private $io;
    private $composer;

    public function setUp()
    {
        $this->io = $this->getMockBuilder('\Composer\IO\IOInterface')
            ->getMock();
        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);
    }

    /**
     * @covers ::getInstallPath
     * @covers ::supportedType
     * @dataProvider installPathProvider
     */
    public function testGetInstallPath($packageType, $expected)
    {
        $installer = new Installer($this->io, $this->composer);

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')
            ->getMock();
        $package->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($packageType));
        $package->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/name'));

        $this->assertEquals($expected, $installer->getInstallPath($package));
    }

    /**
     * Data provider for testGetInstallPath
     *
     * @return array
     */
    public function installPathProvider()
    {
        return array(
            array('blesta-plugin', 'plugins/name/'),
            array('blesta-module', 'components/modules/name/'),
            array('blesta-messenger', 'components/messenger/name/'),
            array('blesta-gateway-merchant', 'components/gateways/merchant/name/'),
            array('blesta-gateway-nonmerchant', 'components/gateways/nonmerchant/name/'),
            array('blesta-invoice-template', 'components/invoice_templates/name/'),
            array('blesta-report', 'components/reports/name/')
        );
    }

    /**
     * @covers ::getInstallPath
     * @covers ::supportedType
     * @expectedException \InvalidArgumentException
     */
    public function testGetInstallPathException()
    {
        $installer = new Installer($this->io, $this->composer);

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')
            ->getMock();
        $package->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('invalid'));
        $installer->getInstallPath($package);
    }

    /**
     * @covers ::uninstall
     * @covers ::getInstallPath
     * @covers ::supportedType
     */
    public function testUninstall()
    {
        $installer = new Installer($this->io, $this->composer);

        $this->io->expects($this->once())
            ->method('write');

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')
            ->getMock();
        $package->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('blesta-plugin'));

        $repo = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
            ->getMock();
        $repo->expects($this->once())
            ->method('removePackage')
            ->with($this->equalTo($package));
        $repo->expects($this->once())
            ->method('hasPackage')
            ->with($this->equalTo($package))
            ->will($this->returnValue(true));

        $installer->uninstall($repo, $package);
    }

    /**
     * @covers ::uninstall
     * @expectedException \InvalidArgumentException
     */
    public function testUninstallException()
    {
        $installer = new Installer($this->io, $this->composer);

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')
            ->getMock();
        $repo = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
            ->getMock();
        $repo->expects($this->once())
            ->method('hasPackage')
            ->will($this->returnValue(false));

        $installer->uninstall($repo, $package);
    }

    /**
     * @covers ::supports
     * @covers ::supportedType
     * @dataProvider packageTypeProvider
     * @param string $packageType
     * @param boolean $expected
     */
    public function testSupports($packageType, $expected)
    {
        $installer = new Installer($this->io, $this->composer);

        $this->assertEquals($expected, $installer->supports($packageType));
    }

    /**
     * Data provider for testSupports
     *
     * @return array
     */
    public function packageTypeProvider()
    {
        return array(
            array('blesta-plugin', true),
            array('blesta-module', true),
            array('blesta-messenger', true),
            array('blesta-gateway-merchant', true),
            array('blesta-gateway-nonmerchant', true),
            array('blesta-invoice-template', true),
            array('blesta-report', true),
            array('blesta-', false),
            array('blesta', false)
        );
    }
}
