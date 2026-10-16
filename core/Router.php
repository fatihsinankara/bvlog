<?php

namespace Core;

class Router
{
    private static array $routes = [];

    public static function get(string $pattern, array|callable $handler, array $middlewares = []): void
    {
        self::add('GET', $pattern, $handler, $middlewares);
    }

    public static function post(string $pattern, array|callable $handler, array $middlewares = []): void
    {
        self::add('POST', $pattern, $handler, $middlewares);
    }

    public static function add(string $method, string $pattern, array|callable $handler, array $middlewares = []): void
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern === '') {
            $pattern = '/';
        }

        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        self::$routes[] = [
            'method'      => strtoupper($method),
            'pattern'     => $pattern,
            'regex'       => $regex,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();

        // POST isteklerinde global CSRF kontrolü
        if ($method === 'POST') {
            if (!Csrf::verify()) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => 'Güvenlik doğrulaması (CSRF) başarısız oldu. Sayfayı yenileyin.'], 403);
                }
                Session::flash('error', 'Güvenlik anahtarı (CSRF) süresi doldu veya geçersiz. Lütfen tekrar deneyin.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                Response::redirect($referer);
            }
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                // Middleware kontrolleri
                foreach ($route['middlewares'] as $mw) {
                    self::runMiddleware($mw);
                }

                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[] = $val;
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $action], $params);
                    return;
                }

                call_user_func_array($handler, $params);
                return;
            }
        }

        self::abort(404);
    }

    private static function runMiddleware(string $middleware): void
    {
        if ($middleware === 'auth') {
            if (!Session::has('admin_user_id')) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => 'Oturum açmanız gerekiyor.'], 401);
                }
                Response::redirect('/admin/login');
            }
        } elseif ($middleware === 'guest') {
            if (Session::has('admin_user_id')) {
                Response::redirect('/admin');
            }
        } elseif (str_starts_with($middleware, 'module:')) {
            $mod = substr($middleware, 7);
            if (!is_module_active($mod)) {
                self::abort(404);
            }
        }
    }

    public static function abort(int $code = 404, string $message = ''): never
    {
        http_response_code($code);
        View::render('errors/404', [
            'code'    => $code,
            'message' => $message ?: 'Aradığınız sayfa bulunamadı veya taşınmış olabilir.',
            'meta'    => ['title' => $code . ' Bulunamadı'],
        ]);
        exit;
    }
}
