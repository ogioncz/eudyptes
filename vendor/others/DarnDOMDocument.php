<?php

namespace Ogion\Utils;

use DOMDocument;
use DOMNode;

class DarnDOMDocument extends DOMDocument {
	public function __toString() {
		return $this->saveHTMLExact();
	}

	public function loadHTML(string $html, int $options = 0): bool {
		// $options |= LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD; // don’t wrap document fragments

		libxml_use_internal_errors(true); // prevent warning when using html5 tags

		$convmap = [
			0x80, 0x1FFFFF, 0, 0x10FFFF,
		];
		$html = mb_encode_numericentity($html, $convmap, 'utf8', true);
		$dom = parent::loadHTML($html, $options);

		libxml_use_internal_errors(false);

		return $dom;
	}

	public function saveHTML(?DOMNode $element = null): string|false {
		return preg_replace('~^<body[^>]*>(.*)</body>$~s', '$1', parent::saveHTML($this->getElementsByTagName('body')->item(0)));
	}
}

