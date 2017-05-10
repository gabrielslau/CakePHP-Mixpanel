<?php

namespace CakephpMixpanel\Test\TestCase\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakephpMixpanel\View\Helper\MixpanelHelper;

/**
 * Class MixpanelHelperTest
 *
 * @property \Cake\Controller\Controller $Controller
 * @property \CakephpMixpanel\View\Helper\MixpanelHelper $Mixpanel
 *
 * @package CakephpMixpanel\Test\TestCase\View\Helper
 */
class MixpanelHelperTest extends TestCase
{
    /**
     * Helper
     *
     * @var \CakephpMixpanel\View\Helper\MixpanelHelper
     */
    public $Mixpanel;

    /**
     * Mocked view
     *
     * @var \Cake\View\View|\PHPUnit_Framework_MockObject_MockObject
     */
    public $View;

    public function setUp()
    {
        parent::setUp();

        Configure::write('App', []);
        Configure::write('Mixpanel.token', 'token');

        $this->Controller = new Controller();
        $this->Controller->loadComponent('CakephpMixpanel.Mixpanel');
        $this->Controller->startupProcess();

        // setup events to render in javascript
        $this->Controller->Mixpanel->track('test_event', ['number' => 1]);
        $this->Controller->Mixpanel->register(['number' => 1]);
        $this->Controller->Mixpanel->identify('id1');
        $this->Controller->Mixpanel->name_tag('john');
        $this->Controller->Mixpanel->people('id1', [
            'email' => 'john@doe.com'
        ]);

        $this->View = $this->getMockBuilder(View::class)
            ->setMethods(['append'])
            ->getMock();

        $this->Mixpanel = new MixpanelHelper($this->View);
    }

    /**
     * End Test
     *
     * @return void
     */
    public function tearDown()
    {
        $this->Controller->Mixpanel->reset();

        unset(
            $this->Mixpanel,
            $this->View
        );

        parent::tearDown();
    }

    public function testEmbed()
    {
        $this->Mixpanel->setConfig(
            'templates.script',
            '<script>mixpanel.init({{token}}); {{trackers}}</script>'
        );

        $this->Controller->dispatchEvent('Controller.beforeRender');

        $embed = $this->Mixpanel->embed();
        $expected = <<<HTML
<script>mixpanel.init("token"); mixpanel.identify("id1");
mixpanel.name_tag("john");
mixpanel.register({"number":1});
mixpanel.track("test_event", {"number":1});
mixpanel.people.set({"email":"john@doe.com"});</script>
HTML;

        self::assertEquals($expected, $embed);
    }
}
