<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Psr\Http\Message\RequestInterface;

class Request implements ArrayAccess
{
    use Macroable;

    /**
     * The decoded payload for the request.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new request instance.
     *
     * @param  RequestInterface  $request
     * @return void
     */
    public function __construct(
        /**
         * The underlying PSR request.
         */
        protected $request
    ) {}

    /**
     * Get the request method.
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get the URL of the request.
     */
    public function url(): string
    {
        return (string) $this->request->getUri();
    }

    /**
     * Determine if the request has a given header.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function hasHeader($key, $value = null)
    {
        if (is_null($value)) {
            return ! empty($this->request->getHeaders()[$key]);
        }

        $headers = $this->headers();

        if (! Arr::has($headers, $key)) {
            return false;
        }

        $value = is_array($value) ? $value : [$value];

        return array_diff($value, $headers[$key]) === [];
    }

    /**
     * Determine if the request has the given headers.
     *
     * @param  array|string  $headers
     */
    public function hasHeaders($headers): bool
    {
        if (is_string($headers)) {
            $headers = [$headers => null];
        }

        foreach ($headers as $key => $value) {
            if (! $this->hasHeader($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the values for the header with the given name.
     *
     * @param  string  $key
     * @return array
     */
    public function header($key)
    {
        return Arr::get($this->headers(), $key, []);
    }

    /**
     * Get the request headers.
     */
    public function headers(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Get the body of the request.
     */
    public function body(): string
    {
        if ($this->request->getBody()->isSeekable()) {
            $this->request->getBody()->rewind();
        }

        return (string) $this->request->getBody();
    }

    /**
     * Get the request's data (form parameters or JSON).
     *
     * @return array
     */
    public function data()
    {
        if ($this->isForm()) {
            return $this->parameters();
        } elseif ($this->isJson()) {
            return $this->json();
        }

        return $this->data ?? $this->body() ?? [];
    }

    /**
     * Get the request's form parameters.
     *
     * @return array
     */
    protected function parameters()
    {
        if (! $this->data) {
            parse_str($this->body(), $parameters);

            $this->data = $parameters;
        }

        return $this->data;
    }

    /**
     * Get the JSON decoded body of the request.
     *
     * @return array
     */
    protected function json()
    {
        if (! $this->data) {
            $this->data = json_decode($this->body(), true);
        }

        return $this->data;
    }

    /**
     * Determine if the request is simple form data.
     *
     * @return bool
     */
    public function isForm()
    {
        return $this->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Determine if the request is JSON.
     */
    public function isJson(): bool
    {
        return $this->hasHeader('Content-Type') &&
            str_contains((string) $this->header('Content-Type')[0], 'json');
    }

    /**
     * Determine if the request is multipart.
     */
    public function isMultipart(): bool
    {
        return $this->hasHeader('Content-Type') &&
            str_contains((string) $this->header('Content-Type')[0], 'multipart');
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->data()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     *
     * @throws LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     *
     * @throws LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }

    public function queryString(): array
    {
        $queryString = [];
        parse_str($this->request->getUri()->getQuery(), $queryString);

        return $queryString;
    }
}
