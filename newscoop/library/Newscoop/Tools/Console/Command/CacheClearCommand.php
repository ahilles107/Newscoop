<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Log maintenance command
 */
class CacheClearCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('cache:clear')
        ->setDescription('Clear Newscoop cache files.');
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();

        $filesystem->remove(__DIR__ . '/../../../../../cache');
        $filesystem->mkdir(__DIR__ . '/../../../../../cache');

        $output->writeln('Cache cleared.');
    }
}