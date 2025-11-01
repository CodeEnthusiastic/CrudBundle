<?php

namespace Coen\CrudBundle\Decorator;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Reflection\ReflectionProperty;
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
use Traversable;

class FormBuilderDecorator implements FormBuilderInterface, IteratorAggregate
{
    private array $removedProperties = [];
    private ReflectionProperty $currentProperty;

    public function __construct(
        protected readonly FormBuilderInterface $formBuilder,
        protected readonly EntityContext $entityContext,
        protected readonly CrudAction $currentAction
    ) { }


    protected function createDefaultOptions(ReflectionProperty $property): array
    {
        return  [
            'required' => $property->isRequired(),
            'disabled' => $property->isDisabled(),
            'label' => $property->getTranslationKey()
        ];
    }

    public function addForProperty(?string $type = null, array $options = []): static
    {
        $currentProperty = $this->currentProperty;

        foreach ($this->createDefaultOptions($currentProperty) as $key => $value) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        $this->add($currentProperty->getName(), $type, $options);
        return $this;
    }


    public function removeProperty(): void
    {
        $this->remove($this->currentProperty->getName());
    }

    // Decorator Function

    public function getIterator(): Traversable
    {
        return $this->formBuilder->getIterator();
    }

    public function count(): int
    {
        return $this->formBuilder->count();
    }

    public function add(string|FormBuilderInterface $child, ?string $type = null, array $options = []): static
    {
        if(is_string($child) && in_array($child, $this->removedProperties)) {
            unset($this->removedProperties[$child]);
        }
        $this->formBuilder->add($child, $type, $options);
        return $this;
    }

    public function create(string $name, ?string $type = null, array $options = []): FormBuilderInterface
    {
        return $this->formBuilder->create($name, $type, $options);
    }

    public function get(string $name): FormBuilderInterface
    {
        return $this->formBuilder->get($name);
    }

    public function remove(string $name): static
    {
        $this->removedProperties[] = $name;
        $this->formBuilder->remove($name);
        return $this; // return $this, da es sich um eine fließende Methode handelt
    }

    public function has(string $name): bool
    {
        return $this->formBuilder->has($name);
    }

    public function all(): array
    {
        return $this->formBuilder->all();
    }

    public function getForm(): FormInterface
    {
        return $this->formBuilder->getForm();
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->formBuilder->addEventListener($eventName, $listener, $priority);
        return $this;
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): static
    {
        $this->formBuilder->addEventSubscriber($subscriber);
        return $this;
    }

    public function addViewTransformer(DataTransformerInterface $viewTransformer, bool $forcePrepend = false): static
    {
        $this->formBuilder->addViewTransformer($viewTransformer, $forcePrepend);
        return $this;
    }

    public function resetViewTransformers(): static
    {
        $this->formBuilder->resetViewTransformers();
        return $this;
    }

    public function addModelTransformer(DataTransformerInterface $modelTransformer, bool $forceAppend = false): static
    {
        $this->formBuilder->addModelTransformer($modelTransformer, $forceAppend);
        return $this;
    }

    public function resetModelTransformers(): static
    {
        $this->formBuilder->resetModelTransformers();
        return $this;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->formBuilder->setAttribute($name, $value);
        return $this;
    }

    public function setAttributes(array $attributes): static
    {
        $this->formBuilder->setAttributes($attributes);
        return $this;
    }

    public function setDataMapper(?DataMapperInterface $dataMapper): static
    {
        $this->formBuilder->setDataMapper($dataMapper);
        return $this;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->formBuilder->setDisabled($disabled);
        return $this;
    }

    public function setEmptyData(mixed $emptyData): static
    {
        $this->formBuilder->setEmptyData($emptyData);
        return $this;
    }

    public function setErrorBubbling(bool $errorBubbling): static
    {
        $this->formBuilder->setErrorBubbling($errorBubbling);
        return $this;
    }

    public function setRequired(bool $required): static
    {
        $this->formBuilder->setRequired($required);
        return $this;
    }

    public function setPropertyPath(PropertyPathInterface|string|null $propertyPath): static
    {
        $this->formBuilder->setPropertyPath($propertyPath);
        return $this;
    }

    public function setMapped(bool $mapped): static
    {
        $this->formBuilder->setMapped($mapped);
        return $this;
    }

    public function setByReference(bool $byReference): static
    {
        $this->formBuilder->setByReference($byReference);
        return $this;
    }

    public function setInheritData(bool $inheritData): static
    {
        $this->formBuilder->setInheritData($inheritData);
        return $this;
    }

    public function setCompound(bool $compound): static
    {
        $this->formBuilder->setCompound($compound);
        return $this;
    }

