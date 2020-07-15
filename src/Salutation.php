<?php

declare(strict_types=1);

namespace Baraja\CzechNameSalutation;


final class Salutation
{

	/** @var mixed[]|null */
	private $manSuffixes;

	/** @var mixed[]|null */
	private $manVsWomanSuffixes;

	/** @var mixed[]|null */
	private $womanFirstVsLastSuffixes;


	/**
	 * Vrací jméno vyskloňované do 5. pádu
	 *
	 * @param string $name Jméno v původním tvaru
	 * @param bool|null $woman
	 * @param bool|null $lastName
	 * @return string Jméno v 5. pádu
	 */
	public function vokativ(string $name, bool $woman = null, bool $lastName = null): string
	{
		$name = mb_strtolower(trim($name), 'UTF-8');

		if ($woman === null) {
			$woman = !$this->isMale($name);
		}

		if ($woman) {
			if ($lastName === null) {
				[, $type] = $this->getMatchingSuffix($name, $this->getWomanFirstVsLastNameSuffixes());

				$lastName = $type === 'l';
			}

			return $lastName
				? $this->vokativWomanLastName($name)
				: $this->vokativWomanFirstName($name);
		}

		return $this->vokativMan($name);
	}


	/**
	 * Na základě jména nebo přijmení rozhodne o pohlaví
	 *
	 * @param string $name Jméno v prvním pádu
	 * @return boolean Rozhodne, jeslti je jméno mužské
	 */
	public function isMale(string $name): bool
	{
		[, $sex] = $this->getMatchingSuffix(mb_strtolower($name, 'UTF-8'), $this->getManVsWomanSuffixes());

		return $sex !== 'w';
	}


	private function vokativMan(string $name): string
	{
		[$match, $suffix] = $this->getMatchingSuffix($name, $this->getManSuffixes());

		if ($match) {
			$name = mb_substr($name, 0, -1 * mb_strlen($match));
		}

		return $name . $suffix;
	}


	private function vokativWomanFirstName(string $name): string
	{
		if (mb_substr($name, -1) === 'a') {
			return mb_substr($name, 0, -1) . 'o';
		}

		return $name;
	}


	private function vokativWomanLastName(string $name): string
	{
		return $name;
	}


	/**
	 * @param string $name
	 * @param string[] $suffixes
	 * @return string[]
	 */
	private function getMatchingSuffix(string $name, array $suffixes): array
	{
		// it is important(!) to try suffixes from longest to shortest
		foreach (range(mb_strlen($name), 1) as $length) {
			$suffix = mb_substr($name, -1 * $length);
			if (array_key_exists($suffix, $suffixes)) {
				return [$suffix, $suffixes[$suffix]];
			}
		}

		return ['', $suffixes['']];
	}


	private function getManSuffixes(): array
	{
		return $this->manSuffixes ?? $this->manSuffixes = $this->readSuffixes('man_suffixes');
	}


	private function getManVsWomanSuffixes(): array
	{
		return $this->manVsWomanSuffixes ?? $this->manVsWomanSuffixes = $this->readSuffixes('man_vs_woman_suffixes');
	}


	private function getWomanFirstVsLastNameSuffixes(): array
	{
		return $this->womanFirstVsLastSuffixes ?? $this->womanFirstVsLastSuffixes = $this->readSuffixes('woman_first_vs_last_name_suffixes');
	}


	private function readSuffixes(string $file)
	{
		if (file_exists($filename = __DIR__ . '/data/' . $file) === false) {
			throw new \RuntimeException('Data file "' . $filename . '" not found.');
		}

		return unserialize(file_get_contents($filename));
	}
}
