<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use Throwable;

class ConfigCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建配置缓存文件';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
     *
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function handle()
    {
        $this->call('config:clear');

        $config = $this->getFreshConfiguration();

        $configPath = $this->getCachedConfigPath();

        $this->files->put(
            $configPath, '<?php return ' . var_export($config, true) . ';' . PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->info('Configuration cached successfully!');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        $app = require $this->bootstrapPath(). DIRECTORY_SEPARATOR. 'app.php';

        $app->useStoragePath($this->laravel->storagePath());

        $config = [];

        foreach ($this->loadConfigurationFiles($this->baseConfigurationPath()) as $name => $path) {
            $config[$name] = require $path;
        }

        foreach ($this->loadConfigurationFiles($this->frameworkConfigurationPath()) as $name => $path) {
            if (isset($config[$name])) {
                continue;
            }
            $config[$name] = require $path;
        }

        return $config;
    }

    private function getCachedConfigPath()
    {
        return $this->laravel->basePath('bootstrap/cache/config.php');
    }

    private function baseConfigurationPath()
    {
        return $this->laravel->basePath('config');
    }

    private function bootstrapPath()
    {
        return $this->laravel->basePath('bootstrap');
    }

    private function frameworkConfigurationPath()
    {
        $divide = DIRECTORY_SEPARATOR;
        $path = __DIR__;
        $temp = explode($divide, $path);
        $vendorPath = '';
        $count = count($temp);

        for ($i = 0; $i < $count - 3; ++$i) {
            $vendorPath .= ($temp[$i] . $divide);
        }

        return $vendorPath . 'laravel/lumen-framework/config';
    }

    private function loadConfigurationFiles($dir)
    {
        if (is_dir($dir)) {
            $find = '.php';

            foreach (scandir($dir) as $item) {
                if (strpos($item, $find) !== false) {
                    $file = $dir . DIRECTORY_SEPARATOR . $item;

                    $name = str_replace($find, '', $item);

                    yield $name => $file;
                }
            }
        }
    }
}
