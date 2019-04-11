<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 2:24
 */

namespace rabbit\auth;


use Lcobucci\JWT\Token;
use Psr\Http\Message\ServerRequestInterface;
use rabbit\core\ObjectFactory;
use rabbit\server\RequestHandlerInterface;

/**
 * Class AbstractHandler
 * @package rabbit\auth
 */
abstract class AbstractAuth implements RequestHandlerInterface
{
    /**
     * @var AuthMethod[]
     */
    protected $authMethod = [
        [
            'class' => QueryAuth::class,
        ], [
            'class' => HttpBearerAuth::class
        ]
    ];

    /**
     * @param ServerRequestInterface $request
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function auth(ServerRequestInterface $request): bool
    {
        $res = false;
        foreach ($this->authMethod as $authMethod) {
            /** @var AuthMethod $authMethod */
            $authMethod = ObjectFactory::createObject($authMethod);
            if ($authMethod->authenticate($request)) {
                /** @var AuthInterface $auth */
                $auth = getDI('auth');
                /** @var Token $token */
                $token = $auth->parseToken($request->getAttribute(AuthMethod::AUTH_TOKEN_STRING));
                $res = !$token->isExpired(\DateTimeImmutable::createFromFormat('U.u', microtime(true)));
                if ($res) {
                    $request->withAttribute(AuthMethod::AUTH_TOKEN, $token);
                }
            }
        }
        return $res;
    }
}