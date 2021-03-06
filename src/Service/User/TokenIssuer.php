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

namespace Fusio\Impl\Service\User;

use Firebase\JWT\JWT;
use Fusio\Impl\Service;
use Fusio\Impl\Table;
use PSX\Framework\Config\Config;
use PSX\Http\Exception as StatusCode;

/**
 * TokenIssuer
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class TokenIssuer
{
    /**
     * @var \Fusio\Impl\Service\App
     */
    protected $appService;

    /**
     * @var \Fusio\Impl\Table\User
     */
    protected $userTable;

    /**
     * @var \PSX\Framework\Config\Config
     */
    protected $config;

    /**
     * @param \Fusio\Impl\Service\App $appService
     * @param \Fusio\Impl\Table\User $userTable
     * @param \PSX\Framework\Config\Config $config
     */
    public function __construct(Service\App $appService, Table\User $userTable, Config $config)
    {
        $this->appService = $appService;
        $this->userTable  = $userTable;
        $this->config     = $config;
    }

    public function createToken($userId, array $scopes)
    {
        $user = $this->userTable->get($userId);

        if (empty($user)) {
            throw new StatusCode\BadRequestException('Invalid user');
        }

        // @TODO this is the consumer app. Probably we need a better way to
        // define this id
        $appId = 2;

        $token = $this->appService->generateAccessToken(
            $appId,
            $userId,
            $scopes,
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
            new \DateInterval($this->config->get('fusio_expire_consumer'))
        );

        $payload = [
            'sub'  => $token->getAccessToken(),
            'iat'  => time(),
            'exp'  => $token->getExpiresIn(),
            'name' => $user['name']
        ];

        return JWT::encode($payload, $this->config->get('fusio_project_key'));
    }
}
