<?php declare(strict_types=1);
namespace Plugin\startseite_plus\Portlets\HeadingPlus;

use JTL\OPC\Portlet;

class headingPlus extends Portlet {

    public function getButtonHtml(): string{
        return $this->getFontAwesomeButtonHtml('fas fa-heading');
    }
    
    public function getPropertyDesc(): array{
    return [
            'name'   => [
                'label'   => __('Name'),
                'type'    => 'text',
            ],
            'color'  => [
                'label'   => __('Textfarbe bei Hover'),
                'type'    => 'color',
            ],
        ];
    }

}