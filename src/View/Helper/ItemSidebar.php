<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ItemSidebar extends AbstractHelper
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function __invoke()
    {
        $userRole = $this->user->getRole();
        return $this->getView()->partial(
            'common/item-sidebar',
            [
                'userRole' => $userRole,
            ]
        );
    }
}
