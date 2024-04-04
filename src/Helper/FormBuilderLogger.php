<?php

namespace Coen\CrudBundle\Helper;
use ArrayIterator;
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

class FormBuilderLogger implements FormBuilderInterface, IteratorAggregate
{
    private array $loggedChilds = [];
    private array $loggedEventListener = [];
    private array $loggedEventSubscriber = [];
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->loggedChilds);
    }

    public function count(): int
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function add(string|FormBuilderInterface $child, string $type = null, array $options = []): static
    {
        if(!is_string($child)) {
            throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
        }

        $this->loggedChilds[$child] = [
            'name' => $child,
            'type' => $type,
            'options' => $options
        ];

        return $this;
    }

    /**
     * @throws Exception
     */
    public function create(string $name, string $type = null, array $options = []): FormBuilderInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function get(string $name): FormBuilderInterface
    {
        throw new Exception(self::class . ' cant create forms');
    }

    public function remove(string $name): static
    {
        if(array_key_exists($name, $this->loggedChilds)) {
            unset($this->loggedChilds[$name]);
        }

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->loggedChilds);
    }

    public function all(): array
    {
        return $this->loggedChilds;
    }

    public function getForm(): FormInterface
    {
        throw new Exception(self::class . ' cant create forms');
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

    public function addViewTransformer(DataTransformerInterface $viewTransformer, bool $forcePrepend = false): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function resetViewTransformers(): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function addModelTransformer(DataTransformerInterface $modelTransformer, bool $forceAppend = false): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function resetModelTransformers(): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setAttribute(string $name, mixed $value): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setAttributes(array $attributes): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setDataMapper(?DataMapperInterface $dataMapper): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setDisabled(bool $disabled): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setEmptyData(mixed $emptyData): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setErrorBubbling(bool $errorBubbling): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setRequired(bool $required): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setPropertyPath(PropertyPathInterface|string|null $propertyPath): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setMapped(bool $mapped): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setByReference(bool $byReference): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setInheritData(bool $inheritData): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setCompound(bool $compound): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setType(ResolvedFormTypeInterface $type): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setData(mixed $data): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setDataLocked(bool $locked): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setFormFactory(FormFactoryInterface $formFactory): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setAction(string $action): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setMethod(string $method): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setRequestHandler(RequestHandlerInterface $requestHandler): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setAutoInitialize(bool $initialize): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getFormConfig(): FormConfigInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function setIsEmptyCallback(?callable $isEmptyCallback): static
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getName(): string
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getPropertyPath(): ?PropertyPathInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getMapped(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getByReference(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getInheritData(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getCompound(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getType(): ResolvedFormTypeInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getViewTransformers(): array
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getModelTransformers(): array
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getDataMapper(): ?DataMapperInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getRequired(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getDisabled(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getErrorBubbling(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getEmptyData(): mixed
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getAttributes(): array
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function hasAttribute(string $name): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getData(): mixed
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getDataClass(): ?string
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getDataLocked(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getFormFactory(): FormFactoryInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getAction(): string
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getMethod(): string
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getAutoInitialize(): bool
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function getIsEmptyCallback(): ?callable
    {
        throw new Exception('Class ' . __CLASS__ . ' is a fake FormBuilder that logs config. Method ' . __FUNCTION__ . ' is not implemented.');
    }
}