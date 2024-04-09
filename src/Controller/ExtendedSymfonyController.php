<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Form\CrudEntityType;
use Coen\CrudBundle\Helper\ButtonHandler;
use Coen\CrudBundle\Helper\CrudRouterDecorator;
use Coen\CrudBundle\Helper\TranslationKeyFactory;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Service\TwigTemplateSelector;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class ExtendedSymfonyController extends AbstractController
{
    protected string $entityClass;
    protected ObjectRepository $entityRepository;
    protected ReflectionEntity $entityReflection;
    protected TranslationKeyFactory $translationKeyFactory;
    protected CrudRouterDecorator $crudRouter;
    protected TwigTemplateSelector $twigTemplateSelector;

    /**
     * @throws ReflectionException
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        TwigTemplateSelector $twigTemplateSelector
    )
    {
        $this->entityReflection = $this->createEntityReflection();
        $this->entityRepository = $this->entityManager->getRepository($this->entityClass);

        $this->twigTemplateSelector = clone $twigTemplateSelector;
        $this->twigTemplateSelector->setEntityReflection($this->entityReflection);

        $this->translationKeyFactory = new TranslationKeyFactory($this->entityReflection);
    }

    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $this->crudRouter = new CrudRouterDecorator($container->get('router'), $this);

        return parent::setContainer($container);
    }

    /**
     * @throws ReflectionException
     */
    protected function createEntityReflection(): ReflectionEntity
    {
        return new ReflectionEntity(new ReflectionClass($this->entityClass));
    }

    // Access Helper Function

    protected function hasAccessRole(CrudAction $action): bool
    {
        $accessRole = $this->entityReflection->getAccessRole($action);

        if(null !== $accessRole) {
            return $this->isGranted($accessRole, $this->getUser());
        }

        return true;
    }

    protected function hasAction(CrudAction $action): bool
    {
        return $this->entityReflection->hasAction($action);
    }

    protected function hasAccess(CrudAction $action): bool
    {
        return $this->hasAccessRole($action) && $this->hasAction($action);
    }

    protected function checkAccess(CrudAction $action, object $entity = null): void
    {
        if(!$this->hasAccess($action)) {
            throw new NotFoundHttpException();
        }
    }

    // View Helper Function

    protected function renderCrud(CrudAction $action, array $parameters = [], string $template = null, Response $response = null): Response
    {
        $data = [];

        // Crud Helper
        $data['twigTemplateSelector'] = $this->twigTemplateSelector;
        $data['translationKeyFactory'] = $this->translationKeyFactory;
        $data['crudRouter'] = $this->crudRouter;
        $data['crudButton'] = new ButtonHandler(
            $this->entityReflection,
            $this->translationKeyFactory,
            $this->twigTemplateSelector,
            $this->crudRouter,
            $this->container->get('twig'),
            $this->container->get('security.authorization_checker')
        );

        // Add Entity Data
        $data['crudAction'] = CrudAction::toTwigArray();
        $data['currentAction'] = $action;
        $data['entityReflection'] = $this->entityReflection;

        return$this->render(
            $template ?? $this->twigTemplateSelector->getActionTemplate($action),
            array_merge($data, $parameters),
            $response
        );
    }

    protected function createEntityForm(CrudAction $action, ?object $entity, array $options = []): FormInterface
    {
        return $this->createForm(CrudEntityType::class, $entity, array_merge($options, [
            'crud_action' => $action,
            'entity_reflection' => $this->entityReflection,
            'entity_repository' => $this->entityRepository,
            'data_class' => $this->entityReflection->getClass(),
            'form_customisation' => $this->getFormCustomisations()
        ]));
    }

    protected abstract function getFormCustomisations(): array;

    /**
     * @throws \Exception
     */
    protected function renderCrudForm(CrudAction $action, Request $request, object $entity = null): Response
    {
        $form = $this->createEntityForm($action, $entity ?? $this->createNewEntity());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $this->beforePersist($action, $entity);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->afterPersist($action, $entity);

            return $this->successActionRedirect($action);
        }

        return $this->renderCrud($action, [
            'entity' => $form->getData(),
            'form' => $form->createView(),
        ]);
    }

    protected function createNewEntity(): mixed {
        $entity = new $this->entityClass();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        foreach($this->entityReflection->getProperties() as $property) {
            if($property->isId()) {
                continue;
            }

            if($value = $request->get($property->getName(), null)) {
                if(!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $val) {
                    $val = match ($property->getFormType()) {
                        'date' => strtotime($val),
                        'enum' => function ($property, $value) {
                            foreach($property->getEnumType()::cases() as $case) {
                                if($case->name == $value) {
                                    return $case;
                                }
                            }

                            return null;
                        },
                        'collection' => $this->entityManager->getRepository($property->getTargetEntity())->find($val),
                        default => $val
                    };

                    $property->set($entity, $val);
                }
            }
        }

        return $entity;
    }

    // Redirect Methods

    protected function successActionRedirect(CrudAction $action): RedirectResponse
    {
        $this->addFlash('success', 'Done');
        return $this->redirectToAction(CrudAction::LIST);
    }

    protected function errorActionRedirect(CrudAction $action, \Exception $exception): RedirectResponse
    {
        if($this->getParameter('kernel.environment') == 'dev') {
            throw $exception;
        }

        $this->addFlash('danger', $exception->getMessage());
        return $this->redirectToAction(CrudAction::LIST);
    }

    // Router Alias

    protected function getRouteForAction(CrudAction $action): string
    {
        return $this->crudRouter->getRouteForAction($action);
    }

    protected function redirectToAction(CrudAction $action, array $parameters = []): RedirectResponse
    {
        return $this->crudRouter->redirectToAction($action, $parameters);
    }

    protected function generateUrlForAction(CrudAction $action): string
    {
        return $this->crudRouter->generateForAction($action);
    }

    protected abstract function beforePersist(CrudAction $action, object &$entity);

    protected abstract function afterPersist(CrudAction $action, object &$entity);
}

