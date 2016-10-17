<?php namespace Wireshell\Tests\Commands\Common;

use Symfony\Component\Console\Tester\CommandTester;
use Wireshell\Tests\BaseTestCase as Base;
use Wireshell\Commands\Common\NewCommand;

class NewCommandTest extends Base {

    /**
     * @field array default config values
     */
    protected $defaults = array(
        '--timezone' => 'Europe\Berlin',
        '--httpHosts' => 'pwtest.dev',
        '--username' => 'admin',
        '--userpass' => 'password',
        '--useremail' => 'test@wireshell.pw'
    );

    /**
     * @before
     */
    public function setupCommand() {
        $this->app->add(new NewCommand());
        $this->command = $this->app->find('new');
        $this->tester = new CommandTester($this->command);

        $credentials = array(
            '--dbUser' => $GLOBALS['DB_USER'], 
            '--dbPass' => $GLOBALS['DB_PASSWD'],
            '--dbName' => $GLOBALS['DB_DBNAME']
        );

        $this->extends = array(
            'command'  => $this->command->getName(),
            'directory' => Base::INSTALLATION_FOLDER,
        );

        $this->defaults = array_merge($this->defaults, $credentials, $this->extends);
    }

    public function testDownload() {
        $this->checkInstallation();
        $this->tester->execute(array_merge($this->defaults, array('--no-install' => true)));

        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER);
        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER . '/wire');
        $this->assertDirectoryNotExists(Base::INSTALLATION_FOLDER . '/site');

        // download zip file for later usage
    }

    /**
     * @depends testDownload
     */
    public function testInstall() {
        // check ProcessWire has not been installed yet
        if ($this->fs->exists(Base::INSTALLATION_FOLDER . '/site/config.php')) return;

        var_dump(array_merge($this->defaults, array('--dbPass' => 'wrong')));

        // @todo: check wrong email address, wrong database credentials
        $this->tester->execute(array_merge($this->defaults, array('--useremail' => 'wrong')));
        // $this->tester->execute(array_merge($this->defaults, array('--dbPass' => 'wrong')));
        $output = $this->tester->getDisplay();

        var_dump($output, array_merge($this->defaults, array('--dbPass' => 'wrong')));
        die();

        $this->tester->execute($this->defaults);
        $output = $this->tester->getDisplay();

        $this->assertDirectoryExists(Base::INSTALLATION_FOLDER . '/site');
        $this->assertFileExists(Base::INSTALLATION_FOLDER . '/site/config.php');
        $this->assertContains('Congratulations, ProcessWire has been successfully installed.', $output);
    }

    /**
     * @depends testInstall
     * @expectedException RuntimeException
     */
    public function testIsInstalled() {
        $this->tester->execute($this->defaults);
    }

}
