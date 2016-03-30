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
            <div id='media-sidebar' class='sidebar flags'>
                <legend>Media Importing</legend>
                <ul>";
        
        foreach($mediaForms as $type=>$mediaForm) {
            $html .= "<li data-flag='$type' data-flag-type='media'>
                <a href='#' class='button'><span>{$mediaForm['label']}</span></a>
            </li>";
        }
       
        $html .= "</ul></div>";
        return $html;
        
    }
}