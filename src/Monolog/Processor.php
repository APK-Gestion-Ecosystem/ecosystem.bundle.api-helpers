<?php

namespace Ecosystem\ApiHelpersBundle\Monolog;

use Monolog\LogRecord;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;

#[AutoconfigureTag('monolog.processor', attributes: ['method' => 'process'])]
final class Processor
{
    protected const SOURCE_CLI = 'cli';
    protected const SOURCE_HTTP = 'http';

    #[Required]
    public RequestStack $requestStack;

    private ?string $requestId = null;
    private ?string $requestSource = null;

    public function __construct(private string $build)
    {
    }

    public function process(LogRecord $record): LogRecord
    {
        if ($this->requestId === null) {
            $this->requestId = substr(uniqid(), -8);
            if (php_sapi_name() === 'cli') {
                $this->requestSource = self::SOURCE_CLI;
                $this->requestId = strval(getmypid());
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $this->requestSource = self::SOURCE_HTTP;
            $record->extra['http'] = [
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'user-agent' => $request->headers->get('user-agent'),
                'ip' => $request->getClientIp(),
                'aws-trace-id' => $request->headers->get('X-Amzn-Trace-Id'),
            ];

            $record->extra['get'] = $request->query->all();
        }

        $record->extra['request'] = [
            'id' => $this->requestId,
            'source' => $this->requestSource,
        ];

        $record->extra['build'] = $this->build;

        return $record;
    }
}
