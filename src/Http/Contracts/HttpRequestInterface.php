<?php

declare(strict_types=1);

namespace Superset\Http\Contracts;

interface HttpRequestInterface
{
    /**
     * @param array<string, mixed>  $query.  Query parameters to pass in the request URL
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    public function get(string $url, array $query = [], array $headers = []): array;

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    public function post(string $url, array $data = [], array $headers = []): array;

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    public function put(string $url, array $data = [], array $headers = []): array;

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    public function patch(string $url, array $data = [], array $headers = []): array;

    /**
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     */
    public function delete(string $url, array $headers = []): array;
}
