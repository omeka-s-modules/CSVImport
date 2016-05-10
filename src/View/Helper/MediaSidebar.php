<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MediaSidebar extends AbstractHelper
{

    protected $mediaIngester;

    public function __construct($mediaIngestManager)
    {
        $this->mediaIngester = $mediaIngestManager;
    }

    public function __invoke()
    {
        $mediaForms = [];
        foreach ($this->mediaIngester->getRegisteredNames() as $ingester) {
            if ($ingester != 'upload') {
                $mediaForms[$ingester] = [
                    'label' => $this->mediaIngester->get($ingester)->getLabel(),
                ];
            }
        }

        $html = "
            <div id='media-import' class='sidebar flags'>
                <a href='#' class='sidebar-close o-icon-close'><span class='screen-reader-text'>Close Me</span></a>
                <h3>Media Importing: </h3>
                <ul>";

        foreach($mediaForms as $type=>$mediaForm) {
            $html .= "<li data-flag='$type' data-flag-type='media'>
                <a href='#' class='button'><span>{$mediaForm['label']}</span></a>
            </li>";
        }

        $html .= "</ul>";
        
        $html .= "<div class='media-mapping options'>";
        $html .= "<h4>Options</h4>";
        $html .= "<a href='#' class='button column-multivalue'><span>Use multivalue separator</span></a>";
        $html .= "</div>";
        
        $html .= "</div>";
        return $html;

    }
}