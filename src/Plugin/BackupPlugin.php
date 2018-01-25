<?php

namespace Rocket\Plugin;

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class Rocket Framework
 */
class BackupPlugin {

	protected $config;

	private function archive($source, $destination, $exclude = [])
	{
		if ( !extension_loaded( 'zip' ) )
			return 'Zip Extension is not loaded';

		if ( is_string( $source ) )
			$source_arr = [$source];
		else
			$source_arr = $source;

		$exclude = array_merge( $exclude, ['.', '..'] );
		$zip     = new \ZipArchive();

		if ( !$zip->open( $destination, \ZipArchive::CREATE ) )
			return 'Can\'t create archive file';

		foreach ( $source_arr as $source ) {

			$source = str_replace( '\\', '/', realpath( $source ) );
			$folder = "";

			if ( count( $source_arr ) > 1 ) {

				$folder = substr( $source, strrpos( $source, '/' ) + 1 ) . '/';
				$zip->addEmptyDir( $folder );
			}

			if ( is_dir( $source ) === true ) {

				$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source ), \RecursiveIteratorIterator::SELF_FIRST );

				foreach ( $files as $file ) {

					$file = str_replace( '\\', '/', $file );

					// Ignore "." and ".." folders
					if ( in_array( substr( $file, strrpos( $file, '/' ) + 1 ), $exclude ) ) {
						continue;
					}

					$file = realpath( $file );

					if ( is_dir( $file ) === true ) {

						$zip->addEmptyDir( $folder . str_replace( $source . '/', '', $file . '/' ) );
					}
					else {
						if ( is_file( $file ) === true ) {

							$zip->addFile( $file, $folder . str_replace( $source . '/', '', $file ) );
						}
					}
				}
			}
			else {

				if ( is_file( $source ) === true ) {

					$zip->addFile( $source, $folder . basename( $source ) );
				}
			}
		}

		return $zip->close();
	}


	/**
	 * Remove all thumbnails
	 */
	private function dumpDatabase($file)
	{
		try {

			$dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
			$dump->start($file);

			return true;
		}
		catch (\Exception $e)
		{
			return 'mysqldump-php error: ' . $e->getMessage();
		}
	}

	/**
	 * Remove all thumbnails
	 */
	private function download($all=false)
	{
		if ( current_user_can('administrator') && (!$all || is_super_admin()) )
		{
			$rootPath = BASE_URI. '/src/WordpressBundle/uploads/';
			$tmpPath = BASE_URI. '/src/WordpressBundle/uploads/tmp/';

			mkdir($tmpPath, 077);

			$this->dumpDatabase($tmpPath.'bdd.sql');
			$this->archive($rootPath, $tmpPath.'backup.zip');

			unlink($tmpPath.'bdd.sql');
		}

		wp_redirect( get_admin_url(null, $all?'network/settings.php':'options-general.php') );
	}


	/**
	 * add network parameters
	 */
	public function wpmuOptions()
	{
		echo '<h2>Backup</h2>';
		echo '<table id="backup" class="form-table">
			<tbody><tr>
				<th scope="row">'.__('Download backup').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?download_mu_backup">Download</a></td>
			</tr>
		</tbody></table>';
	}


	/**
	 * add admin parameters
	 */
	public function adminInit()
	{
		if( isset($_GET['download_backup']) )
			$this->download();

		if( isset($_GET['download_mu_backup']) )
			$this->download(true);

		// Remove generated thumbnails option
		add_settings_field('download_backup', __('Backup'), function(){

			echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup">'.__('Download').'</a>';

		}, 'general');

	}
	
	public function __construct($config)
	{
		$this->config = $config;

		add_action( 'init', function()
		{
			if( is_admin() )
			{
				add_action( 'admin_init', [$this, 'adminInit'] );
				add_action( 'wpmu_options', [$this, 'wpmuOptions'] );
			}
		});
	}
}
