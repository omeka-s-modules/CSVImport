<?php
namespace CSVImport\View\Helper;

use CSVImport\Form\ResourceSidebarFieldset;
use Omeka\Entity\User;
use Zend\View\Helper\AbstractHelper;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill;

class ResourceSidebar extends AbstractHelper
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var FormElementManagerV3Polyfill
     */
    protected $formElementManager;

    /**
     * @param User $user
     * @param FormElementManagerV3Polyfill $formElementManager
     */
    public function __construct(User $user, FormElementManagerV3Polyfill $formElementManager)
    {
        $this->user = $user;
        $this->formElementManager = $formElementManager;
    }

    /**
     * Render the resource sidebar.
     *
     * @param string $resourceType
     * @return string
     */
    public function __invoke($resourceType = null)
    {
        $form = $this->formElementManager->get(ResourceSidebarFieldset::class, [
            'resourceType' => $resourceType,
        ]);

        return $this->getView()->partial(
            'common/resource-sidebar',
            [
                'resourceType' => $resourceType,
                'form' => $form,
            ]
        );
    }
}
