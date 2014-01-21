<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Tools\Console\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create oauth client
 */
class LoadDamoDataFixturesCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('newscoop:load-demo-data')
            ->setDescription('Load demo data fixtures.');
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $manager = $container->get('h4cc_alice_fixtures.manager');

        $set = $manager->createFixtureSet();
        $set->addFile(__DIR__.'/../../../../../install/Resources/sample_data/fixtures/Comments.yml', 'yaml');
        $set->setLocale('pl_PL');
        $set->setSeed(42);
        $set->setDoPersist(true);
        $set->setDoDrop(false);

        $manager->load($set);
    }
}
