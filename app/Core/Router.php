<?php
declare(strict_types=1);

namespace App\Core;

final class Router {
  private array $routes = [];

  public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
  public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }

  private function add(string $method, string $path, callable $handler): void {
    $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
    $pattern = '#^' . $pattern . '$#';
    $this->routes[] = [$method, $pattern, $handler];
  }

  public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    foreach ($this->routes as [$m, $pattern, $handler]) {
      if ($m !== $method) continue;
      if (preg_match($pattern, $path, $matches)) {
        $params = [];
        foreach ($matches as $k => $v) if (!is_int($k)) $params[$k] = $v;
        $handler($params);
        return;
      }
    }
    http_response_code(404);
    echo "<h1>404</h1><p>Not found</p>";
  }
}
