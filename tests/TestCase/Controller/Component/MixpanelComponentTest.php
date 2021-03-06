<?php

namespace CakephpMixpanel\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Class MixpanelComponentTest
 *
 * @property \Cake\Controller\Controller $Controller
 * @property \CakephpMixpanel\Controller\Component\MixpanelComponent $Mixpanel
 *
 * @package CakephpMixpanel\Test\TestCase\Controller\Component
 */
class MixpanelComponentTest extends TestCase
{
    private $Controller;
    private $Mixpanel;

    public function setUp()
    {
        parent::setUp();

        Configure::write('App', []);
        Configure::write('Mixpanel.token', 'token');

        $this->Controller = new Controller();
        $this->Controller->loadComponent('CakephpMixpanel.Mixpanel');
        $this->Controller->startupProcess();

        $this->Mixpanel = $this->Controller->Mixpanel;
    }

    public function tearDown()
    {
        // dynamically call the Mixpanel::reset() method to clear the queue
        $this->Mixpanel->reset();

        unset($this->Mixpanel);

        parent::tearDown();
    }

    public function testMixpanelLibraryInstance()
    {
        $instance = $this->Mixpanel->getInstance();

        self::assertInstanceOf(\Mixpanel::class, $instance);
        self::assertInstanceOf(\Producers_MixpanelPeople::class, $instance->people);
    }

    public function testTrack()
    {
        $this->Mixpanel->track('test_event', ['number' => 1]);

        $events = (array)$this->Controller->request->session()->read('Mixpanel.events');

        $this->assertCount(1, $events);
        $this->assertEquals('test_event', $events[0]['event']);
        $this->assertEquals(1, $events[0]['properties']['number']);
    }

    public function testTrackWithMetatags()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,pt-BR;q=0.6,pt;q=0.4';

        $this->Mixpanel->config('include_metatags_cli', true);

        $this->Mixpanel->track('test_event', ['number' => 1]);

        $events = (array)$this->Controller->request->session()->read('Mixpanel.events');

        $this->assertArrayHasKey('$browser', $events[0]['properties']);
    }

    public function testRegister()
    {
        $this->Mixpanel->register(['number' => 1]);

        $properties = (array)$this->Controller->request->session()->read('Mixpanel.register');

        $this->assertCount(1, $properties);
        $this->assertEquals(1, $properties['number']);
    }
}
