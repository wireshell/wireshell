<?php namespace Wireshell\Tests\Commands\Common;

use Symfony\Component\Console\Tester\CommandTester;
use Wireshell\Tests\BaseTestCase as Base;
use Wireshell\Commands\Common\StatusCommand;

class StatusCommandTest extends Base {

    /**
     * @before
     */
    public function setupCommand() {
        $this->app->add(new StatusCommand());
        $this->command = $this->app->find('status');
        $this->tester = new CommandTester($this->command);
    }

    public function testNotEmptyOutput() {
        $this->tester->execute(array(
            'command'  => $this->command->getName()
        ));

        $output = $this->tester->getDisplay();
        $this->assertContains('Version', $output);
        $this->assertContains('ProcessWire', $output);
        $this->assertContains('*****', $output);
    }

    public function testImageDiagnostic() {
        $this->tester->execute(array(
            'command'  => $this->command->getName(),
            '--image' => true
        ));

        $output = $this->tester->getDisplay();
        $this->assertContains('Image Diagnostics', $output);
    }

    public function testPhpDiagnostic() {
        $this->tester->execute(array(
            'command'  => $this->command->getName(),
            '--php' => true
        ));

        $output = $this->tester->getDisplay();
        $this->assertContains('PHP Diagnostics', $output);
    }

    public function testDisplayPass() {
        $this->tester->execute(array(
            'command'  => $this->command->getName(),
            '--pass' => true
        ));

        $output = $this->tester->getDisplay();
        $this->assertNotContains('*****', $output);
    }
}
