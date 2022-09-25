<?php
namespace Blesta\Composer\Installer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use InvalidArgumentException;

class Installer extends LibraryInstaller
{
    protected $supportedTypes = array(
        'blesta' => 'BlestaInstaller'
    );

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $type = $package->getType();
        $supportedType = $this->supportedType($type);

        if ($supportedType === false) {
            throw new InvalidArgumentException(
                'Sorry the package type of this package is not supported.'
            );
        }

        $class = 'Blesta\\Composer\\Installer\\' . $this->supportedTypes[$supportedType];
        $installer = new $class($package, $this->composer, $this->io);

        return $installer->getInstallPath($package, $supportedType);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            throw new InvalidArgumentException(
                sprintf('Package is not installed: %s', $package)
            );
        }

        $repo->removePackage($package);

        $installPath = $this->getInstallPath($package);
        $this->io->write(
            sprintf(
                'Deleting %s - %s',
                $installPath,
                $this->filesystem->removeDirectory($installPath)
                ? '<comment>deleted</comment>'
                : '<error>not deleted</error>'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        $supportedType = $this->supportedType($packageType);

        if ($supportedType === false) {
            return false;
        }

        $class = 'Blesta\\Composer\\Installer\\' . $this->supportedTypes[$supportedType];
        $installer = new $class(null, $this->composer, $this->io);
        $locations = $installer->getLocations();

        foreach ($locations as $type => $path) {
            if ($supportedType . '-' . $type === $packageType) {
                return true;
            }
        }
        return false;
    }

    /**
     * Find the matching installer type
     *
     * @param string $type
     * @return boolean|string
     */
    protected function supportedType($type)
    {
        $supportedType = false;

        $baseType = substr($type, 0, strpos($type, '-'));

        if (array_key_exists($baseType, $this->supportedTypes)) {
            $supportedType = $baseType;
        }

        return $supportedType;
    }
}
