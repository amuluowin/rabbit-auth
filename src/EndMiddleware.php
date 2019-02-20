<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/15
 * Time: 1:53
 */

namespace rabbit\auth\middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rabbit\auth\AbstractAuth;
use rabbit\core\Context;
use rabbit\core\ObjectFactory;
use rabbit\server\AttributeEnum;
use rabbit\web\HttpException;
use rabbit\web\NotFoundHttpException;

/**
 * Class EndMiddleware
 * @package rabbit\auth\middleware
 */
class EndMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = explode('/', ltrim($request->getUri()->getPath(), '/'));
        if (count($route) < 2) {
            throw new NotFoundHttpException("can not find the route:$route");
        }
        $controller = 'apis';
        foreach ($route as $index => $value) {
            if ($index === count($route) - 1) {
                $action = $value;
            } elseif ($index === count($route) - 2) {
                $controller .= '\controllers\\' . ucfirst($value) . 'Controller';
            } else {
                $controller .= '\\' . $value;
            }
        }
        $controller = ObjectFactory::get($controller);
        if ($controller === null) {
            throw new NotFoundHttpException("can not find the route:$route");
        }
        if ($controller instanceof AbstractAuth && !$controller->auth($request)) {
            throw new HttpException(401, 'Your request was made with invalid credentials.');
        }
        /**
         * @var ResponseInterface $response
         */
        $response = call_user_func_array([$controller, $action], $request->getQueryParams());
        if (!$response instanceof ResponseInterface) {
            /**
             * @var ResponseInterface $newResponse
             */
            $newResponse = Context::get('response');
            $response = $newResponse->withAttribute(AttributeEnum::RESPONSE_ATTRIBUTE, $response);
        }

        return $response;
    }

}