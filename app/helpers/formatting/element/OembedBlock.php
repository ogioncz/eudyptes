<?php

namespace App\Helpers\Formatting\Element;

use Alb\OEmbed\Response;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\InlineContainer;
use League\CommonMark\Cursor;

class OembedBlock extends AbstractBlock implements InlineContainer {
	/**
	 * @var Response
	 */
	private $response;

	/**
	 * Constructor
	 *
	 * @param Response $response Response of the OEmbed provider
	 */
	public function __construct(Response $response) {
		parent::__construct();

		$this->response = $response;
	}

	/**
	 * Returns the response of the OEmbed provider
	 *
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Returns true if this block can contain the given block as a child node
	 *
	 * @param AbstractBlock $block
	 *
	 * @return bool
	 */
	public function canContain(AbstractBlock $block) {
		return false;
	}

	/**
	 * Returns true if block type can accept lines of text
	 *
	 * @return bool
	 */
	public function acceptsLines() {
		return false;
	}

	/**
	 * Whether this is a code block
	 *
	 * @return bool
	 */
	public function isCode() {
		return false;
	}

	public function matchesNextLine(Cursor $cursor) {
		return false;
	}
}
