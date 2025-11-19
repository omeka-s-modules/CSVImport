<?php

namespace CSVImport\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use CSVImport\Form\MappingEditForm;
use CSVImport\Form\MappingSelectForm;
use CSVImport\Form\MappingSaveForm;

class MappingController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('csvimport_mappings');

        $this->paginator($response->getTotalResults());

        $formDeleteSelected = $this->getForm(ConfirmForm::class);
        $formDeleteSelected->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'batch-delete'], true));
        $formDeleteSelected->setButtonLabel('Confirm Delete'); // @translate
        $formDeleteSelected->setAttribute('id', 'confirm-delete-selected');

        $view = new ViewModel;
        $mappingModels = $response->getContent();
        $view->setVariable('mappingModels', $mappingModels);
        $view->setVariable('formDeleteSelected', $formDeleteSelected);
        return $view;
    }

    public function showAction()
    {
        $propertiesMap = [];
        $properties = $this->api()->search('properties')->getContent();
        foreach ($properties as $property) {
            $propertiesMap[$property->id()] = $property->term();
        }

        $mappingModel = $this->loadMapping($this->params('id'), []);

        $view = new ViewModel;
        $view->setVariable('propertiesMap', $propertiesMap);
        $view->setVariable('columns', $mappingModel['columns']);
        unset($mappingModel['columns']);
        $this->logger()->debug(json_encode($mappingModel));
        $view->setVariable('automaps', $mappingModel);
        return $view;
    }

    public function saveAction()
    {
        $response = $this->api()->search('csvimport_mappings');
        $mappings = $response->getContent();
        $mappingNames = [];
        foreach ($mappings as $mapping) {
            $mappingNames[] = $mapping->name();
        }

        $query = $this->params()->fromQuery();

        $jobId = null;
        if ($this->getRequest()->isPost()) {
            if (!empty($this->params()->fromPost()['job_id']))
                $jobId = $this->params()->fromPost()['job_id'];
            else {
                $this->messenger()->addError('Job id not provided.'); // @translate;
                return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
            }
        }
        else {
            if (!empty($query['job_id'])) {
                $jobId = $query['job_id'];
            }
            else {
                return $this->getResponse()->setStatusCode(404)->setContent('Job id not provided.'); // @translate
            }
        }

        $view = new ViewModel;
        $form = $this->getForm(MappingSaveForm::class, ['job_id' => $jobId]);
        $view->setVariable('form', $form);
        $view->setTerminal(true);
        $view->setTemplate('csv-import/admin/mapping/save-mapping');
        $view->setVariable('mappings', $mappingNames);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);

            if ($form->isValid()) {

                $job = null;
                $job = $this->api()->read('jobs', $jobId)->getContent();
                if (empty($job)) {
                    $this->messenger()->addError('Could not find job with id %s.', $jobId); // @translate;
                    return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
                }

                $args = $job->args();
                $args['override_mapping'] = $data['override_mapping'] ?? null;
                $args['mapping_name'] = $data['mapping_name'];
                if (!$this->saveMapping($args)) {
                    // TODO Keep user variables when the form is invalid.
                    $this->messenger()->addError('A mapping with that name already exists.'); // @translate
                    return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
                }
                else {
                    $this->messenger()->addSuccess(sprintf('Mapping successfully saved as %s.', // @translate 
                $data['mapping_name']));
                    return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
                }
            }
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
        }

        return $view;
    }

    /*
     * meant for JS
     */
    public function selectMappingAction()
    {
        $response = $this->api()->search('csvimport_mappings');
        $mappings = $response->getContent();

        $view = new ViewModel;
        $form = $this->getForm(MappingSelectForm::class);
        $view->setVariable('form', $form);
        $view->setTerminal(true);
        $view->setTemplate('csv-import/admin/mapping/select-mapping');

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $mappingId = $form->get('mapping_id')->getValue();

                $session = new \Laminas\Session\Container('CsvImport');

                $columns = $session->columns;

                $response = $this->api()->read('csvimport_mappings', $mappingId);
                if ($response) {
                    $view = new ViewModel([
                        'automaps' => $this->loadMapping($mappingId, $columns),
                        'columns' => $columns,
                        'resourceType' => $session->resourceType,
                    ]);
                    $view->setTemplate('common/mapping-table');
                    $view->setTerminal(true); // no layout
                    return $view;
                } else {
                    return $this->getResponse()->setStatusCode(404)->setContent('Mapping not found.'); // @translate
                }
            }
            return $this->getResponse()->setStatusCode(400)->setContent('Mapping selection form not valid.');
        }

        return $view;
    }

    public function editAction()
    {
        $response = $this->api()->read('csvimport_mappings', $this->params('id'));
        $mapping = $response->getContent();

        $view = new ViewModel;
        $form = $this->getForm(MappingEditForm::class);
        $form->setAttribute('action', $mapping->url('edit'));
        $form->setData([
            'model_name' => $mapping->name(),
        ]);

        $view->setVariable('form', $form);
        $view->setTerminal(true);
        $view->setTemplate('csv-import/admin/mapping/edit');
        $view->setVariable('mapping', $mapping);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $mappingName = $form->get('model_name')->getValue();

                $response = $this->api($form)->update('csvimport_mappings', $this->params('id'), ['name' => $mappingName], [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Mapping successfully updated'); // @translate
                    return $this->redirect()->toRoute(
                        'admin/csvimport/mapping',
                        ['action' => 'browse'],
                        true
                    );
                }
            } else {
                $this->messenger()->addFormErrors($form);
                return $this->redirect()->toRoute(
                    'admin/csvimport/mapping',
                    ['action' => 'browse'],
                    true
                );
            }
        }
        return $view;
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('csvimport_mappings', $this->params('id'));
        $mappingModel = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $mappingModel);
        $view->setVariable('resourceLabel', 'Mapping'); // @translate
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('csvimport_mappings', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Mapping successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(
            'admin/csvimport/mapping',
            ['action' => 'browse'],
            true
        );
    }
}
