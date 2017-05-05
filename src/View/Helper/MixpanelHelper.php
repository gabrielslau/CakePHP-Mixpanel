<?php

namespace CakephpMixpanel\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

class MixpanelHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * {@inheritDoc}
     * @throws \Cake\Core\Exception\Exception
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setConfig(
            'templates.script',
            '<!-- start Mixpanel --><script type="text/javascript">(function(e,a){if(!a.__SV){var b=window;try{var c,l,i,j=b.location,g=j.hash;c=function(a,b){return(l=a.match(RegExp(b+"=([^&]*)")))?l[1]:null};g&&c(g,"state")&&(i=JSON.parse(decodeURIComponent(c(g,"state"))),"mpeditor"===i.action&&(b.sessionStorage.setItem("_mpcehash",g),history.replaceState(i.desiredHash||"",e.title,j.pathname+j.search)))}catch(m){}var k,h;window.mixpanel=a;a._i=[];a.init=function(b,c,f){function e(b,a){var c=a.split(".");2==c.length&&(b=b[c[0]],a=c[1]);b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments, 0)))}}var d=a;"undefined"!==typeof f?d=a[f]=[]:f="mixpanel";d.people=d.people||[];d.toString=function(b){var a="mixpanel";"mixpanel"!==f&&(a+="."+f);b||(a+=" (stub)");return a};d.people.toString=function(){return d.toString(1)+".people (stub)"};k="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");for(h=0;h<k.length;h++)e(d,k[h]);a._i.push([b,c,f])};a.__SV=1.2;b=e.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";c=e.getElementsByTagName("script")[0];c.parentNode.insertBefore(b,c)}})(document,window.mixpanel||[]);mixpanel.init({{token}});{{trackers}}</script><!-- end Mixpanel -->'
        );
    }

    public function embed()
    {
        $settings = Configure::read('Mixpanel.settings');
        $events = Configure::read('Mixpanel.events');
        $register = Configure::read('Mixpanel.register');

        $trackers = [];

        // Integration
        if (Configure::read('debug')) {
            $trackers[] = 'mixpanel.set_config({debug: true});';
        }
        if (isset($settings['identify'])) {
            $trackers[] = sprintf('mixpanel.identify(%s);', json_encode($settings['identify']));
        }
        if (isset($settings['name_tag'])) {
            $trackers[] = sprintf('mixpanel.name_tag(%s);', json_encode($settings['name_tag']));
        }
        if (is_array($register)) {
            $trackers[] = sprintf('mixpanel.register(%s);', json_encode($register));
        }

        if (!empty($events)) {
            foreach ($events as $event) {
                $properties = $event['properties'];
                $properties = array_merge($settings['properties'], $properties);

                $trackers[] = sprintf(
                    'mixpanel.track(%s, %s);',
                    json_encode($event['event']),
                    (!empty($properties)) ? json_encode($properties) : '{}'
                );
            }
        }

        // People
        if (isset($settings['people'])) {
            $trackers[] = sprintf('mixpanel.people.identify(%s);', json_encode($settings['people']['identify']));
            $trackers[] = sprintf('mixpanel.people.set(%s);', json_encode($settings['people']['set']));
        }

        return $this->formatTemplate('script', [
            'token' => json_encode($settings['token']),
            'trackers' => implode("\n", $trackers)
        ]);
    }
}
