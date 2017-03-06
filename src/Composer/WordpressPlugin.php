<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Rocket\System\FileManager;

class WordpressPlugin implements PluginInterface
{
    /** @var IOInterface $io */
    protected $io;
    /** @var Composer $composer */
    protected $composer;

    /** @var  Package $package */
    protected $package;


    /**
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {

        $this->composer = $composer;
        $this->io       = $io;

        /** @var Package $package */
        $package       = $composer->getPackage();
        $this->package = $package;

        if ( class_exists( 'Rocket\System\FileManager' ) )
        {

            $this->handleFiles();
        }
        else
        {
            $this->io->write( "<warning>Extra files have not been installed, you must install 'metabolism/rocket-kernel' package before Wordpress installation.</warning>" );
        }
    }

    /**
     * Creating importants files for next steps
     *
     */
    protected function handleFiles()
    {
        $extras = $this->package->getExtra();


        if ( isset( $extras["file-management"] ) )
        {
            foreach ( $extras['file-management'] as $action => $pkg_names )
            {

                if ( array_key_exists( $this->package->getName(), $pkg_names ) )
                {

                    /** @var FileManager $fm */
                    $fm = FileManager::getInstance( $this->io );

                    if ( method_exists( $fm, $action ) )
                    {

                        try
                        {

                            $fm->$action( $pkg_names[$this->package->getName()], $this->package, $this->io );
                        } catch ( \Exception $e )
                        {

                            $this->io->writeError( "<error>Error: " . $action . " action on " . $this->package->getName() . " : \n" . $e->getMessage() . "</error>" );
                        }
                    }
                    else
                    {

                        $this->io->writeError( "<warning> Skipping extra folder action : " . $action . ", method does not exist.</warning>" );
                    }
                }

            }
        }
    }
}