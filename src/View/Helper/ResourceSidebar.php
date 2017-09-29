<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ResourceSidebar extends AbstractHelper
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function __invoke($resourceType = null)
    {
        $userRole = $this->user->getRole();
        return $this->getView()->partial(
            'common/resource-sidebar',
            [
                'userRole' => $userRole,
                'resourceType' => $resourceType,
            ]
        );
    }
}
