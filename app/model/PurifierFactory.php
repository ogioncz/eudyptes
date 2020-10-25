<?php

namespace App\Model;

use Nette;
use HTMLPurifier;
use HTMLPurifier_Config;

class PurifierFactory {
	use Nette\SmartObject;

	/**
	 * @return HTMLPurifier
	 */
	public function createPurifier(Nette\Application\Application $app, $cacheDir) {
		$config = HTMLPurifier_Config::createDefault();
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
		$config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|w.soundcloud.com/player/|upload.fan-club-penguin.cz/(?!public/))%');

		// Set some HTML5 properties
		$config->set('HTML.DefinitionID', 'html5-definitions'); // unique id
		$config->set('HTML.DefinitionRev', 1);

		if ($def = $config->maybeGetRawHTMLDefinition()) {
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
			$def->addElement('video', 'Block', 'Optional: (source, Flow | #PCDATA) | Flow | #PCDATA', 'Common', [
				'src' => 'URI',
				'type' => 'Text',
				'width' => 'Length',
				'height' => 'Length',
				'poster' => 'URI',
				'preload' => 'Enum#auto,metadata,none',
				'controls' => 'Bool',
				'autoplay' => 'Bool',
				'loop' => 'Bool',
			]);
			$def->addElement('audio', 'Block', 'Optional: (source, Flow | #PCDATA) | Flow | #PCDATA', 'Common', [
				'src' => 'URI',
				'type' => 'Text',
				'preload' => 'Enum#auto,metadata,none',
				'controls' => 'Bool',
				'autoplay' => 'Bool',
				'loop' => 'Bool',
				'muted' => 'Bool',
			]);
			$def->addElement('source', 'Block', 'Empty', 'Common', [
				'src' => 'URI',
				'type' => 'Text',
			]);
			$def->addElement('track', 'Block', 'Empty', 'Common', [
				'src' => 'URI',
				'srclang' => 'Text',
				'kind' => 'Enum#subtitles,captions,descriptions,chapters,metadata',
				'label' => 'Text',
				'default' => 'Bool',
			]);

			// http://developers.whatwg.org/interactive-elements.html#the-details-element
			$def->addElement('details', 'Block', 'Required: (summary, Flow)', 'Common', ['open' => 'Bool']);
			$def->addElement('summary', 'Inline', 'Flow', 'Common');

			// http://developers.whatwg.org/text-level-semantics.html
			$def->addElement('s', 'Inline', 'Inline', 'Common');
			$def->addElement('var', 'Inline', 'Inline', 'Common');
			$def->addElement('sub', 'Inline', 'Inline', 'Common');
			$def->addElement('sup', 'Inline', 'Inline', 'Common');
			$def->addElement('mark', 'Inline', 'Inline', 'Common');
			$def->addElement('wbr', 'Inline', 'Empty', 'Core');

			// https://developers.whatwg.org/the-button-element.html#the-meter-element
			$def->addElement('meter', 'Inline', 'Inline', 'Common', ['value' => 'Length', 'min' => 'Length', 'max' => 'Length', 'low' => 'Length', 'high' => 'Length', 'optimum' => 'Length']);

			// http://developers.whatwg.org/edits.html
			$def->addElement('ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']);
			$def->addElement('del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']);

			// Custom data attributes
			$def->addAttribute('a', 'data-lightbox', 'Enum#true');
			$def->addAttribute('div', 'data-ride', 'Enum#carousel');
			$def->addAttribute('div', 'data-interval', 'Text');
			$def->addAttribute('a', 'data-slide', 'Enum#prev,next');
			$def->addAttribute('blockquote', 'data-from', 'Text');

			$def->addAttribute('a', 'rev', 'Enum#footnote');
			$def->addAttribute('a', 'rel', 'Enum#prev,next');

			$def->addAttribute('img', 'data-content', 'Text');
			$def->addAttribute('img', 'data-title', 'Text');

			// Others
			$def->addAttribute('iframe', 'allowfullscreen', 'Bool');
		}

		$uri = $config->getDefinition('URI');
		$uri->addFilter(new TransformCustomSchemesUriFilter($app), $config);

		return new HTMLPurifier($config);
	}
}
