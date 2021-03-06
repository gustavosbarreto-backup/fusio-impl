<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Service;

use Fusio\Impl\Authorization\UserContext;
use Fusio\Impl\Controller\SchemaApiController;
use Fusio\Impl\Event\Routes\CreatedEvent;
use Fusio\Impl\Event\Routes\DeletedEvent;
use Fusio\Impl\Event\Routes\UpdatedEvent;
use Fusio\Impl\Event\RoutesEvents;
use Fusio\Impl\Service;
use Fusio\Impl\Table;
use PSX\Http\Exception as StatusCode;
use PSX\Sql\Condition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Routes
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Routes
{
    /**
     * @var \Fusio\Impl\Table\Routes
     */
    protected $routesTable;

    /**
     * @var \Fusio\Impl\Table\Routes\Method
     */
    protected $methodTable;

    /**
     * @var \Fusio\Impl\Service\Routes\Config
     */
    protected $configService;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param \Fusio\Impl\Table\Routes $routesTable
     * @param \Fusio\Impl\Table\Routes\Method $methodTable
     * @param \Fusio\Impl\Service\Routes\Config $configService
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Table\Routes $routesTable, Table\Routes\Method $methodTable, Service\Routes\Config $configService, EventDispatcherInterface $eventDispatcher)
    {
        $this->routesTable     = $routesTable;
        $this->methodTable     = $methodTable;
        $this->configService   = $configService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function create($priority, $path, $controller, $config, UserContext $context)
    {
        // check whether route exists
        $condition  = new Condition();
        $condition->equals('status', Table\Routes::STATUS_ACTIVE);
        $condition->equals('path', $path);

        $route = $this->routesTable->getOneBy($condition);

        if (!empty($route)) {
            throw new StatusCode\BadRequestException('Route already exists');
        }

        if ($priority === null) {
            $priority = $this->routesTable->getMaxPriority() + 1;
        } elseif ($priority >= 0x1000000) {
            throw new StatusCode\GoneException('Priority can not be greater or equal to ' . 0x1000000);
        }

        if (!empty($controller)) {
            if (!class_exists($controller)) {
                throw new StatusCode\BadRequestException('Provided controller does not exist');
            }
        } else {
            $controller = SchemaApiController::class;
        }

        try {
            $this->routesTable->beginTransaction();

            // create route
            $record = [
                'status'     => Table\Routes::STATUS_ACTIVE,
                'priority'   => $priority,
                'methods'    => 'ANY',
                'path'       => $path,
                'controller' => $controller,
            ];

            $this->routesTable->create($record);

            // get last insert id
            $routeId = $this->routesTable->getLastInsertId();

            $this->configService->handleConfig($routeId, $path, $config, $context);

            $this->routesTable->commit();
        } catch (\Throwable $e) {
            $this->routesTable->rollBack();

            throw $e;
        }

        $this->eventDispatcher->dispatch(RoutesEvents::CREATE, new CreatedEvent($routeId, $record, $config, $context));
    }

    public function update($routeId, $priority, $config, UserContext $context)
    {
        $route = $this->routesTable->get($routeId);

        if (empty($route)) {
            throw new StatusCode\NotFoundException('Could not find route');
        }

        if ($route['status'] == Table\Routes::STATUS_DELETED) {
            throw new StatusCode\GoneException('Route was deleted');
        }

        if ($priority === null) {
            $priority = $this->routesTable->getMaxPriority() + 1;
        } elseif ($priority >= 0x1000000) {
            throw new StatusCode\GoneException('Priority can not be greater or equal to ' . 0x1000000);
        }

        try {
            $this->routesTable->beginTransaction();

            // update route
            $record = [
                'id'       => $route->id,
                'priority' => $priority,
            ];

            $this->routesTable->update($record);

            $this->configService->handleConfig($route->id, $route->path, $config, $context);

            $this->routesTable->commit();
        } catch (\Throwable $e) {
            $this->routesTable->rollBack();

            throw $e;
        }

        $this->eventDispatcher->dispatch(RoutesEvents::UPDATE, new UpdatedEvent($routeId, [], $config, $route, $context));
    }

    public function delete($routeId, UserContext $context)
    {
        $route = $this->routesTable->get($routeId);

        if (empty($route)) {
            throw new StatusCode\NotFoundException('Could not find route');
        }

        if ($route['status'] == Table\Routes::STATUS_DELETED) {
            throw new StatusCode\GoneException('Route was deleted');
        }

        // check whether route has a production version
        if ($this->methodTable->hasProductionVersion($route->id)) {
            throw new StatusCode\ConflictException('It is not possible to delete a route which contains a active production or deprecated method');
        }

        // delete route
        $record = [
            'id'     => $route->id,
            'status' => Table\Routes::STATUS_DELETED
        ];

        $this->routesTable->update($record);

        $this->eventDispatcher->dispatch(RoutesEvents::DELETE, new DeletedEvent($routeId, $route, $context));
    }
}
