<?php

/**
 * Zend Server 8 Z-Ray Extension for Joomla!
 *
 * @author    Jisse Reitsma (jisse@yireo.com)
 * @copyright Copyright 2015
 * @license   Zend Server License
 * @link      https://www.yireo.com/software/joomla/zray
 * @version   0.2.5
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
        $storage['config'] = $this->getConfig();
        $storage['modules'] = $this->getModules();
        $storage['events'] = $this->getEvents();
        $storage['plugins'] = $this->getPlugins();
        $storage['files'] = $this->getFiles();
    }

    /**
     * Method called before a specific plugin oject method has been called
     *
     * @param array $context
     * @param array $storage
     */
    public function beforePluginObjectCall($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (!isset($arguments[0][0])) {
            return;
        }

        $plugin = $context['this'];
        $pluginClass = get_class($plugin);

        if(isset($arguments[0]['event'])) {
            $method = $arguments[0]['event'];
        } elseif(isset($context['locals']['event'])) {
            $method = $context['locals']['event'];
        } else {
            return;
        }

        $hash = md5(strtolower($pluginClass.':'.$method));
        if (isset($this->joomlaPlugins[$hash])) {
            $this->joomlaPlugins[$hash]['type'] = 'Object-based observer';
            return;
        }

        $this->joomlaPlugins[$hash] = array(
            'type' => 'Object-based observer',
            'class' => $pluginClass,
            'method' => $method,
            'timer.start' => microtime(true),
        );
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
            return;
        }

        $plugin = $context['this'];
        $pluginClass = get_class($plugin);
 
        if(isset($arguments[0]['event'])) {
            $method = $arguments[0]['event'];
        } elseif(isset($context['locals']['event'])) {
            $method = $context['locals']['event'];
        } else {
            return;
        }

        $hash = md5(strtolower($pluginClass.':'.$method));

        if (!isset($this->joomlaPlugins[$hash])) {
            return;
        }

        $this->joomlaPlugins[$hash]['timer.end'] = microtime(true);
        $this->joomlaPlugins[$hash]['timer.total'] = $this->joomlaPlugins[$hash]['timer.end'] - $this->joomlaPlugins[$hash]['timer.start'];
    }

    /**
     * Method called before a specific plugin function method has been called
     *
     * @param array $context
     * @param array $storage
     */
    public function beforePluginFunctionCall($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (!isset($arguments[0][0])) {
            return;
        }

        $object = $arguments[0][0];
        if(is_object($object) && $object instanceof JPlugin) {
            $pluginClass = get_class($object);
        } elseif(is_string($object) && preg_match('/^plg/i', $object)) {
            $pluginClass = $object;
        } else {
            return;
        }

        $method = $arguments[0][1];

        $hash = md5(strtolower($pluginClass.':'.$method));
        if (isset($this->joomlaPlugins[$hash])) {
            return;
        }

        $this->joomlaPlugins[$hash] = array(
            'type' => 'Function-based observer',
            'class' => $pluginClass,
            'method' => $method,
            'timer.start' => microtime(true),
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
            return;
        }

        $object = $arguments[0][0];
        if(is_object($object) && $object instanceof JPlugin) {
            $pluginClass = get_class($object);
        } elseif(is_string($object) && preg_match('/^plg/i', $object)) {
            $pluginClass = $object;
        } else {
            return;
        }

        $method = $arguments[0][1];

        $hash = md5(strtolower($pluginClass.':'.$method));

        if (!isset($this->joomlaPlugins[$hash])) {
            return;
        }

        $this->joomlaPlugins[$hash]['timer.end'] = microtime(true);
        $this->joomlaPlugins[$hash]['timer.total'] = $this->joomlaPlugins[$hash]['timer.end'] - $this->joomlaPlugins[$hash]['timer.start'];
    }

    /**
     * Method called before a specific event has been dispatched
     *
     * @param array $context
     * @param array $storage
     */
    public function beforeEventTrigger($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (empty($arguments)) {
            return;
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
                'timer.start' => microtime(true),
                'count' => 1,
                'argument1' => $argument1,
                'argument2' => $argument2,
                'argument3' => $argument3,
                'arguments' => $arguments,
            );

        } else {
            $this->joomlaEvents[$eventHash]['count']++;
        }
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
            return;
        }

        $eventHash = md5($this->convertToString($arguments));

        if(!isset($this->joomlaEvents[$eventHash])) {
            return;
        }

        $this->joomlaEvents[$eventHash]['timer.end'] = microtime(true);
        $this->joomlaEvents[$eventHash]['timer.total'] = $this->joomlaEvents[$eventHash]['timer.end'] - $this->joomlaEvents[$eventHash]['timer.start'];
    }

    /**
     * Method called before a specific module has been rendered
     *
     * @param array $context
     * @param array $storage
     */
    public function beforeModuleRender($context, &$storage)
    {
        $arguments = $context['functionArgs'];
        if (empty($arguments)) {
            return;
        }

        $module = $arguments[0];
        if (empty($module) || !is_object($module)) {
            return;
        }

        $params = json_decode($module->params, true);
        $cache = (isset($params['cache']) && $params['cache'] == 1) ? true : false;

        $this->joomlaModules[$module->id] = array(
            'id' => $module->id,
            'module' => $module->module,
            'position' => $module->position,
            'params' => $params,
            'cache' => $cache,
            'timer.start' => microtime(true),
        );
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
        if (empty($arguments)) {
            return;
        }

        $module = $arguments[0];
        if (empty($module) || !is_object($module)) {
            return;
        }

        if (empty($this->joomlaModules[$module->id]))
        {
            return;
        }

        $this->joomlaModules[$module->id]['timer.end'] = microtime(true);
        $this->joomlaModules[$module->id]['timer.total'] = $this->joomlaModules[$module->id]['timer.end'] - $this->joomlaModules[$module->id]['timer.start'];
        $this->joomlaModules[$module->id]['title'] = $module->title;
        $this->joomlaModules[$module->id]['content'] = $module->content;
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
            return;
        }

        $path = $context['returnValue'];
        $joomlaPath = str_replace(JPATH_ROOT, '', $path);
        $this->joomlaPathFiles[] = array(
            'Relative Path' => $joomlaPath,
            'Absolute Path' => $path,
        );
    }

    /**
     * Method to return an array representing the current Joomla configuration
     *
     * @return array
     */
    private function getConfig()
    {
        $app = JFactory::getApplication();
        $config = JFactory::getConfig();

        $version = null;
        if (!class_exists('JVersion')) {
            jimport('joomla.version.version');
        }

        if (class_exists('JVersion')) {
            $jversion = new JVersion();
            $version = $jversion->getLongVersion();
        }

        $data = array(
            array('Key' => 'Joomla Version', 'Value' => $version),
            array('Key' => 'Joomla Template', 'Value' => $app->getTemplate()),
            array('Key' => 'Caching', 'Value' => $config->get('caching')),
            array('Key' => 'Cache handler', 'Value' => $config->get('cache_handler')),
            array('Key' => 'Cache time', 'Value' => $config->get('cachetime')),
            array('Key' => 'Session handler', 'Value' => $config->get('session_handler')),
            array('Key' => 'SEF', 'Value' => $config->get('sef')),
            array('Key' => 'SEF Rewrites', 'Value' => $config->get('sef_rewrite')),
            array('Key' => 'Debug', 'Value' => $config->get('debug')),
            array('Key' => 'Gzip', 'Value' => $config->get('gzip')),
            array('Key' => 'Error reporting', 'Value' => $config->get('error_reporting')),
            array('Key' => 'DB type', 'Value' => $config->get('dbtype')),
            array('Key' => 'DB host', 'Value' => $config->get('host')),
            array('Key' => 'DB database', 'Value' => $config->get('db')),
            array('Key' => 'Offset', 'Value' => $config->get('offset')),
            array('Key' => 'Mailer', 'Value' => $config->get('mailer')),
        );

        $template = $app->getTemplate(true);
        $params = $template->params;
        foreach($params as $name => $value) {
            $data[] = array(
                'Key' => 'Template Parameter: '.$name,
                'Value' => $value,
            );
        }

        return $data;
    }

    /**
     * Method to return an array representing the current HTTP request
     *
     * @return array
     */
    private function getRequest()
    {
        $app = JFactory::getApplication();
        $input = $app->input;

        $request = array(
            'option' => array('Key' => 'Component', 'Value' => $input->get('option')),
            'view' => array('Key' => 'View', 'Value' => $input->get('view')),
            'layout' => array('Key' => 'Layout', 'Value' => $input->get('layout', 'default')),
            'id' => array('Key' => 'ID', 'Value' => $input->get('id')),
            'Itemid' => array('Key' => 'Itemid', 'Value' => $input->get('Itemid')),
        );

        // Dump other variables
        foreach ($_REQUEST as $name => $value) {
            if(isset($request[$name])) {
                continue;
            }

            $request[$name] = array('Key' => $name, 'Value' => $value);
        }

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
            $module['Cached'] = ($joomlaModule['cache']) ? 'Yes' : 'No';
            $module['ID'] = $joomlaModule['id'];
            $module['Time'] = $this->formatTime($joomlaModule['timer.total']);
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

            if(!isset($joomlaPlugin['timer.total'])) {
                print_r($joomlaPlugin);
            }

            $plugins[] = array(
                'Type' => $joomlaPlugin['type'],
                'Class' => $joomlaPlugin['class'],
                'Method' => $joomlaPlugin['method'],
                'Time' => $this->formatTime($joomlaPlugin['timer.total']),
            );
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

            $time = (isset($joomlaEvent['timer.total'])) ? $this->formatTime($joomlaEvent['timer.total']) : null;

            $events[] = array(
                'Event' => $joomlaEvent['event'],
                'Time' => $time,
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

            $array = array();
            foreach($variable as $name => $value) {
                $value = $this->convertToString($value);
                $array[$name] = $value;
            }

            return var_export($array, true);
        }

        if (is_object($variable)) {
            return '['.get_class($variable).']';
        }

        return $variable;
    }

    /**
     * Helper method to get the timer 
     *
     * @param mixed $variable
     * @return mixed
     */
    private function formatTime($seconds)
    {
        return number_format((float)$seconds * 1000, 2).' ms';
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
$zrayJoomla->getZRay()->traceFunction('JDispatcher::trigger', array($zrayJoomla, 'beforeEventTrigger'), array($zrayJoomla, 'afterEventTrigger'));
$zrayJoomla->getZRay()->traceFunction('JEventDispatcher::trigger', array($zrayJoomla, 'beforeEventTrigger'), array($zrayJoomla, 'afterEventTrigger'));
$zrayJoomla->getZRay()->traceFunction('call_user_func_array', array($zrayJoomla, 'beforePluginFunctionCall'), array($zrayJoomla, 'afterPluginFunctionCall'));
$zrayJoomla->getZRay()->traceFunction('JEvent::update', array($zrayJoomla, 'beforePluginObjectCall'), array($zrayJoomla, 'afterPluginObjectCall'));
$zrayJoomla->getZRay()->traceFunction('JModuleHelper::renderModule', array($zrayJoomla, 'beforeModuleRender'), array($zrayJoomla, 'afterModuleRender'));
$zrayJoomla->getZRay()->traceFunction('JDocument::render', function(){}, array($zrayJoomla, 'afterDocumentRender'));
