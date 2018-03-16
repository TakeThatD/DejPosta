<?php
class Message {
	public $text;
	public $type;
	const ERROR = 2;
	const WARNING = 1;
	const INFO = 0;
	public function __construct($text,$type){ $this->text = $text; $this->type = $type; }
	public function isError() { return $this->type == Message::ERROR; }
	public function isWarning() { return $this->type == Message::WARNING; }
	public function isInfo() { return $this->type == Message::INFO; }
}