    public function setType(ResolvedFormTypeInterface $type): static
    {
        $this->formBuilder->setType($type);
        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->formBuilder->setData($data);
        return $this;
    }

    public function setDataLocked(bool $locked): static
    {
        $this->formBuilder->setDataLocked($locked);
        return $this;
    }

    public function setFormFactory(FormFactoryInterface $formFactory): static
    {
        $this->formBuilder->setFormFactory($formFactory);
        return $this;
    }

    public function setAction(string $action): static
    {
        $this->formBuilder->setAction($action);
        return $this;
    }

    public function setMethod(string $method): static
    {
        $this->formBuilder->setMethod($method);
        return $this;
    }

    public function setRequestHandler(RequestHandlerInterface $requestHandler): static
    {
        $this->formBuilder->setRequestHandler($requestHandler);
        return $this;
    }

    public function setAutoInitialize(bool $initialize): static
    {
        $this->formBuilder->setAutoInitialize($initialize);
        return $this;
    }

    public function getFormConfig(): FormConfigInterface
    {
        return $this->formBuilder->getFormConfig();
    }

    public function setIsEmptyCallback(?callable $isEmptyCallback): static
    {
        $this->formBuilder->setIsEmptyCallback($isEmptyCallback);
        return $this;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->formBuilder->getEventDispatcher();
    }

    public function getName(): string
    {
        return $this->formBuilder->getName();
    }

    public function getPropertyPath(): ?PropertyPathInterface
    {
        return $this->formBuilder->getPropertyPath();
    }

    public function getMapped(): bool
    {
        return $this->formBuilder->getMapped();
    }

    public function getByReference(): bool
    {
        return $this->formBuilder->getByReference();
    }

    public function getInheritData(): bool
    {
        return $this->formBuilder->getInheritData();
    }

    public function getCompound(): bool
    {
        return $this->formBuilder->getCompound();
    }

    public function getType(): ResolvedFormTypeInterface
    {
        return $this->formBuilder->getType();
    }

    public function getViewTransformers(): array
    {
        return $this->formBuilder->getViewTransformers();
    }

    public function getModelTransformers(): array
    {
        return $this->formBuilder->getModelTransformers();
    }

    public function getDataMapper(): ?DataMapperInterface
    {
        return $this->formBuilder->getDataMapper();
    }

    public function getRequired(): bool
    {
        return $this->formBuilder->getRequired();
    }

    public function getDisabled(): bool
    {
        return $this->formBuilder->getDisabled();
    }

    public function getErrorBubbling(): bool
    {
        return $this->formBuilder->getErrorBubbling();
    }

    public function getEmptyData(): mixed
    {
        return $this->formBuilder->getEmptyData();
    }

    public function getAttributes(): array
    {
        return $this->formBuilder->getAttributes();
    }

    public function hasAttribute(string $name): bool
    {
        return $this->formBuilder->hasAttribute($name);
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->formBuilder->getAttribute($name, $default);
    }

    public function getData(): mixed
    {
        return $this->formBuilder->getData();
    }

    public function getDataClass(): ?string
    {
        return $this->formBuilder->getDataClass();
    }

    public function getDataLocked(): bool
    {
        return $this->formBuilder->getDataLocked();
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->formBuilder->getFormFactory();
    }

    public function getAction(): string
    {
        return $this->formBuilder->getAction();
    }

    public function getMethod(): string
    {
        return $this->formBuilder->getMethod();
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->formBuilder->getRequestHandler();
    }

    public function getAutoInitialize(): bool
    {
        return $this->formBuilder->getAutoInitialize();
    }

    public function getOptions(): array
    {
        return $this->formBuilder->getOptions();
    }

    public function hasOption(string $name): bool
    {
        return $this->formBuilder->hasOption($name);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->formBuilder->getOption($name, $default);
    }

    public function getIsEmptyCallback(): ?callable
    {
        return $this->formBuilder->getIsEmptyCallback();
    }

    public function isPropertyRemoved(ReflectionProperty $property): bool
    {
        return in_array($property->getName(), $this->removedProperties);
    }

    public function setCurrentProperty(ReflectionProperty $property): void
    {
        $this->currentProperty = $property;
    }

    public function getCurrentProperty(): ReflectionProperty
    {
        return $this->currentProperty;
    }

    public function needsDefaultField(): bool
    {
        return !$this->has($this->currentProperty->getName()) && !$this->isPropertyRemoved($this->currentProperty);
    }

    public function getCurrentAction(): CrudAction
    {
        return $this->currentAction;
    }

    public function getEntityContext(): EntityContext
    {
        return $this->entityContext;
    }
}
