<?php

namespace App\Model;

class PurifierFactory extends \Nette\Object {

	/**
	 * @return \HTMLPurifier
	 */
	public function createPurifier($cacheDir) {
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
		$config->set('CSS.AllowTricky', true);
		$config->set('Cache.SerializerPath', $cacheDir);
		$config->set('Cache.DefinitionImpl', null); // TODO: remove this later!
		$config->set('Core.CollectErrors', true);
		$config->set('Attr.EnableID', true);

		// Allow iframes from:
		// o YouTube.com
		// o Vimeo.com
		// o soundcloud.com
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|w.soundcloud.com/player/)%');

		// Set some HTML5 properties
		$config->set('HTML.DefinitionID', 'html5-definitions'); // unique id
		$config->set('HTML.DefinitionRev', 1);

		if($def = $config->maybeGetRawHTMLDefinition()) {
			// http://developers.whatwg.org/sections.html
			$def->addElement('section', 'Block', 'Flow', 'Common');
			$def->addElement('nav', 'Block', 'Flow', 'Common');
			$def->addElement('article', 'Block', 'Flow', 'Common');
			$def->addElement('aside', 'Block', 'Flow', 'Common');
			$def->addElement('header', 'Block', 'Flow', 'Common');
			$def->addElement('footer', 'Block', 'Flow', 'Common');

			// Content model actually excludes several tags, not modelled here
			$def->addElement('address', 'Block', 'Flow', 'Common');
			$def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');

			// http://developers.whatwg.org/grouping-content.html
			$def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
			$def->addElement('figcaption', 'Inline', 'Flow', 'Common');

			// http://developers.whatwg.org/the-video-element.html#the-video-element
			$def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
				'src' => 'URI',
				'type' => 'Text',
				'width' => 'Length',
				'height' => 'Length',
				'poster' => 'URI',
				'preload' => 'Enum#auto,metadata,none',
				'controls' => 'Bool',
			));
			$def->addElement('source', 'Block', 'Flow', 'Common', array(
				'src' => 'URI',
				'type' => 'Text',
			));

			// http://developers.whatwg.org/text-level-semantics.html
			$def->addElement('s', 'Inline', 'Inline', 'Common');
			$def->addElement('var', 'Inline', 'Inline', 'Common');
			$def->addElement('sub', 'Inline', 'Inline', 'Common');
			$def->addElement('sup', 'Inline', 'Inline', 'Common');
			$def->addElement('mark', 'Inline', 'Inline', 'Common');
			$def->addElement('wbr', 'Inline', 'Empty', 'Core');

			// http://developers.whatwg.org/edits.html
			$def->addElement('ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));
			$def->addElement('del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));

			// Custom data attributes
			$def->addAttribute('a', 'data-lightbox', 'Bool');
			$def->addAttribute('blockquote', 'data-from', 'Text');

			$def->addAttribute('a', 'rev', 'Enum#footnote');

			// Others
			$def->addAttribute('iframe', 'allowfullscreen', 'Bool');
		}

		return new \HTMLPurifier($config);	
	}
}
