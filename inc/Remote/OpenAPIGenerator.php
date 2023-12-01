<?php


namespace dokuwiki\Remote;

class OpenAPIGenerator
{

    protected $api;

    protected $documentation = [];

    public function __construct()
    {
        $this->api = new Api();

        $this->documentation['openapi'] = '3.1.0';
        $this->documentation['info'] = [
            'title' => 'DokuWiki API',
            'description' => 'The DokuWiki API OpenAPI specification',
            'version' => ((string)ApiCore::API_VERSION),
        ];

    }

    public function generate()
    {
        $this->addServers();
        $this->addSecurity();
        $this->addMethods();

        return json_encode($this->documentation, JSON_PRETTY_PRINT);
    }

    protected function addServers()
    {
        $this->documentation['servers'] = [
            [
                'url' => DOKU_URL . 'lib/exe/jsonrpc.php',
            ],
        ];
    }

    protected function addSecurity()
    {
        $this->documentation['components']['securitySchemes'] = [
            'basicAuth' => [
                'type' => 'http',
                'scheme' => 'basic',
            ],
            'jwt' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ]
        ];
        $this->documentation['security'] = [
            [
                'basicAuth' => [],
            ],
            [
                'jwt' => [],
            ],
        ];
    }

    protected function addMethods()
    {
        $methods = $this->api->getMethods();

        $this->documentation['paths'] = [];
        foreach ($methods as $method => $call) {
            $this->documentation['paths']['/' . $method] = [
                'post' => $this->getMethodDefinition($method, $call),
            ];
        }
    }

    protected function getMethodDefinition(string $method, ApiCall $call)
    {
        $retType = $this->fixTypes($call->getReturn()['type']);
        $retExample = $this->generateExample('result', $retType);

        return [
            'operationId' => $method,
            'summary' => $call->getSummary(),
            'description' => $call->getDescription(),
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => $this->getMethodArguments($call->getArgs()),
                ]
            ],
            'responses' => [
                200 => [
                    'description' => 'Result',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'result' => [
                                        'type' => $retType,
                                        'description' => $call->getReturn()['description'],
                                        'examples' => [$retExample],
                                    ],
                                    'error' => [
                                        'type' => 'object',
                                        'description' => 'Error object in case of an error',
                                        'properties' => [
                                            'code' => [
                                                'type' => 'integer',
                                                'description' => 'The error code',
                                                'examples' => [0],
                                            ],
                                            'message' => [
                                                'type' => 'string',
                                                'description' => 'The error message',
                                                'examples' => ['Success'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    protected function getMethodArguments($args)
    {
        if (!$args) {
            // even if no arguments are needed, we need to define a body
            // this is to ensure the openapi spec knows that a application/json header is needed
            return ['schema' => ['type' => 'null']];
        }

        $props = [];
        $schema = [
            'schema' => [
                'type' => 'object',
                'properties' => &$props
            ]
        ];

        foreach ($args as $name => $info) {
            $type = $this->fixTypes($info['type']);
            $example = $this->generateExample($name, $type);
            $props[$name] = [
                'type' => $type,
                'description' => $info['description'],
                'examples' => [ $example ],
            ];
        }
        return $schema;
    }

    protected function fixTypes($type)
    {
        switch ($type) {
            case 'int':
                $type = 'integer';
                break;
            case 'bool':
                $type = 'boolean';
                break;
            case 'file':
                $type = 'string';
                break;

        }
        return $type;
    }

    protected function generateExample($name, $type)
    {
        switch ($type) {
            case 'integer':
                return 42;
            case 'boolean':
                return true;
            case 'string':
                return 'some-'.$name;
            case 'array':
                return ['some-'.$name, 'other-'.$name];
            default:
                return new \stdClass();
        }
    }
}
