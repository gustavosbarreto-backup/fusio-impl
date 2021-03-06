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

namespace Fusio\Impl\Backend\View\Audit;

use Fusio\Impl\Backend\View\QueryFilterAbstract;

/**
 * QueryFilter
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class QueryFilter extends QueryFilterAbstract
{
    /**
     * @var integer
     */
    protected $appId;

    /**
     * @var integer
     */
    protected $userId;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $message;

    public function getAppId()
    {
        return $this->appId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getCondition($alias = null)
    {
        $condition = parent::getCondition($alias);
        $alias     = $alias !== null ? $alias . '.' : '';

        if (!empty($this->appId)) {
            $condition->equals($alias . 'appId', $this->appId);
        }

        if (!empty($this->userId)) {
            $condition->equals($alias . 'userId', $this->userId);
        }

        if (!empty($this->event)) {
            $condition->like($alias . 'event', '%' . $this->event . '%');
        }

        if (!empty($this->ip)) {
            $condition->like($alias . 'ip', $this->ip);
        }

        if (!empty($this->message)) {
            $condition->like($alias . 'message', '%' . $this->message . '%');
        }

        return $condition;
    }

    public static function create(array $parameters)
    {
        $filter  = parent::create($parameters);
        $appId   = isset($parameters['appId']) ? $parameters['appId'] : null;
        $userId  = isset($parameters['userId']) ? $parameters['userId'] : null;
        $event   = isset($parameters['event']) ? $parameters['event'] : null;
        $ip      = isset($parameters['ip']) ? $parameters['ip'] : null;
        $message = isset($parameters['message']) ? $parameters['message'] : null;
        $search  = isset($parameters['search']) ? $parameters['search'] : null;

        // parse search if available
        if (!empty($search)) {
            $parts = explode(',', $search);
            foreach ($parts as $part) {
                $part = trim($part);
                if (filter_var($part, FILTER_VALIDATE_IP) !== false) {
                    $ip = $part;
                } else {
                    $message = $search;
                }
            }
        }

        $filter->appId   = $appId;
        $filter->userId  = $userId;
        $filter->event   = $event;
        $filter->ip      = $ip;
        $filter->message = $message;

        return $filter;
    }
}
