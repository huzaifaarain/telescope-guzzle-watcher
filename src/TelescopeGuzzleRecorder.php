<?php

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class TelescopeGuzzleRecorder
{
    private TransferStats $transferStats;

    private Request $request;

    private ?Response $response;

    public function __construct(TransferStats $transferStats)
    {
        $this->transferStats = $transferStats;
        $this->request = new Request($transferStats->getRequest());

        $transferStatsResponse = $transferStats->getResponse();
        $this->response = $transferStatsResponse ? new Response($transferStatsResponse) : null;
    }

    public static function recordGuzzleRequest(TransferStats $transferStats)
    {
        $instance = new static($transferStats);
        $instance->recordRequest();
    }

    private function recordRequest()
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $entry = IncomingEntry::make([
            'method' => $this->request->method(),
            'uri' => strtok($this->request->url(), '?'),
            'headers' => $this->headers($this->request->headers()),
            'payload' => array_merge(
                ['query_string' => $this->request->queryString()],
                ['payload' => $this->payload($this->input($this->request))]
            ),
            'response_status' => $this->response ? $this->response->status() : null,
            'response_headers' => $this->response ? $this->headers($this->response->headers()) : null,
            'response' => $this->response ? $this->response($this->response) : null,
            'duration' => $this->duration(),
        ]);

        if (config('telescope-guzzle-watcher.enable_uri_tags') === true) {
            $entry->tags($this->extractTagsFromUri());
        }

        Telescope::recordClientRequest($entry);
    }

    /**
     * Determine if the content is within the set limits.
     *
     * @param  string  $content
     * @return bool
     */
    public function contentWithinLimits($content)
    {
        $limit = config('telescope-guzzle-watcher.size_limit') ?? 64;

        return mb_strlen($content) / 1000 <= $limit;
    }

    /**
     * Format the given response object.
     *
     * @return array|string
     */
    protected function response(Response $response)
    {
        $content = $response->body();

        $stream = $this->transferStats->getResponse()->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (is_string($content)) {
            if (
                is_array(json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE
            ) {
                return $this->contentWithinLimits($content)
                    ? $this->hideParameters(json_decode($content, true), Telescope::$hiddenResponseParameters)
                    : 'Purged By Telescope Guzzle Watcher';
            }

            if (Str::startsWith(strtolower($response->header('Content-Type') ?? ''), 'text/plain')) {
                return $this->contentWithinLimits($content) ? $content : 'Purged By Telescope Guzzle Watcher';
            }
        }

        if ($response->redirect()) {
            return 'Redirected to '.$response->header('Location');
        }

        if (empty($content)) {
            return 'Empty Response';
        }

        return 'HTML Response';
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headerNames = collect($headers)->keys()->map(function ($headerName) {
            return strtolower($headerName);
        })->toArray();

        $headerValues = collect($headers)->map(function ($value) {
            return $value[0];
        })->toArray();

        $headers = array_combine($headerNames, $headerValues);

        $hidableParams = array_merge(
            config('telescope-guzzle-watcher.except_request_headers', []),
            config('telescope-guzzle-watcher.except_response_headers', [])
        );

        return $this->hideParameters(
            $headers,
            $hidableParams
        );
    }

    /**
     * Format the given payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function payload($payload)
    {
        return $this->hideParameters(
            $payload,
            Telescope::$hiddenRequestParameters
        );
    }

    /**
     * Hide the given parameters.
     *
     * @param  array  $data
     * @param  array  $hidden
     * @return mixed
     */
    protected function hideParameters($data, $hidden)
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }

    /**
     * Extract the input from the given request.
     *
     * @return array
     */
    protected function input(Request $request)
    {
        if (! $request->isMultipart()) {
            return $request->data();
        }

        return collect(preg_split("/--.*\r\n/", $request->data()))
            ->filter()
            ->values()
            ->mapWithKeys(function ($content): array {
                $contentArray = collect(preg_split("/\r\n/", $content))
                    ->filter()
                    ->values();

                $key = $contentArray->firstWhere(fn ($content) => str_contains($content, 'name='));

                if ($hasContentType = $contentArray->search(fn ($contentItem) => str_contains($contentItem, 'Content-Type'))) {
                    $contentArray = $contentArray->filter(fn ($contentItem, $index) => $index <= $hasContentType);
                }

                $contentName = str($key)->match("/name\=[\',\"](\w*)[\',\"]/")->toString();

                return [$contentName => $contentArray];
            })->toArray();
    }

    /**
     * Extract the query string from the given request url
     *
     * @return array
     */
    protected function queryString(Request $request)
    {
        $queryString = [];
        parse_str($request->url(), $queryString);

        return $queryString;
    }

    private function extractTagsFromUri()
    {
        $uri = $this->request->url();
        $parsedURI = parse_url($uri);
        $tags = [$parsedURI['host']];
        if (array_key_exists('path', $parsedURI)) {
            $pathArr = array_filter(explode('/', $parsedURI['path']));
            $tags = array_merge($tags, $pathArr);
        }

        $exceptTags = config('telescope-guzzle-watcher.exclude_words_from_uri_tags');
        if (count($exceptTags) > 0) {
            $tags = Arr::where($tags, function ($tag) use ($exceptTags) {
                return ! in_array($tag, $exceptTags);
            });
        }

        return $tags;
    }

    /**
     * Get the request duration in milliseconds.
     *
     * @return int|null
     */
    private function duration()
    {
        if ($this->transferStats && $this->transferStats->getTransferTime()) {
            return floor($this->transferStats->getTransferTime() * 1000);
        }
    }
}
