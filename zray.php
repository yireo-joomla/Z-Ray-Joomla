<?php
/*********************************
    Joomla Z-Ray Extension
    Version: 0.0.1
**********************************/
class Joomla
{
    private $request = array();

    private $zray = null;
    
    public function setZRay($zray)
    {
        $this->zray = $zray;
    }

    /**
     * @return \ZRayExtension
     */
    public function getZRay()
    {
        return $this->zray;
    }

    public function onGetApplication()
    {
        $app = JFactory::getApplication();
        $this->request = array(
            'option' => $app->input->get('option'),
            'view' => $app->input->get('view'),
            'layout' => $app->input->get('layout'),
            'id' => $app->input->get('id'),
        );
        
        //$this->getZRay()->untraceFunction('JFactory::getApplication');
    }
}

$zrayJoomla = new Joomla();
$zrayJoomla->setZRay(new ZRayExtension('joomla'));
$zrayJoomla->getZRay()->setMetadata(array(
    'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));
$zrayJoomla->getZRay()->setEnabledAfter('JFactory::getApplication');
//$zrayJoomla->getZRay()->traceFunction('JFactory::getApplication', function(){}, array($zrayJoomla, 'onGetApplication'));

