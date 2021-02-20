<?php
namespace NeutronStars\Router;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];
    private string $domainUrl;
    private string $pathBase;
    private string $path;

    public function __construct(string $domainUrl, string $path, string $pathBase)
    {
        $this->domainUrl = $domainUrl;
        if (mb_strlen($path) > 1) {
            $path = trim(rtrim($path, '/'));
        }
        $this->pathBase = $pathBase;
        if (strlen($pathBase) > 0) {
            if (strpos($path, $pathBase) === 0) {
                $this->path = substr_replace($path, '', 0, strlen($pathBase));
            } else {
                $this->path = $pathBase . '/404';
            }
        } else {
            $this->path = $path;
        }
        if (strlen($this->path) < 1) {
            $this->path = '/';
        }
    }

    final public function add(string $name, array $route): self
    {
        $route = $this->createRecursive($name, $route);
        $this->routes[$route->getName()] = $route;
        return $this;
    }
    final private function createRecursive($name, array $array): Route
    {
        $controllerInfo = explode('#', $array['controller']);
        $route = new Route($name, $array['path'], $controllerInfo[0], $controllerInfo[1] ?? null,
            $array['params'] ?? [], $array['roles'] ?? [], $array['methods'] ?? []);
        foreach (($array['children'] ?? []) as $key => $value) {
            $route->add($this->createRecursive($key, $value));
        }
        return $route;
    }

    final public function find(&$params): ?Route
    {
        if ($params == null) {
            $params = [];
        }
        foreach ($this->routes as $route) {
            $checkRoute = $route->get($this->path, $params);
            if ($checkRoute != null) {
                return $checkRoute;
            }
        }
        return $this->routes['404'];
    }

    final public function get(string $name, array $params = [], $fullPath = false): string
    {
        $split = explode('.', $name);
        if (!empty($this->routes[$split[0]])) {
            $build = $this->routes[$split[0]]->buildPath(array_slice($split, 1), $params);
            if ($build != null) {
                return ($fullPath ? $this->domainUrl : '').$this->pathBase . $build;
            }
        }
        return ($fullPath ? $this->domainUrl : '').$this->pathBase . '/';
    }

    public function isRoute(string $name, bool $strict = true): bool
    {
        $split = explode('.', $name);
        if (!empty($this->routes[$split[0]])) {
            return $this->routes[$split[0]]->isRoute(array_slice($split, 1), $strict);
        }
        return false;
    }
}
