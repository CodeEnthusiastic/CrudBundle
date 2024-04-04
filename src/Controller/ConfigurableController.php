<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Service\TwigTemplateSelector;
use Doctrine\ORM\EntityManagerInterface;

abstract class ConfigurableController extends ActionController
{
    private string $baseAppTemplate;
    private array $formCustomisation = [];

    public function __construct(EntityManagerInterface $entityManager, TwigTemplateSelector $twigTemplateSelector)
    {
        $this->configure();

        parent::__construct($entityManager, $twigTemplateSelector);

        $this->twigTemplateSelector->setAppBaseTemplate($this->baseAppTemplate);
    }

    protected abstract function configure(): void;

    /**
     * Define the Entity Class for the Crud Controller.
     *
     * @param string $entityClass
     * @return void
     */
    protected function setEntityClass(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Define the base Template for this Crud Controller
     *
     * @param string $baseAppTemplate
     * @return void
     */
    protected function setAppBaseTemplate(string $baseAppTemplate)
    {
        $this->baseAppTemplate = $baseAppTemplate;
    }

    /**
     * Add a custom Field for a specific property of the Entity
     *
     * @param string $propertyName
     * @param callable $function
     * @return void
     */
    protected function addFormCustomisation(string $propertyName, callable $function)
    {
        $this->formCustomisation[$propertyName] = $function;
    }

    protected function getFormCustomisations(): array
    {
        return $this->formCustomisation;
    }

    protected function beforePersist(CrudAction $action, object &$entity)
    {
    }

    protected function afterPersist(CrudAction $action, object &$entity)
    {
    }
}
