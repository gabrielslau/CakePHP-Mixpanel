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

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->config([
            'token' => Configure::read('Mixpanel.token'),
            'options' => (array)Configure::read('Mixpanel.options'),
            'properties' => [],
        ]);

        if (!$this->request->session()->check('Mixpanel.events')) {
            $this->request->session()->write('Mixpanel.events', []);
        }

        $this->Mixpanel = Mixpanel::getInstance($this->config('token'), $this->config('options'));
    }

    public function beforeRender(Event $event)
    {
        Configure::write('Mixpanel.events', $this->request->session()->read('Mixpanel.events'));
        Configure::write('Mixpanel.register', $this->request->session()->read('Mixpanel.register'));
        Configure::write('Mixpanel.settings', $this->config());
        $this->request->session()->delete('Mixpanel.events');
        $this->request->session()->delete('Mixpanel.register');
    }

    /**
     * @return \Mixpanel
     */
    public function getInstance()
    {
        return $this->Mixpanel;
    }

    public function identify($id)
    {
        $this->Mixpanel->identify($id);
        $this->config('id', $id);
    }

    public function name_tag($name)
    {
        $this->config('name_tag', $name);
    }

    public function people($id, array $properties = [])
    {
        $this->Mixpanel->people->set($id, $properties);
        $this->config('people.identify', $id);
        $this->config('people.set', $properties);
    }

    /**
     * Register new properties using mixpanel.register(), accepts a key => value array of properties
     * Sending a key => value with a duplicate key replaces the old value
     *
     * @param array $properties Array of key => value properties to register
     *
     * @return void
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

        $this->Mixpanel->registerAll($properties);
    }

    public function track($event, array $properties = [])
    {
        // send event to mixpanel
        $this->Mixpanel->track($event, $properties);

        // configure Mixpanel events to render in javascript embed script
        $events = $this->request->session()->read('Mixpanel.events');
        $events[] = compact('event', 'properties');
        $this->request->session()->write('Mixpanel.events', $events);
    }

    /**
     * Handles behavior delegation + dynamic finders.
     *
     * If your Table uses any behaviors you can call them as if
     * they were on the table object.
     *
     * @param string $method name of the method to be invoked
     * @param array  $args   List of arguments passed to the function
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (method_exists($this->Mixpanel, $method)) {
            return call_user_func_array([$this->Mixpanel, $method], $args);
        }

        throw new \BadMethodCallException(
            sprintf('Unknown method "%s"', $method)
        );
    }
}
