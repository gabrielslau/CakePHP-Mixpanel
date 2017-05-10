<?php

namespace CakephpMixpanel\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Mixpanel;

class MixpanelComponent extends Component
{
    /**
     * @var \Mixpanel
     */
    private $Mixpanel;

    public function startup(Event $event)
    {
        $this->setupInitialConfiguration($event->subject());
    }

    /**
     * Load the API into a class property and allow access to it.
     *
     * @throws \InvalidArgumentException
     * @throws \Cake\Core\Exception\Exception
     */
    public function setupInitialConfiguration($controller)
    {
        $this->config([
            'token' => Configure::read('Mixpanel.token'),
            'options' => (array)Configure::read('Mixpanel.options'),
            'properties' => [],
        ]);

        /** @var \Cake\Network\Session $session */
        $session = $controller->request->session();

        if (!$session->check('Mixpanel.events')) {
            $session->write('Mixpanel.events', []);
        }

        $this->Mixpanel = Mixpanel::getInstance($this->config('token'), $this->config('options'));
    }

    public function beforeRender(Event $event)
    {
        /** @var \Cake\Network\Session $session */
        $session = $event->subject()->request->session();

        Configure::write('Mixpanel.events', $session->read('Mixpanel.events'));
        Configure::write('Mixpanel.register', $session->read('Mixpanel.register'));
        Configure::write('Mixpanel.settings', $this->config());
        $session->delete('Mixpanel.events');
        $session->delete('Mixpanel.register');
    }

    /**
     * @return \Mixpanel
     * @throws \InvalidArgumentException
     * @throws \Cake\Core\Exception\Exception
     */
    public function getInstance()
    {
        if ($this->Mixpanel === null) {
            $this->setupInitialConfiguration($this->_registry->getController());
        }

        return $this->Mixpanel;
    }

    public function identify($id)
    {
        $this->getInstance()->identify($id);
        $this->config('identify', $id);
    }

    public function name_tag($name)
    {
        $this->config('name_tag', $name);
    }

    public function people($id, array $properties = [])
    {
        $this->getInstance()->people->set($id, $properties);
        $this->config('people.set', $properties);
    }

    /**
     * Register new properties using mixpanel.register(), accepts a key => value array of properties
     * Sending a key => value with a duplicate key replaces the old value
     *
     * @param array $properties Array of key => value properties to register
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Cake\Core\Exception\Exception
     * @author David Kullmann
     */
    public function register(array $properties)
    {
        $register = (array)$this->request->session()->read('Mixpanel.register');
        if (!empty($properties)) {
            foreach ($properties as $key => $value) {
                $register[$key] = $value;
            }
        }
        $this->request->session()->write('Mixpanel.register', $register);

        $this->getInstance()->registerAll($properties);
    }

    public function track($event, array $properties = [])
    {
        // send event to mixpanel
        $this->getInstance()->track($event, $properties);

        // configure Mixpanel events to render in javascript embed script
        $events = $this->request->session()->read('Mixpanel.events');
        $events[] = compact('event', 'properties');
        $this->request->session()->write('Mixpanel.events', $events);
    }

    /**
     * Calls the original methods of the Mixpanel SDK
     *
     * @param string $method name of the method to be invoked
     * @param array  $args   List of arguments passed to the function
     *
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Cake\Core\Exception\Exception
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        $instance = $this->getInstance();

        if (method_exists($instance, $method)) {
            return call_user_func_array([$instance, $method], $args);
        }

        throw new \BadMethodCallException(
            sprintf('Unknown method "%s"', $method)
        );
    }
}
