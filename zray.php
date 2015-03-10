<?php

/**
 * Zend Server 8 Z-Ray Extension for Joomla!
 *
 * @author    Jisse Reitsma (jisse@yireo.com)
 * @copyright Copyright 2015
 * @license   Zend Server License
 * @link      https://www.yireo.com/software/joomla/zray
 * @version   0.2.1
 */
class Joomla
{
	/**
	 * Internal reference to ZRayExtension instance
	 */
	private $zray = null;

	/**
	 * Internal listing of Joomla modules
	 */
	private $joomlaModules = array();

	/**
	 * Internal listing of Joomla plugins
	 */
	private $joomlaPlugins = array();

	/**
	 * Internal listing of Joomla events (triggered by JEventDispatcher)
	 */
	private $joomlaEvents = array();

	/**
	 * Internal listing of Joomla files (triggered by JPath::find)
	 */
    private $joomlaPathFiles = array();

	/**
	 * Method to bind ZRayExtension instance internally
	 *
	 * @param \ZRayExtension $zray
	 */
	public function setZRay($zray)
	{
		$this->zray = $zray;
	}

	/**
	 * Method to return the current ZRayExtension instance
	 *
	 * @return \ZRayExtension
	 */
	public function getZRay()
	{
		return $this->zray;
	}

	/**
	 * Method called after JDocument has rendered
	 *
	 * @param array $context
	 * @param array $storage
	 */
	public function afterDocumentRender($context, &$storage)
	{
		$storage['request'] = $this->getRequest();
		$storage['modules'] = $this->getModules();
		$storage['events'] = $this->getEvents();
        $storage['plugins'] = $this->getPlugins();
        $storage['files'] = $this->getFiles();
	}

	/**
	 * Method called after a specific plugin oject method has been called
	 *
	 * @param array $context
	 * @param array $storage
	 */
    public function afterPluginObjectCall($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (!isset($arguments[0][0])) {
            return false;
        }

        $plugin = $context['this'];
        $pluginClass = get_class($plugin);
        $methodName = $context['locals']['event'];
        $this->joomlaPlugins[] = array(
            'type' => 'Object-based observer',
            'class' => $pluginClass,
            'method' => $methodName,
        );
    }

	/**
	 * Method called after a specific plugin function method has been called
	 *
	 * @param array $context
	 * @param array $storage
	 */
    public function afterPluginFunctionCall($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (!isset($arguments[0][0])) {
            return false;
        }

        $object = $arguments[0][0];
        if(is_object($object) && $object instanceof JPlugin) {
            $pluginClass = get_class($object);
        } elseif(is_string($object) && preg_match('/^plg/i', $object)) {
            $pluginClass = $object;
        } else {
            return false;
        }

        $this->joomlaPlugins[] = array(
            'type' => 'Function-based observer',
            'class' => $pluginClass,
            'method' => $arguments[0][1],
        );
    }

	/**
	 * Method called after a specific event has been dispatched
	 *
	 * @param array $context
	 * @param array $storage
	 */
	public function afterEventTrigger($context, &$storage)
	{
        $arguments = $context['functionArgs'];
        if (empty($arguments)) {
            return false;
        }

        $eventHash = md5($this->convertToString($arguments));
        $eventName = array_shift($arguments);

        $argument1 = null;
        $argument2 = null;
        $argument3 = null;
        if(isset($arguments[0])) {
            $arguments = $arguments[0];
            for($i = 1; $i <= 3; $i++) {
                if(empty($arguments)) break;
                $name = 'argument'.$i;
                $$name = array_shift($arguments);
            }

            if(!empty($arguments)) {
                $arguments = $this->convertToString($arguments);
            } else {
                $arguments = null;
            }
        } else {
            $arguments = null;
        }

        if(!isset($this->joomlaEvents[$eventHash])) {
            $this->joomlaEvents[$eventHash] = array(
                'event' => $eventName,
                'count' => 1,
                'argument1' => $argument1,
                'argument2' => $argument2,
                'argument3' => $argument3,
                'arguments' => $arguments,
            );
        } else {
            $this->joomlaEvents[$eventHash]['count']++;
        }

        return true;
	}

	/**
	 * Method called after a specific module has been rendered
	 *
	 * @param array $context
	 * @param array $storage
	 */
	public function afterModuleRender($context, &$storage)
	{
		$arguments = $context['functionArgs'];
		if (empty($arguments))
		{
			return;
		}

		$module = $arguments[0];
		if (empty($module) || !is_object($module))
		{
			return;
		}

		$this->joomlaModules[] = array(
			'id' => $module->id,
			'title' => $module->title,
			'module' => $module->module,
			'position' => $module->position,
			'content' => $module->content,
		);
	}

