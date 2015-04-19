<?php
namespace Joomla;

class Module extends \ZRay\ZRayModule {
    
    public function config() {
        return array(
            'extension' => array(
                'name' => 'joomla',
            ),
            'defaultPanels' => array(
                '1-request' => false,
                '2-config' => false,
                '3-modules' => false,
                '4-events' => false,
                '5-plugins' => false,
                '6-files' => false,
             ),
            'panels' => array(
                '1-request' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Request',
                    'panelTitle'    => 'Request',
                ),
                '2-config' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Configuration',
                    'panelTitle'    => 'Configuration',
                ),
                '3-config' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Modules',
                    'panelTitle'    => 'Modules',
                ),
                '4-events' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Events',
                    'panelTitle'    => 'Events',
                ),
                '5-plugins' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Plugins',
                    'panelTitle'    => 'Plugins',
                ),
                '6-files' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle'     => 'Files',
                    'panelTitle'    => 'Files',
                ),
             )
        );
    }   
}
