<?php


namespace Test\Ecotone\Dbal\Fixture;


use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class StubMethodInvocation implements MethodInvocation
{
    /**
     * @var integer
     */
    private $calledTimes = 0;

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    public function getCalledTimes(): int
    {
        return $this->calledTimes;
    }

    public function proceed()
    {
        $this->calledTimes++;
    }

    public function getObjectToInvokeOn()
    {
        return new \stdClass();
    }

    public function getInterceptedClassName(): string
    {
        return self::class;
    }

    public function getInterceptedMethodName(): string
    {
        return "getInterceptedInterface";
    }

    public function getInterceptedInterface(): InterfaceToCall
    {
        return InterfaceToCall::create(self::class, "getInterceptedInterface");
    }

    public function getEndpointAnnotations(): iterable
    {
        return [];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function replaceArgument(string $parameterName, $value): void
    {
        return;
    }
}