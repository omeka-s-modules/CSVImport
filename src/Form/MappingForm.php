<?php
namespace CSVImport\Form;

use Omeka\Form\Form;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends Form
{
    public function buildForm()
    {
        $resourceType = $this->getOption('resourceType');
        $currentUser = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        $userRole = $currentUser->getRole();
        $translator = $this->getTranslator();
        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
                'info'  => $translator->translate('A note about the purpose or source of this import.')
            ),
            'attributes' => array(
                'id' => 'comment',
                'class' => 'input-body',
            ),
        ));

        if ($resourceType == 'items' || $resourceType == 'item_sets') {
            $serviceLocator = $this->getServiceLocator();
            $url = $serviceLocator->get('ViewHelperManager')->get('url');
            $templateSelect = new ResourceSelect($serviceLocator);
            $templateSelect
                ->setName('o:resource_template[o:id]')
                ->setAttribute('id', 'resource-template-select')
                ->setAttribute('data-api-base-url', $url('api/default',
                    ['resource' => 'resource_templates']))
                ->setLabel('Resource Template') // @translate
                ->setEmptyOption('Select Template') // @translate
                ->setOption('info', 'A pre-defined template for resource creation.') // @translate
                ->setResourceValueOptions(
                    'resource_templates',
                    [],
                    function ($template, $serviceLocator) {
                        return $template->label();
                    }
                );
                $this->add($templateSelect);
                $classSelect = new ResourceSelect($serviceLocator);
                $classSelect
                    ->setName('o:resource_class[o:id]')
                    ->setAttribute('id', 'resource-class-select')
                    ->setLabel('Class') // @translate
                    ->setEmptyOption('Select Class') // @translate
                    ->setOption('info', 'A type for the resource. Different types have different default properties attached to them.') // @translate
                    ->setResourceValueOptions(
                        'resource_classes',
                        [],
                        function ($resourceClass, $serviceLocator) {
                            return [
                                $resourceClass->vocabulary()->label(),
                                $resourceClass->label()
                            ];
                        }
                    );
                $this->add($classSelect);
                $inputFilter = $this->getInputFilter();
                $inputFilter->add([
                    'name' => 'o:resource_template[o:id]',
                    'required' => false,
                ]);
                $inputFilter->add([
                    'name' => 'o:resource_class[o:id]',
                    'required' => false,
                ]);
            }

            $itemSetSelect = new ResourceSelect($serviceLocator);
            $itemSetSelect->setName('o:item_set')
                ->setAttribute('required', false)
                ->setAttribute('multiple', true)
                ->setAttribute('id', 'select-item-set')
                ->setAttribute('data-placeholder', $translator->translate('Select Item Sets...'))
                ->setLabel($translator->translate('Item Sets'))
                ->setOption('info', $translator->translate('Select Items Sets for this resource.'))
                ->setResourceValueOptions(
                    'item_sets',
                    [],
                    function ($itemSet, $serviceLocator) {
                        return $itemSet->displayTitle();
                    }
                );
            if (!$itemSetSelect->getValueOptions()) {
                $itemSetSelect->setAttribute('disabled', true);
                $itemSetSelect->setAttribute('data-placeholder',
                    $translator->translate('No item sets exist'));
            }
            $this->add($itemSetSelect);

            if( ($userRole == 'global_admin') || ($userRole == 'site_admin')) {
                $ownerSelect = new ResourceSelect($this->getServiceLocator());
                $ownerSelect->setName('o:owner')
                    ->setAttribute('id', 'select-owner')
                    ->setLabel($translator->translate('Owner'))
                    ->setOption('info', $translator->translate('Assign ownership'))
                    ->setResourceValueOptions(
                        'users',
                        [],
                        function ($user, $serviceLocator) {
                            return $user->name();
                        }
                    );
                $this->add($ownerSelect);
            }

            $this->add(array(
                'name' => 'multivalue-separator',
                'type' => 'text',
                'options' => array(
                    'label' => $translator->translate('Multivalue Separator'),
                    'info'  => $translator->translate('The separator to use for columns with multiple values.')
                ),
                'attributes' => array(
                    'id' => 'multivalue-separator',
                    'class' => 'input-body',
                    'value' => ','
                )
            ));
        }
}
