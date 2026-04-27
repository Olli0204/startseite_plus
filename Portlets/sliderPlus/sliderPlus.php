<?php declare(strict_types=1);
namespace Plugin\startseite_plus\Portlets\sliderPlus;

use JTL\OPC\Portlet;

class sliderPlus extends Portlet {

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-images');
    }

    public function getPropertyDesc(): array
    {
        return [
            'name'   => [
                'label'   => __('Name'),
                'type'    => 'text',
            ],
            'color' => [
                'label'   => __('Farbe für Hover-Effekt'),
                'type'    => 'color',
            ],
            'slides' => [
                'label'     => \__('images'),
                'type'      => 'startseite_plus.image-set-button',
                'default'   => [],
                'useTitles' => true,
                'useLinks'  => true,
                'useButton' => true,
            ],
        ];
    }

    public function getPropertyTabs(): array
    {
        return [
            __('Slides') => ['slides'],
        ];
    }

}
