<?php

namespace TgUtils;

class DateRange {

	protected $from;
	protected $until;

	public function __construct($from = NULL, $until = NULL) {
		$this->from  = $from;
		$this->until = $until;
	}

	public function setFrom($value) {
		$this->from = $value;
		$this->checkRange();
		return $this;
	}

	public function getFrom() {
		return $this->from;
	}

	public function setUntil($value) {
		$this->until = $value;
		$this->checkRange();
		return $this;
	}

	public function getUntil() {
		return $this->until;
	}

	public function set($from, $until) {
		$this->from  = $from;
		$this->until = $until;
		$this->checkRange();
		return $this;
	}

	protected function checkRange() {
		if (($this->from != NULL) && ($this->until != NULL)) {
			if ($this->from->toUnix() > $this->until->toUnix()) {
				$foo = $this->until;
				$this->until = $this->from;
				$this->from  = $foo;
			}
		}
	}

	public function __toString() {
		$rc  = ($this->from  != NULL) ? $this->from->toMySql(TRUE)  : 'N/A';
		$rc .= ' - ';
		$rc .= ($this->until != NULL) ? $this->until->toMySql(TRUE) : 'N/A';
		return $rc;
	}
}

