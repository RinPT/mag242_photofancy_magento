<?php

namespace Photofancy\Theme\Console\Command;

use Magento\Deploy\Model\Filesystem as Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{

    /**
     * GenerateLessCommand constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Filesystem $filesystem
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Filesystem $filesystem
    ) {
        $this->objectManager = $objectManager;
        $this->filesystem = $filesystem;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('photofancy:test')
            ->setDescription('Photofancy Test Command');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Photofancy Test started:');
        $output->writeln('...');
        $output->writeln('Photofancy Test finished.');

    }
}
