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

namespace Fusio\Impl\Tests\Backend\Api\Import;

use Fusio\Impl\Tests\Fixture;
use PSX\Framework\Test\ControllerDbTestCase;

/**
 * OpenAPITest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class OpenAPITest extends ControllerDbTestCase
{
    public function getDataSet()
    {
        return Fixture::getDataSet();
    }

    public function testDocumentation()
    {
        $response = $this->sendRequest('/doc/*/backend/import/:format', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $actual = (string) $response->getBody();
        $expect = <<<'JSON'
{
    "path": "\/backend\/import\/:format",
    "version": "*",
    "status": 1,
    "description": "",
    "schema": {
        "$schema": "http:\/\/json-schema.org\/draft-04\/schema#",
        "id": "urn:schema.phpsx.org#",
        "definitions": {
            "Schema": {
                "type": "object",
                "title": "schema",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "status": {
                        "type": "integer"
                    },
                    "name": {
                        "type": "string",
                        "pattern": "[a-zA-Z0-9\\-\\_]{3,64}"
                    },
                    "source": {
                        "$ref": "#\/definitions\/Source"
                    }
                }
            },
            "Routes": {
                "type": "object",
                "title": "routes",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "priority": {
                        "type": "integer"
                    },
                    "path": {
                        "type": "string"
                    },
                    "controller": {
                        "type": "string"
                    },
                    "config": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Version"
                        }
                    }
                }
            },
            "Version": {
                "type": "object",
                "title": "version",
                "properties": {
                    "version": {
                        "type": "integer"
                    },
                    "status": {
                        "type": "integer"
                    },
                    "scopes": {
                        "type": "array",
                        "items": {
                            "type": "string"
                        }
                    },
                    "methods": {
                        "$ref": "#\/definitions\/Methods"
                    }
                }
            },
            "Methods": {
                "type": "object",
                "title": "methods",
                "patternProperties": {
                    "^(GET|POST|PUT|PATCH|DELETE)$": {
                        "$ref": "#\/definitions\/Method"
                    }
                }
            },
            "Method": {
                "type": "object",
                "title": "method",
                "properties": {
                    "method": {
                        "type": "string"
                    },
                    "version": {
                        "type": "integer"
                    },
                    "status": {
                        "type": "integer"
                    },
                    "active": {
                        "type": "boolean"
                    },
                    "public": {
                        "type": "boolean"
                    },
                    "parameters": {
                        "type": "string"
                    },
                    "request": {
                        "type": "string"
                    },
                    "response": {
                        "type": "string"
                    },
                    "responses": {
                        "$ref": "#\/definitions\/Responses"
                    },
                    "action": {
                        "type": "string"
                    }
                }
            },
            "Responses": {
                "type": "object",
                "title": "responses",
                "patternProperties": {
                    "^([0-9]{3})$": {
                        "type": "string"
                    }
                }
            },
            "Action": {
                "type": "object",
                "title": "action",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "status": {
                        "type": "integer"
                    },
                    "name": {
                        "type": "string",
                        "pattern": "[a-zA-Z0-9\\-\\_]{3,64}"
                    },
                    "class": {
                        "type": "string"
                    },
                    "engine": {
                        "type": "string"
                    },
                    "config": {
                        "$ref": "#\/definitions\/Config"
                    }
                }
            },
            "Config": {
                "type": "object",
                "title": "config",
                "additionalProperties": {
                    "oneOf": [
                        {
                            "type": "string"
                        },
                        {
                            "type": "number"
                        },
                        {
                            "type": "boolean"
                        },
                        {
                            "type": "null"
                        },
                        {
                            "type": "array",
                            "items": {
                                "oneOf": [
                                    {
                                        "type": "string"
                                    },
                                    {
                                        "type": "number"
                                    },
                                    {
                                        "type": "boolean"
                                    },
                                    {
                                        "type": "null"
                                    }
                                ]
                            },
                            "maxItems": 16
                        }
                    ]
                },
                "maxProperties": 16
            },
            "Source": {
                "type": "object",
                "title": "source",
                "additionalProperties": true
            },
            "Connection": {
                "type": "object",
                "title": "connection",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "name": {
                        "type": "string",
                        "pattern": "[a-zA-Z0-9\\-\\_]{3,64}"
                    },
                    "class": {
                        "type": "string"
                    },
                    "config": {
                        "$ref": "#\/definitions\/Config"
                    }
                }
            },
            "Adapter": {
                "type": "object",
                "title": "adapter",
                "properties": {
                    "actionClass": {
                        "type": "array",
                        "items": {
                            "type": "string"
                        }
                    },
                    "connectionClass": {
                        "type": "array",
                        "items": {
                            "type": "string"
                        }
                    },
                    "routes": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Routes"
                        }
                    },
                    "action": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Action"
                        }
                    },
                    "schema": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Schema"
                        }
                    },
                    "connection": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Connection"
                        }
                    }
                }
            },
            "POST-request": {
                "$ref": "#\/definitions\/Schema"
            },
            "POST-200-response": {
                "$ref": "#\/definitions\/Adapter"
            }
        }
    },
    "methods": {
        "POST": {
            "request": "#\/definitions\/POST-request",
            "responses": {
                "200": "#\/definitions\/POST-200-response"
            }
        }
    },
    "links": [
        {
            "rel": "openapi",
            "href": "\/export\/openapi\/*\/backend\/import\/:format"
        },
        {
            "rel": "swagger",
            "href": "\/export\/swagger\/*\/backend\/import\/:format"
        },
        {
            "rel": "raml",
            "href": "\/export\/raml\/*\/backend\/import\/:format"
        }
    ]
}
JSON;

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    /**
     * @dataProvider providerSpecs
     */
    public function testPost($case)
    {
        $spec   = file_get_contents(__DIR__ . '/resource/' . $case . '.json');
        $expect = file_get_contents(__DIR__ . '/resource/' . $case . '_expect.json');

        $body = json_encode(['schema' => $spec]);

        $response = $this->sendRequest('/backend/import/openapi', 'POST', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf',
            'Content-Type'  => 'application/json',
        ), $body);

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function providerSpecs()
    {
        return [
            ['openapi_case01'],
            ['openapi_case02'],
        ];
    }
}
