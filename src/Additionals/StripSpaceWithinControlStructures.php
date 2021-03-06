<?php
final class StripSpaceWithinControlStructures extends AdditionalPass {

	public function candidate($source, $foundTokens) {

		if (
			isset($foundTokens[T_IF]) ||
			isset($foundTokens[T_DO]) ||
			isset($foundTokens[T_WHILE]) ||
			isset($foundTokens[T_FOR]) ||
			isset($foundTokens[T_FOREACH]) ||
			isset($foundTokens[T_SWITCH])
		) {
			return true;
		}

		return false;
	}

	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;

			switch ($id) {
			case T_IF:
			case T_DO:
			case T_FOR:
			case T_FOREACH:
			case T_SWITCH:
				$this->appendCode($text);
				$this->printUntil(ST_PARENTHESES_OPEN);
				$this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
				$this->printUntil(ST_CURLY_OPEN);

				if ($this->hasLnAfter()) {
					each($this->tkns);
					$this->appendCode($this->newLine);
					continue;
				}

				break;

			case T_WHILE:
				$this->appendCode($this->newLine);
				$this->appendCode($text);
				$this->printUntil(ST_PARENTHESES_OPEN);
				$this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);

				if ($this->rightUsefulTokenIs(ST_CURLY_OPEN)) {
					$this->printUntil(ST_CURLY_OPEN);

					if ($this->hasLnAfter()) {
						each($this->tkns);
						$this->appendCode($this->newLine);
						continue;
					}

				}

				break;

			case ST_CURLY_CLOSE:

				if ($this->hasLnBefore()) {
					$this->rtrimAndAppendCode($this->newLine . $text);
					continue;
				}

				$this->appendCode($text);
				break;

			default:
				$this->appendCode($text);
				break;
			}

		}

		return $this->code;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getDescription() {
		return 'Strip empty lines within control structures.';
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getExample() {
		return <<<'EOT'
<?php
// From
for ($a = 0; $a < 10; $a++){

	if($a){

		// do something
	}

}
// To
for ($a = 0; $a < 10; $a++){
	if($a){
		// do something
	}
}
?>
EOT;
	}

}
