<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Impl\Console;

use Doctrine\DBAL\Connection;
use Fusio\Impl\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SystemImportCommand
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class SystemImportCommand extends Command
{
    protected $importService;
    protected $connection;

    public function __construct(Service\System\Import $importService, Connection $connection)
    {
        parent::__construct();

        $this->importService = $importService;
        $this->connection    = $connection;
    }

    protected function configure()
    {
        $this
            ->setName('system:import')
            ->setDescription('Import system data from a JSON structure')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');

        if (!is_file($file)) {
            throw new RuntimeException('File does not exists');
        }

        try {
            $this->connection->beginTransaction();

            $result = $this->importService->import(file_get_contents($file));

            $this->connection->commit();

            $output->writeln('Import successful!');
            $output->writeln('The following actions were done:');
            $output->writeln('');

            foreach ($result as $message) {
                $output->writeln('- ' . $message);
            }
        } catch (\Exception $e) {
            $this->connection->rollback();

            $output->writeln('An exception occured during import. No changes are applied to the database.');
            $output->writeln('');
            $output->writeln('Message: ' . $e->getMessage());
            $output->writeln('Trace: ' . $e->getTraceAsString());
        }
    }
}