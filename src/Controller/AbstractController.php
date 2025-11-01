<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Configurator\EntityConfigurator;
use Coen\CrudBundle\Decorator\RouterDecorator;
use Coen\CrudBundle\Generator\ButtonGenerator;
use Coen\CrudBundle\Generator\FilterGeneratorInterface;
use Coen\CrudBundle\Generator\FormGenerator;
use Coen\CrudBundle\Generator\TranslationKeyGenerator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Helper\TwigTemplateSelector;
use Coen\CrudBundle\Service\CrudServiceFactory;
use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Service\SymfonyServiceFacade;
use ReflectionClass;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractController extends AbstractSymfonyController
{
    protected EntityContext $entityContext;
    protected FilterGeneratorInterface $filterService;
    protected TranslationKeyGenerator $translationKeyGenerator;
    protected TwigTemplateSelector $templateSelector;

    protected RouterDecorator $router;
    protected ButtonGenerator $buttonGenerator;
    protected FormGenerator $crudFormGenerator;

    abstract protected function defineEntityClass(): string;
    abstract protected function configure(EntityConfigurator $configurator);

    public function __construct(
        SymfonyServiceFacade $symfonyServiceFacade,
        CrudServiceFactory   $crudServiceFactory,
    )
    {
        parent::__construct($symfonyServiceFacade);

        $crudServicesCollection = $crudServiceFactory->create($this->defineEntityClass(), self::generateBaseRoute($this));

        $this->entityContext = $crudServicesCollection->getEntityContext();

        $this->configure($this->entityContext->getConfigurator());

        $this->filterService = $crudServicesCollection->getFilterGenerator();
        $this->translationKeyGenerator = $crudServicesCollection->getTranslationKeyGenerator();
        $this->templateSelector = $crudServicesCollection->getTwigTemplateSelector();
        $this->router = $crudServicesCollection->getRouter();
        $this->crudFormGenerator = $crudServicesCollection->getFormGenerator();
        $this->buttonGenerator = $crudServicesCollection->getButtonGenerator();
    }

    protected static function generateBaseRoute(AbstractController $class)
    {
        $reflector = new ReflectionClass($class);
        foreach($reflector->getAttributes() as $attribute) {
            if($attribute->getName() == Route::class) {
                return $attribute->getArguments()['name'] ?? '';
            }
        }

        return '';
    }

    // Twig Helper Function

    protected function renderCrud(array $parameters = [], string $template = null, Response $response = null): Response
    {
        $data = [];

        $data['templateSelector'] = $this->templateSelector;
        $data['translationKeyGenerator'] = $this->translationKeyGenerator;
        $data['router'] = $this->router;
        $data['entityReflection'] = $this->entityContext->getReflection();

        return $this->render(
            $template,
            array_merge($data, $parameters),
            $response
        );
    }

    protected function saveEntity(object $entity)
    {
        $this->beforePersist($entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->afterPersist($entity);
    }

    protected function renderCrudForm(Request $request, FormInterface $formBuilder): Response
    {
        $form = $formBuilder;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            $this->saveEntity($entity);

            return $this->successResponse();
        }

        return $this->renderCrud([
            'entity' => $form->getData(),
            'form' => $form->createView(),
        ]);
    }


    // Access Helper Function

    protected function hasAccessRole(CrudAction $action): bool
    {
        $accessRole = $this->entityContext->getReflection()->getAccessRole($action);

        if(null !== $accessRole) {
            return $this->isGranted($accessRole, $this->getUser());
        }

        return true;
    }

    protected function hasAction(CrudAction $action): bool
    {
        return $this->entityContext->getReflection()->hasAction($action);
    }

    protected function hasAccess(CrudAction $action): bool
    {
        return $this->hasAccessRole($action) && $this->hasAction($action);
    }
}