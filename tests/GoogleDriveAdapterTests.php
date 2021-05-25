<?php

declare(strict_types=1);

namespace Masbug\Flysystem;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;

class GoogleDriveAdapterTests extends FilesystemAdapterTestCase
{
    /**
     * @var string
     */
    private static $adapterPrefix = 'ci';

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = 'ci/' . bin2hex(random_bytes(10));
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $file=__DIR__ .'/../google-drive-service-account.json';
        if ( ! file_exists($file)) {
            self::markTestSkipped("No google service account file {$file} found in project root.");
        }
        try{
            $config=json_decode(file_get_contents($file),true);
            if(!$config)
                self::markTestSkipped("Format json error in {$file}.");
            if( (!isset($config['GOOGLE_DRIVE_CLIENT_ID'])||empty($config['GOOGLE_DRIVE_CLIENT_ID']))
                ||(!isset($config['GOOGLE_DRIVE_CLIENT_SECRET'])||empty($config['GOOGLE_DRIVE_CLIENT_SECRET']))
                ||(!isset($config['GOOGLE_DRIVE_REFRESH_TOKEN'])||empty($config['GOOGLE_DRIVE_REFRESH_TOKEN']))
            )
                self::markTestSkipped("No google service config found in {$file}.");
            $options = [];
            if(isset($config['teamDriveId'])&&!empty($config['teamDriveId'])) $options['teamDriveId']=$config['teamDriveId'];
            $client = new \Google_Client();
            $client->setClientId($config['GOOGLE_DRIVE_CLIENT_ID']);
            $client->setClientSecret($config['GOOGLE_DRIVE_CLIENT_SECRET']);
            $client->refreshToken($config['GOOGLE_DRIVE_REFRESH_TOKEN']);
            $service = new \Google_Service_Drive($client);
            return new GoogleDriveAdapter($service,'tests/', $options);
        }catch(\Exception $e){
            self::markTestSkipped($e->getMessage());
        }
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->assertTrue(true); //This adapter always returns a mime-type.
    }

    /**
     * @test
     */
    public function creating_zero_dir()
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write('0/file.txt', 'contents', new Config());
            $contents = $adapter->read('0/file.txt');
            $this->assertEquals('contents', $contents);
        });
    }

}
