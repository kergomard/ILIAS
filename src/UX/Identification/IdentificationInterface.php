<?php namespace ILIAS\UX\Identification;

/**
 * Interface IdentificationInterface
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
interface IdentificationInterface extends \Serializable {

	/**
	 * @return string
	 */
	public function getClassName(): string;


	/**
	 * @return string
	 */
	public function getInternalIdentifier(): string;
}
