# lumen-artisan-config-cache

移植Laravel的 `php artisan config:cache` [创建配置缓存文件]指令到Lumen

# Usage

在 **'app/commands/kernel.php'** 中注册指令：

```
protected $commands = [
	\AdamTyn\Lumen\Artisan\ConfigCacheCommand::class
];
```
