<?php

namespace Coen\CrudBundle\Form;

use ArrayIterator;
use Coen\CrudBundle\Decorator\FormBuilderDecorator;
use Exception;
use IteratorAggregate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class FormBuilderLogger extends FormBuilderDecorator
{
    private array $loggedChildren = [];
    private array $loggedEventListener = [];
    private array $loggedEventSubscriber = [];

    public function getLoggedChildren(): array
    {
        return $this->loggedChildren;
    }

    public function getLoggedEventListener(): array
    {
        return $this->loggedEventListener;
    }

    public function getLoggedEventSubscriber(): array
    {
        return $this->loggedEventSubscriber;
    }

    public function add(string|FormBuilderInterface $child, string $type = null, array $options = []): static
    {
        $this->loggedChildren[$child] = [
            'name' => $child,
            'type' => $type,
            'options' => $options
        ];

        return $this;
    }

    public function remove(string $name): static
    {
        if(array_key_exists($name, $this->loggedChildren)) {
            unset($this->loggedChildren[$name]);
        }

        return $this;
    }
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->loggedEventListener[] = [
            'eventName' => $eventName,
            'listener' => $listener,
            'priority' => $priority
        ];

        return $this;
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): static
    {
        $this->loggedEventSubscriber[] = $subscriber;

        return $this;
    }
}