	/**
	 * Method called after a specific file has been found through JPath::find()
	 *
	 * @param array $context
	 * @param array $storage
	 */
    public function afterPathFind($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (empty($arguments)) {
            return false;
        }

        $path = $context['returnValue'];
        $joomlaPath = str_replace(JPATH_ROOT, '', $path);
        $this->joomlaPathFiles[] = array(
            'Relative Path' => $joomlaPath,
            'Absolute Path' => $path,
        );
    }

	/**
	 * Method to return an array representing the current HTTP request
	 *
	 * @return array
	 */
	private function getRequest()
	{
		$app = JFactory::getApplication();

		$request = array(
			array('Key' => 'Component', 'Value' => $app->input->get('option')),
			array('Key' => 'View', 'Value' => $app->input->get('view')),
			array('Key' => 'Layout', 'Value' => $app->input->get('layout', 'default')),
			array('Key' => 'ID', 'Value' => $app->input->get('id')),
		);

		return $request;
	}

	/**
	 * Method to return an array of all found files
	 *
	 * @return array
	 */
    private function getFiles()
    {
        $templateFiles = array();
        foreach($this->joomlaPathFiles as $file) {
            $templateFiles[] = $file;
        }

        return $templateFiles;
    }

	/**
	 * Method to return an array representing all rendered modules
	 *
	 * @return array
	 */
	private function getModules()
	{
        $modules = array();
        foreach($this->joomlaModules as $joomlaModule) {

            $module = array();
            $module['Title'] = $joomlaModule['title'];
            $module['Module'] = $joomlaModule['module'];
            $module['Position'] = $joomlaModule['position'];
            $module['ID'] = $joomlaModule['id'];
            $module['Content'] = (empty($joomlaModule['content'])) ? 'No content' : $joomlaModule['content'];

            $modules[] = $module;
        }

        return $modules;
	}

	/**
	 * Method to return an array representing all loaded plugins
	 *
	 * @return array
	 */
    private function getPlugins()
    {
        $plugins = array();
        foreach($this->joomlaPlugins as $joomlaPlugin) {
            $plugins[] = $joomlaPlugin;
        }

        return $plugins;
    }

	/**
	 * Method to return an array representing all triggered events
	 *
	 * @return array
	 */
	private function getEvents()
	{
        $events = array();
        foreach($this->joomlaEvents as $joomlaEvent) {
            $events[] = array(
                'Event' => $joomlaEvent['event'],
                'Occurances' => $joomlaEvent['count'],
                'Argument 1' => $this->convertToString($joomlaEvent['argument1']),
                'Argument 2' => $this->convertToString($joomlaEvent['argument2']),
                'Argument 3' => $this->convertToString($joomlaEvent['argument3']),
                'Other arguments' => $this->convertToString($joomlaEvent['arguments']),
            );
        }

		return $events;
	}

    /**
     * Helper method to dump a variable to a string
     *
     * @param mixed $variable
     * @return mixed
     */
    private function convertToString($variable)
    {
        if (is_array($variable)) {
            foreach($variable as $name => $value) {
                $value = $this->convertToString($value);
                $variable[$name] = $value;
            }

            return var_export($variable, true);
        }

        if (is_object($variable)) {
            return '['.get_class($variable).']';
        }

        return $variable;
    }
}

// Initialize this ZRay extension
$zrayJoomla = new Joomla();
$zrayJoomla->setZRay(new ZRayExtension('joomla'));
$zrayJoomla->getZRay()->setMetadata(array(
	'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));

// Enable only after JApplication has been fetched
$zrayJoomla->getZRay()->setEnabledAfter('JFactory::getApplication');

// Trace functions
$zrayJoomla->getZRay()->traceFunction('JPath::find', function(){}, array($zrayJoomla, 'afterPathFind'));
$zrayJoomla->getZRay()->traceFunction('JDispatcher::trigger', function(){}, array($zrayJoomla, 'afterEventTrigger'));
$zrayJoomla->getZRay()->traceFunction('JEventDispatcher::trigger', function(){}, array($zrayJoomla, 'afterEventTrigger'));
$zrayJoomla->getZRay()->traceFunction('call_user_func_array', function(){}, array($zrayJoomla, 'afterPluginFunctionCall'));
$zrayJoomla->getZRay()->traceFunction('JEvent::update', function(){}, array($zrayJoomla, 'afterPluginObjectCall'));
$zrayJoomla->getZRay()->traceFunction('JModuleHelper::renderModule', function(){}, array($zrayJoomla, 'afterModuleRender'));
$zrayJoomla->getZRay()->traceFunction('JDocument::render', function(){}, array($zrayJoomla, 'afterDocumentRender'));
