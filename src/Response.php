<?php

declare(strict_types=1);

namespace MuhammadHuzaifa\TelescopeGuzzleWatcher;

use ArrayAccess;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

class Response implements ArrayAccess, Stringable
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected $decoded;

    /**
     * The request cookies.
     *
     * @var CookieJar
     */
    public $cookies;

    /**
     * The transfer stats for the request.
     *
     * @var TransferStats|null
     */
    public $transferStats;

    /**
     * Create a new response instance.
     *
     * @param  MessageInterface  $response
     * @return void
     */
    public function __construct(
        /**
         * The underlying PSR response.
         */
        protected $response
    ) {}

    /**
     * Get the body of the response.
     */
    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array or scalar value.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function json($key = null, $default = null)
    {
        if (! $this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        return data_get($this->decoded, $key, $default);
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object|array
     */
    public function object(): mixed
    {
        return json_decode($this->body(), false);
    }

    /**
     * Get the JSON decoded body of the response as a collection.
     *
     * @param  string|null  $key
     * @return Collection
     */
    public function collect($key = null)
    {
        return Collection::make($this->json($key));
    }

    /**
     * Get a header from the response.
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the status code of the response.
     */
    public function status(): int
    {
        return (int) $this->response->getStatusCode();
    }

    /**
     * Get the reason phrase of the response.
     */
    public function reason(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Get the effective URI of the response.
     *
     * @return UriInterface|null
     */
    public function effectiveUri()
    {
        return $this->transferStats?->getEffectiveUri();
    }

    /**
     * Determine if the response was a redirect.
     */
    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Get the response cookies.
     *
     * @return CookieJar
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->json()[$offset];
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
        throw new LogicException('Response data may not be mutated using array access.');
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
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Get the body of the response.
     */
    public function __toString(): string
    {
        return $this->body();
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return static::hasMacro($method)
            ? $this->macroCall($method, $parameters)
            : $this->response->{$method}(...$parameters);
    }
}
