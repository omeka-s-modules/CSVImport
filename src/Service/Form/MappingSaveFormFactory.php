<?php
namespace CSVImport\Service\Form;

use CSVImport\Form\MappingSaveForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MappingSaveFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new MappingSaveForm(null, $options ?? []);

        if (empty($options["job_id"])) {
            throw new \InvalidArgumentException("Missing job_id option.");
        }

        $form->setOption("job_id", $options["job_id"]);
        return $form;
    }
}
