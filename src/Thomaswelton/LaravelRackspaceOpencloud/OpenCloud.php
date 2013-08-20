<?php namespace Thomaswelton\LaravelRackspaceOpencloud;

use \Config;
use \File;

class OpenCloud extends \OpenCloud\Rackspace{

	public $region = null;

	function __construct(){

		$this->region = Config::get('laravel-rackspace-opencloud::region');
		$authUrl = ($this->region == 'LON') ? 'https://lon.identity.api.rackspacecloud.com/v2.0/' : 'https://identity.api.rackspacecloud.com/v2.0/';

		parent::__construct($authUrl, array(
			'username' => Config::get('laravel-rackspace-opencloud::username'),
			'apiKey' => Config::get('laravel-rackspace-opencloud::apiKey')
		));
	}

	public function getObjectStore(){
		return $this->ObjectStore('cloudFiles', $this->region);
	}

	public function getContainer($name){
		// create a new container
		$container = $this->getObjectStore()->Container();
		$container->Create(array('name' => $name ));

		// publish it to the CDN
		$container->PublishToCDN();

		return $container;
	}

	public function upload($container, $file, $name = null)
	{
		if(is_object($file) && get_class($file) == 'Symfony\Component\HttpFoundation\File\UploadedFile'){
			// Passed with was a file upload from a form. Used the PHP tmp name
			// and guess an extension
			if(is_null($name)){
				$name = basename($file) . '.' . $file->guessExtension();
			}

			return $this->createDataObject($container, $file->getRealPath(), $name);

		}else if(File::isFile($file)){
			// Passed file was a string to the file path
			return $this->createDataObject($container, $file, $name);

		}else{
			throw new Exception("OpenCloud::upload file not found", 1);
		}
	}

	public function createDataObject($container, $filePath, $fileName = null)
	{
		if(is_null($fileName)){
			$fileName = basename($filePath);
		}

		$container = $this->getContainer($container);

		$object = $container->DataObject();
		$object->Create(array('name'=> $fileName), $filePath);

		return $object;
	}
}

