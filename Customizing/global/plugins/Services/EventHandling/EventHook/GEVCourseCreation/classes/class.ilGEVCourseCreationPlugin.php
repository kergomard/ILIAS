<?php

require_once("./Services/EventHandling/classes/class.ilEventHookPlugin.php");

class ilGEVCourseCreationPlugin extends ilEventHookPlugin
{
	final function getPluginName() {
		return "GEVCourseCreation";
	}
	
	final function handleEvent($a_component, $a_event, $a_parameter) {
		if ($a_component !== "Services/Object" || $a_event !== "afterClone") {
			return;
		}
		
		require_once("Services/Object/classes/class.ilObject.php");

		if (ilObject::_lookupType($a_parameter["target_ref_id"], true) !== "crs") {
			return;
		}

		$this->clonedCourses($a_parameter["source_ref_id"], $a_parameter["target_ref_id"]);

		global $ilLog;
		$ilLog->write("Cloned course ".$a_parameter["target_ref_id"]." from course ". $a_parameter["source_ref_id"]);		
	}

	public function clonedCourses($a_source_ref_id, $a_target_ref_id) {
		require_once("Services/GEV/Utils/classes/class.gevObjectUtils.php");
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		require_once("Modules/Course/classes/class.ilObjCourse.php");
				
		try {
			$target = new ilObjCourse($a_target_ref_id);
			$target_utils = gevCourseUtils::getInstance(gevObjectUtils::getObjId($a_target_ref_id));
			
			$source = new ilObjCourse($a_source_ref_id);
			$source_utils = gevCourseUtils::getInstance(gevObjectUtils::getObjId($a_source_ref_id));
			
			
			// Do this anyway to prevent havoc!
			$target->setOfflineStatus(true);
			$target_utils->setStartDate(null);
			$target_utils->setEndDate(null);
		
			if ($source_utils->isTemplate()) {
				$target->setTitle($source->getTitle());
				$target_utils->setTemplateTitle($source->getTitle());
			}

			$this->setCustomId($target_utils, $source_utils);

			$target->update();
		}
		catch (Exception $e) {
			global $ilLog;
			$ilLog->write("Error in GEVCourseCreation::clonedCourses: ".print_r($e, true));
		}
	}
	
	public function setCustomId($a_target_utils, $a_source_utils) {
		if ($a_source_utils->isTemplate()) {
					$custom_id_tmplt = $a_source_utils->getCustomId();
		}
		else {
			$custom_id_tmplt = gevCourseUtils::extractCustomIdTemplate($a_target_utils->getCustomId());
		}

		$custom_id = gevCourseUtils::createNewCustomId($custom_id_tmplt);
		$a_target_utils->setCustomId($custom_id);
	}
}

?>