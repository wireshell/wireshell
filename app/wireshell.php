<?php

use Symfony\Component\Console\Application;

use Wireshell\Commands\User\UserCreateCommand;
use Wireshell\Commands\User\UserUpdateCommand;
use Wireshell\Commands\User\UserDeleteCommand;
use Wireshell\Commands\User\UserListCommand;
use Wireshell\Commands\Role\RoleCreateCommand;
use Wireshell\Commands\Role\RoleDeleteCommand;
use Wireshell\Commands\Role\RoleListCommand;
use Wireshell\Commands\Template\TemplateCreateCommand;
use Wireshell\Commands\Template\TemplateFieldsCommand;
use Wireshell\Commands\Template\TemplateDeleteCommand;
use Wireshell\Commands\Template\TemplateListCommand;
use Wireshell\Commands\Field\FieldCreateCommand;
use Wireshell\Commands\Field\FieldCloneCommand;
use Wireshell\Commands\Field\FieldDeleteCommand;
use Wireshell\Commands\Field\FieldTagCommand;
use Wireshell\Commands\Field\FieldTypesCommand;
use Wireshell\Commands\Field\FieldListCommand;
use Wireshell\Commands\Field\FieldEditCommand;
use Wireshell\Commands\Module\ModuleDownloadCommand;
use Wireshell\Commands\Module\ModuleEnableCommand;
use Wireshell\Commands\Module\ModuleDisableCommand;
use Wireshell\Commands\Module\ModuleGenerateCommand;
use Wireshell\Commands\Module\ModuleUpgradeCommand;
use Wireshell\Commands\Common\NewCommand;
use Wireshell\Commands\Common\UpgradeCommand;
use Wireshell\Commands\Common\StatusCommand;
use Wireshell\Commands\Common\ServeCommand;
use Wireshell\Commands\Common\CheatCommand;
use Wireshell\Commands\Backup\BackupCommand;
use Wireshell\Commands\Backup\BackupImagesCommand;
use Wireshell\Commands\Page\PageCreateCommand;
use Wireshell\Commands\Page\PageListCommand;
use Wireshell\Commands\Page\PageDeleteCommand;
use Wireshell\Commands\Page\PageEmptyTrashCommand;
use Wireshell\Commands\Logs\LogTailCommand;
use Wireshell\Commands\Logs\LogListCommand;

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$app = new Application('wireshell - An extendable ProcessWire CLI', '1.0.0');

$app->add(new UserCreateCommand());
$app->add(new UserUpdateCommand());
$app->add(new UserDeleteCommand());
$app->add(new UserListCommand());
$app->add(new RoleCreateCommand());
$app->add(new RoleDeleteCommand());
$app->add(new RoleListCommand());
$app->add(new TemplateCreateCommand());
$app->add(new TemplateFieldsCommand());
$app->add(new TemplateDeleteCommand());
$app->add(new TemplateListCommand());
$app->add(new FieldCreateCommand());
$app->add(new FieldCloneCommand());
$app->add(new FieldDeleteCommand());
$app->add(new FieldTagCommand());
$app->add(new FieldTypesCommand());
$app->add(new FieldListCommand());
$app->add(new FieldEditCommand());
$app->add(new ModuleDownloadCommand());
$app->add(new ModuleEnableCommand());
$app->add(new ModuleDisableCommand());
$app->add(new ModuleGenerateCommand(new GuzzleHttp\Client()));
$app->add(new ModuleUpgradeCommand());
$app->add(new NewCommand());
$app->add(new UpgradeCommand(new \Symfony\Component\Filesystem\Filesystem()));
$app->add(new StatusCommand());
$app->add(new ServeCommand());
$app->add(new CheatCommand());
$app->add(new BackupCommand());
$app->add(new BackupImagesCommand());
$app->add(new PageCreateCommand());
$app->add(new PageListCommand());
$app->add(new PageDeleteCommand());
$app->add(new PageEmptyTrashCommand());
$app->add(new LogTailCommand());
$app->add(new LogListCommand());

$app->run();
