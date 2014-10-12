<?php namespace Thomaswelton\LaravelRackspaceOpencloud\Commands;

use \File;
use \Str;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CdnSyncCommand extends Command {

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'deploy:assets';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sync assets to Rackspace CDN';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Confirm before proceeding with the action
   *
   * @return bool
   */
  public function confirmToProceed()
  {
    if ($this->getLaravel()->environment() == 'production')
    {
      if ($this->option('force')) return true;

      $this->comment('**************************************');
      $this->comment('*     Application In Production!     *');
      $this->comment('**************************************');
      $this->output->writeln('');

      $confirmed = $this->confirm('Do you really wish to run this command?');

      if ( ! $confirmed)
      {
          $this->comment('Command Cancelled!');

          return false;
      }
    }

    return true;
  }

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function fire()
    {
        if ( ! $this->confirmToProceed()) return;

        $opencloud = \App::make('open-cloud');
        $container_name = \Config::get('rackspace.container');
        $container = $opencloud->getContainer($container_name);

        // Get directory or file path
        // $path = base_path() . '/' . $this->argument('path');
        $path = base_path() . '/app/assets';
        $path_trim = base_path() . '/' . $this->option('trim');

        $this->info('Syncing app/assets to CDN container: ' . $container_name);

        // Exit if not exists
        if(!File::isDirectory($path)){
            return $this->error('Path is not a directory');
        }

        $files = File::allFiles($path);

        // Get an md5 of a concatenated md5_file hash of all files
        $directoryHash = md5(array_reduce($files, function($hash, $file){
            // Do not include .cdn.json files in the directory hash
            if(substr($file, -9) == '.cdn.json'){
                return $hash;
            }

            $hash .= md5_file($file);
            return $hash;
        }));

        $fileCount = count($files);
        $this->info('Found ' . $fileCount . ' ' . Str::plural('file', $fileCount));
        $this->info("Uploading...");

        $cdnFile = $opencloud->uploadDir($container_name, $path, $directoryHash, $path_trim);

        $this->info("");
        $this->info("Extracting...");
        $this->info("Asset sync to CDN container: {$container_name} is complete.");
    }

  /**
   * Get the console command arguments.
   *
   * @return array
   */
  protected function getArguments()
  {
    return array(
      // array('path', InputArgument::REQUIRED, 'File or directory path'),
    );
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    return array(
      array('trim', '', InputOption::VALUE_OPTIONAL, 'String to trim from directory when uploading', null),
      array('force', '', InputOption::VALUE_OPTIONAL, 'Ignore env warning before uploading', null),
    );
  }

}

