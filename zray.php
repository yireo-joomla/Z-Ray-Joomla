<?php

/**
 * Zend Server 8 Z-Ray Extension for Joomla!
 *
 * @author    Jisse Reitsma (jisse@yireo.com)
 * @copyright Copyright 2015
 * @license   Zend Server License
 * @link      https://www.yireo.com/software/joomla/zray
 * @version   0.1.0
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
	 * Internal listing of Joomla events (triggered by JEventDispatcher)
	 */
	private $joomlaEvents = array();

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
		if (empty($arguments))
		{
			return;
		}

		$event = $arguments[0];
		if (isset($this->joomlaEvents[$event]))
		{
			$this->joomlaEvents[$event]++;
		} else
		{
			$this->joomlaEvents[$event] = 1;
		}
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
	 * Method to return an array representing all rendered modules
	 *
	 * @return array
	 */
	private function getModules()
	{
		$modules = array();
		foreach ($this->joomlaModules as $joomlaModule)
		{
			$moduleKey = $joomlaModule['title'];
			$moduleValue = array();
			$moduleValue[] = $joomlaModule['module'];
			$moduleValue[] = $joomlaModule['position'];
			$moduleValue[] = 'ID ' . $joomlaModule['id'];
			$moduleValue[] = (empty($joomlaModule['content'])) ? 'No content' : 'Content';

			$modules[] = array('Key' => $moduleKey, 'Value' => implode(' | ', $moduleValue));
		}

		return $modules;
	}

	/**
	 * Method to return an array representing all triggered events
	 *
	 * @return array
	 */
	private function getEvents()
	{
		$events = array();
		foreach ($this->joomlaEvents as $joomlaEvent => $joomlaEventCount)
		{
			$events[] = array('Key' => $joomlaEvent, 'Value' => 'Occurances: ' . $joomlaEventCount);
		}

		return $events;
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
$zrayJoomla->getZRay()->traceFunction('JEventDispatcher::trigger', function () {}, array($zrayJoomla, 'afterEventTrigger'));
$zrayJoomla->getZRay()->traceFunction('JModuleHelper::renderModule', function () {}, array($zrayJoomla, 'afterModuleRender'));
$zrayJoomla->getZRay()->traceFunction('JDocument::render', function () {}, array($zrayJoomla, 'afterDocumentRender'));

