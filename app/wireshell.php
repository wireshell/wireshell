<?php

use Symfony\Component\Console\Application;

use Wireshell\Commands\User\UserCreateCommand;
use Wireshell\Commands\User\UserUpdateCommand;
use Wireshell\Commands\User\UserDeleteCommand;
use Wireshell\Commands\User\UserListCommand;
use Wireshell\Commands\Role\RoleCreateCommand;
use Wireshell\Commands\Template\TemplateCreateCommand;
use Wireshell\Commands\Template\TemplateFieldsCommand;
use Wireshell\Commands\Field\FieldCreateCommand;
use Wireshell\Commands\Module\ModuleDownloadCommand;
use Wireshell\Commands\Module\ModuleEnableCommand;
use Wireshell\Commands\Module\ModuleDisableCommand;
use Wireshell\Commands\Module\ModuleGenerateCommand;
use Wireshell\Commands\Common\NewCommand;
use Wireshell\Commands\Common\UpgradeCommand;
use Wireshell\Commands\Common\StatusCommand;
use Wireshell\Commands\Common\ServeCommand;
use Wireshell\Commands\Backup\BackupCommand;

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$app = new Application('wireshell - An extendable ProcessWire CLI', '0.4.1');

$app->add(new UserCreateCommand());
$app->add(new UserUpdateCommand());
$app->add(new UserDeleteCommand());
$app->add(new UserListCommand());
$app->add(new RoleCreateCommand());
$app->add(new TemplateCreateCommand());
$app->add(new TemplateFieldsCommand());
$app->add(new FieldCreateCommand());
$app->add(new ModuleDownloadCommand());
$app->add(new ModuleEnableCommand());
$app->add(new ModuleDisableCommand());
$app->add(new ModuleGenerateCommand(new GuzzleHttp\Client()));
$app->add(new NewCommand());
$app->add(new UpgradeCommand());
$app->add(new StatusCommand());
$app->add(new ServeCommand());
$app->add(new BackupCommand());

$app->run();
