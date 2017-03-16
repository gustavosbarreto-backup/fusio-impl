<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Engine\Connection\PingableInterface;
use Fusio\Engine\Factory;
use Fusio\Engine\Parameters;
use Fusio\Impl\Table;
use PSX\Http\Exception as StatusCode;
use PSX\OpenSsl\OpenSsl;
use PSX\Sql\Condition;

/**
 * Connection
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Connection
{
    const CIPHER_METHOD = 'AES-128-CBC';

    protected $connectionTable;
    protected $connectionFactory;
    protected $secretKey;

    public function __construct(Table\Connection $connectionTable, Factory\Connection $connectionFactory, $secretKey)
    {
        $this->connectionTable   = $connectionTable;
        $this->connectionFactory = $connectionFactory;
        $this->secretKey         = $secretKey;
    }

    public function create($name, $class, $config)
    {
        // check whether connection exists
        $condition  = new Condition();
        $condition->equals('status', Table\Connection::STATUS_ACTIVE);
        $condition->equals('name', $name);

        $connection = $this->connectionTable->getOneBy($condition);

        if (!empty($connection)) {
            throw new StatusCode\BadRequestException('Connection already exists');
        }

        $this->testConnection($class, $config);

        // create connection
        $this->connectionTable->create([
            'status' => Table\Connection::STATUS_ACTIVE,
            'name'   => $name,
            'class'  => $class,
            'config' => self::encryptConfig($config, $this->secretKey),
        ]);
    }

    public function update($connectionId, $name, $class, $config)
    {
        $connection = $this->connectionTable->get($connectionId);

        if (!empty($connection)) {
            if ($connection['status'] == Table\Connection::STATUS_DELETED) {
                throw new StatusCode\GoneException('Connection was deleted');
            }

            $this->testConnection($class, $config);

            $this->connectionTable->update([
                'id'     => $connection->id,
                'name'   => $name,
                'class'  => $class,
                'config' => self::encryptConfig($config, $this->secretKey),
            ]);
        } else {
            throw new StatusCode\NotFoundException('Could not find connection');
        }
    }

    public function delete($connectionId)
    {
        $connection = $this->connectionTable->get($connectionId);

        if (!empty($connection)) {
            if ($connection['status'] == Table\Connection::STATUS_DELETED) {
                throw new StatusCode\GoneException('Connection was deleted');
            }

            $this->connectionTable->update([
                'id'     => $connection->id,
                'status' => Table\Connection::STATUS_DELETED,
            ]);
        } else {
            throw new StatusCode\NotFoundException('Could not find connection');
        }
    }

    protected function testConnection($class, array $config)
    {
        $factory    = $this->connectionFactory->factory($class);
        $connection = $factory->getConnection(new Parameters($config));

        if (!is_object($connection)) {
            throw new StatusCode\BadRequestException('Invalid connection');
        }

        if ($factory instanceof PingableInterface) {
            try {
                $ping = $factory->ping($connection);
            } catch (\Exception $e) {
                throw new StatusCode\BadRequestException($e->getMessage());
            }

            if (!$ping) {
                throw new StatusCode\BadRequestException('Could not connect to remote service');
            }
        }
    }

    public static function encryptConfig($config, $secretKey)
    {
        if (empty($config)) {
            return null;
        }

        $iv   = OpenSsl::randomPseudoBytes(16);
        $data = serialize($config);
        $data = OpenSsl::encrypt($data, self::CIPHER_METHOD, $secretKey, 0, $iv);

        return base64_encode($iv) . '.' . $data;
    }

    public static function decryptConfig($data, $secretKey)
    {
        if (empty($data)) {
            return [];
        }

        $parts = explode('.', $data, 2);
        if (count($parts) == 2) {
            list($iv, $data) = $parts;

            $config = OpenSsl::decrypt($data, self::CIPHER_METHOD, $secretKey, 0, base64_decode($iv));
            $config = unserialize($config);

            return $config;
        } else {
            return [];
        }
    }
}
