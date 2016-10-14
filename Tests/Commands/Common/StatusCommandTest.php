<?php namespace Wireshell\Test\Commands\Common;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Wireshell\Commands\Common\StatusCommand;

class StatusCommandTest extends \PHPUnit_Framework_TestCase {

    public function testNotEmptyOutput() {
        $app = new Application();
        $app->add(new StatusCommand());
        $command = $app->find('status');
        $commandTester = new CommandTester($command);

        // @todo: cwd ~/Projects/wireshell change inside woring project directory for now
        // working project: `phpunit -c ~/.composer/vendor/wireshell/wireshell`
        // sqlite?

        $commandTester->execute(array(
            'command'  => $command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertContains('Version', $output);
        $this->assertContains('ProcessWire', $output);
    }
}
