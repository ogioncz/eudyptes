<?php

namespace App\Helpers\Formatting\Element;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\InlineContainerInterface;
use League\CommonMark\Cursor;

class ChatQuote extends AbstractBlock implements InlineContainerInterface {
	/** @var int */
	private $id;

	/**
	 * Constructor
	 *
	 * @param int $id ID of the quoted chat message
	 */
	public function __construct($id) {
		parent::__construct();

		$this->id = $id;
	}

	/**
	 * Returns the ID of the quoted chat message
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
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
