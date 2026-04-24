<?php declare(strict_types=1);
namespace Plugin\startseite_plus\Portlets\PictureBox;

use JTL\OPC\Portlet;

class pictureBox extends Portlet {


    public function getButtonHtml(): string{
        return $this->getFontAwesomeButtonHtml('far fa-object-group');
    }
    
    public function getPropertyDesc(): array{
    return [
            'slides'   => [
                'label'   => __('Bilder'),
                'type'    => 'startseite_plus.image-set-button',
                'default' => [],
                'useTitles'=> true,
                'useLinks3'=> true,
                'useKat'  => true,
                'useKat2' => true,
                'useLinks2' => true,
            ],
        ];
    }

    public function getPropertyTabs(): array{
        return [
            __('Slides') => ['slides'],
        ];
    }

}