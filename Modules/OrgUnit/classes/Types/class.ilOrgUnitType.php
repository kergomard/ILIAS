<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\ResourceStorage\Services as IRSS;

/**
 * Class ilOrgUnitType
 * @author : Stefan Wanzenried <sw@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilOrgUnitType
{
    public const TABLE_NAME = 'orgu_types';
    protected int $id = 0;
    protected string $default_lang = '';
    protected int $owner;
    protected string $create_date;
    protected string $last_update;
    protected string $icon = '';
    protected array $translations = array();
    protected array $amd_records_assigned;
    protected static ?array $amd_records_available = null;
    protected array $orgus = [];
    protected array $orgus_ids = [];
    protected ilDBInterface $db;
    protected \ILIAS\DI\LoggingServices  $log;
    protected ilObjUser $user;
    protected array $active_plugins;
    protected ilLanguage $lng;
    /** @param self[] */
    protected static array $instances = array();
    protected ilComponentFactory $component_factory;
    protected IRSS $irss;

    /**
     * @throws ilOrgUnitTypeException
     */
    public function __construct(int $a_id = 0)
    {
        global $DIC;
        $this->component_factory = $DIC['component.factory'];
        $this->db = $DIC->database();
        $this->log = $DIC->logger();
        $this->user = $DIC->user();
        $this->lng = $DIC->language();
        if ($a_id) {
            $this->id = (int) $a_id;
            $this->read();
        }
        $this->irss = $DIC['resource_storage'];
    }

    /**
     * Get instance of an ilOrgUnitType object
     * Returns object from cache or from database, returns null if no object was found
     * @param int $a_id ID of the OrgUnit type
     * @return ilOrgUnitType|null
     */
    public static function getInstance(int $a_id): ?ilOrgUnitType
    {
        if (!$a_id) {
            return null;
        }
        if (isset(self::$instances[$a_id])) {
            return self::$instances[$a_id];
        } else {
            try {
                $type = new self($a_id);
                self::$instances[$a_id] = $type;

                return $type;
            } catch (ilOrgUnitTypeException $e) {
                return null;
            }
        }
    }

    /**
     * Get array of all instances of ilOrgUnitType objects
     */
    public static function getAllTypes(): array
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];
        $sql = 'SELECT id FROM ' . self::TABLE_NAME;
        $set = $ilDB->query($sql);
        $types = array();
        while ($rec = $ilDB->fetchObject($set)) {
            $type = new self($rec->id);
            $types[] = $type;
            self::$instances[$rec->id] = $type;
        }

        return $types;
    }

    /**
     * Create object in database. Also invokes creating of translation objects.
     * @throws ilOrgUnitTypeException
     */
    public function create(): void
    {
        $default_lang = $this->getDefaultLang();
        $title = $this->getTranslation('title', $default_lang);
        if (!$default_lang || !$title) {
            throw new ilOrgUnitTypeException($this->lng->txt('orgu_type_msg_missing_title_default_language'));
        }

        $this->id = $this->db->nextId(self::TABLE_NAME);
        $this->db->insert(self::TABLE_NAME, array(
            'id' => array('integer', $this->getId()),
            'default_lang' => array('text', $this->getDefaultLang()),
            'owner' => array('integer', $this->user->getId()),
            'icon' => array('text', $this->getIconIdentifier()),
            'create_date' => array('text', date('Y-m-d H:i:s')),
            'last_update' => array('text', date('Y-m-d H:i:s')),
        ));

        // Create translation(s)
        /** @var $trans ilOrgUnitTypeTranslation */
        foreach ($this->translations as $lang => $trans) {
            $trans->setOrguTypeId($this->getId());
            $trans->create();
        }
    }

    /**
     * Update changes to database
     * @throws ilOrgUnitTypePluginException
     * @throws ilOrgUnitTypeException
     */
    public function update(): void
    {
        $title = $this->getTranslation('title', $this->getDefaultLang());
        if (!$title) {
            throw new ilOrgUnitTypeException($this->lng->txt('orgu_type_msg_missing_title'));
        }

        $disallowed = array();
        $titles = array();
        /** @var ilOrgUnitTypeHookPlugin $plugin */
        foreach ($this->getActivePlugins() as $plugin) {
            if (!$plugin->allowUpdate($this->getId())) {
                $disallowed[] = $plugin;
                $titles[] = $plugin->getPluginName();
            }
        }
        if (count($disallowed)) {
            $msg = sprintf($this->lng->txt('orgu_type_msg_updating_prevented'), implode(', ', $titles));
            throw new ilOrgUnitTypePluginException($msg, $disallowed);
        }

        $this->db->update(self::TABLE_NAME, array(
            'default_lang' => array('text', $this->getDefaultLang()),
            'owner' => array('integer', $this->getOwner()),
            'icon' => array('text', $this->getIconIdentifier()),
            'last_update' => array('text', date('Y-m-d H:i:s')),
        ), array(
            'id' => array('integer', $this->getId()),
        ));

        // Update translation(s)
        /** @var $trans ilOrgUnitTypeTranslation */
        foreach ($this->translations as $trans) {
            $trans->update();
        }
    }

    /**
     * Wrapper around create() and update() methods.
     * @throws ilOrgUnitTypePluginException
     */
    public function save(): void
    {
        if ($this->getId()) {
            $this->update();
        } else {
            $this->create();
        }
    }

    /**
     * Delete object by removing all database entries.
     * Deletion is only possible if this type is not assigned to any OrgUnit and if no plugin disallowed deletion process.
     * @throws ilOrgUnitTypeException
     */
    public function delete(): void
    {
        $orgus = $this->getOrgUnits(false);
        if (count($orgus)) {
            $titles = array();
            /** @var $orgu ilObjOrgUnit */
            foreach ($orgus as $orgu) {
                $titles[] = $orgu->getTitle();
            }
            throw new ilOrgUnitTypeException(sprintf(
                $this->lng->txt('orgu_type_msg_unable_delete'),
                implode(', ', $titles)
            ));
        }

        $disallowed = array();
        $titles = array();
        /** @var ilOrgUnitTypeHookPlugin $plugin */
        foreach ($this->getActivePlugins() as $plugin) {
            if (!$plugin->allowDelete($this->getId())) {
                $disallowed[] = $plugin;
                $titles[] = $plugin->getPluginName();
            }
        }
        if (count($disallowed)) {
            $msg = sprintf($this->lng->txt('orgu_type_msg_deletion_prevented'), implode(', ', $titles));
            throw new ilOrgUnitTypePluginException($msg, $disallowed);
        }

        $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE id = ' . $this->db->quote($this->getId(), 'integer');
        $this->db->manipulate($sql);

        // Reset Type of OrgUnits (in Trash)
        $this->db->update('orgu_data', array(
            'orgu_type_id' => array('integer', 0),
        ), array(
            'orgu_type_id' => array('integer', $this->getId()),
        ));

        // Delete all translations
        ilOrgUnitTypeTranslation::deleteAllTranslations($this->getId());

        // Delete icon & folder
        $this->removeIconFromIrss($this->getIconIdentifier());

        // Delete relations to advanced metadata records
        $sql = 'DELETE FROM orgu_types_adv_md_rec WHERE type_id = ' . $this->db->quote($this->getId(), 'integer');
        $this->db->manipulate($sql);
    }

    /**
     * Get the title of an OrgUnit type. If no language code is given, a translation in the user-language is
     * returned. If no such translation exists, the translation of the default language is substituted.
     * If a language code is provided, returns title for the given language or null.
     * @param string $a_lang_code
     * @return null|string
     */
    public function getTitle(string $a_lang_code = ''): ?string
    {
        return $this->getTranslation('title', $a_lang_code);
    }

    /**
     * Set title of OrgUnit type.
     * If no lang code is given, sets title for default language.
     * @param        $a_title
     * @param string $a_lang_code
     */
    public function setTitle(string $a_title, string $a_lang_code = '')
    {
        $lang = ($a_lang_code) ? $a_lang_code : $this->getDefaultLang();
        $this->setTranslation('title', $a_title, $lang);
    }

    /**
     * Get the description of an OrgUnit type. If no language code is given, a translation in the user-language is
     * returned. If no such translation exists, the description of the default language is substituted.
     * If a language code is provided, returns description for the given language or null.
     * @param string $a_lang_code
     * @return null|string
     */
    public function getDescription(string $a_lang_code = ''): ?string
    {
        return $this->getTranslation('description', $a_lang_code);
    }

    /**
     * Set description of OrgUnit type.
     * If no lang code is given, sets description for default language.
     * @param        $a_description
     * @param string $a_lang_code
     */
    public function setDescription(string $a_description, string $a_lang_code = ''): void
    {
        $lang = ($a_lang_code) ? $a_lang_code : $this->getDefaultLang();
        $this->setTranslation('description', $a_description, $lang);
    }

    /**
     * Get an array of IDs of ilObjOrgUnit objects using this type
     * @param bool $include_deleted
     * @return array
     */
    public function getOrgUnitIds(bool $include_deleted = true): array
    {
        $cache_key = ($include_deleted) ? 1 : 0;

        if (array_key_exists($cache_key, $this->orgus_ids)
            && is_array($this->orgus_ids[$cache_key])
        ) {
            return $this->orgus_ids[$cache_key];
        }
        if ($include_deleted) {
            $sql = 'SELECT * FROM orgu_data WHERE orgu_type_id = ' . $this->db->quote($this->getId(), 'integer');
        } else {
            $sql
                = 'SELECT DISTINCT orgu_id FROM orgu_data od ' . 'JOIN object_reference oref ON oref.obj_id = od.orgu_id ' . 'WHERE od.orgu_type_id = '
                . $this->db->quote($this->getId(), 'integer') . ' AND oref.deleted IS NULL';
        }
        $set = $this->db->query($sql);
        $this->orgus_ids[$cache_key] = array();
        while ($rec = $this->db->fetchObject($set)) {
            $this->orgus_ids[$cache_key][] = $rec->orgu_id;
        }

        return $this->orgus_ids[$cache_key];
    }

    /**
     * Get an array of ilObjOrgUnit objects using this type
     * @param bool $include_deleted True if also deleted OrgUnits are returned
     * @return int[]
     */
    public function getOrgUnits(bool $include_deleted = true): array
    {
        $cache_key = ($include_deleted) ? 1 : 0;

        if (array_key_exists($cache_key, $this->orgus)
            && is_array($this->orgus[$cache_key])
        ) {
            return $this->orgus[$cache_key];
        }
        $this->orgus[$cache_key] = array();
        $ids = $this->getOrgUnitIds($include_deleted);
        foreach ($ids as $id) {
            $orgu = new ilObjOrgUnit($id, false);
            if (!$include_deleted) {
                // Check if OrgUnit is in trash (each OrgUnit does only have one reference)
                $ref_ids = ilObject::_getAllReferences($id);
                $ref_ids = array_values($ref_ids);
                $ref_id = $ref_ids[0];
                if (ilObject::_isInTrash($ref_id)) {
                    continue;
                }
            }
            $this->orgus[$cache_key][] = $orgu;
        }

        return $this->orgus[$cache_key];
    }

    /**
     * Get assigned AdvancedMDRecord objects
     * @param bool $a_only_active True if only active AMDRecords are returned
     * @return ilAdvancedMDRecord[]
     */
    public function getAssignedAdvancedMDRecords(bool $a_only_active = false): array
    {
        $active = ($a_only_active) ? 1 : 0; // Cache key
        if (isset($this->amd_records_assigned[$active])) {
            return $this->amd_records_assigned[$active];
        }
        $this->amd_records_assigned[$active] = [];
        $sql = 'SELECT * FROM orgu_types_adv_md_rec WHERE type_id = ' . $this->db->quote($this->getId(), 'integer');
        $set = $this->db->query($sql);
        while ($rec = $this->db->fetchObject($set)) {
            $amd_record = new ilAdvancedMDRecord((int) $rec->rec_id);
            if ($a_only_active) {
                if ($amd_record->isActive()) {
                    $this->amd_records_assigned[1][] = $amd_record;
                }
            } else {
                $this->amd_records_assigned[0][] = $amd_record;
            }
        }

        return $this->amd_records_assigned[$active];
    }

    /**
     * Get IDs of assigned AdvancedMDRecord objects
     * @param bool $a_only_active True if only IDs of active AMDRecords are returned
     * @return int[]
     */
    public function getAssignedAdvancedMDRecordIds(bool $a_only_active = false): array
    {
        $ids = array();
        /** @var ilAdvancedMDRecord $record */
        foreach ($this->getAssignedAdvancedMDRecords($a_only_active) as $record) {
            $ids[] = $record->getRecordId();
        }

        return $ids;
    }

    /**
     * Get all available AdvancedMDRecord objects for OrgUnits/Types
     * @return ilAdvancedMDRecord[]
     */
    public static function getAvailableAdvancedMDRecords(): array
    {
        if (is_array(self::$amd_records_available)) {
            return self::$amd_records_available;
        }
        self::$amd_records_available = ilAdvancedMDRecord::_getActivatedRecordsByObjectType('orgu', 'orgu_type');

        return self::$amd_records_available;
    }

    /**
     * Get IDs of all available AdvancedMDRecord objects for OrgUnit/Types
     * @return ilAdvancedMDRecord[]
     */
    public static function getAvailableAdvancedMDRecordIds(): array
    {
        $ids = array();
        /** @var ilAdvancedMDRecord $record */
        foreach (self::getAvailableAdvancedMDRecords() as $record) {
            $ids[] = $record->getRecordId();
        }

        return $ids;
    }

    /**
     * Assign a given AdvancedMDRecord to this type.
     * If the AMDRecord is already assigned, nothing is done. If the AMDRecord cannot be assigned to OrgUnits/Types,
     * an Exception is thrown. Otherwise the AMDRecord is assigned (relation gets stored in DB).
     * @param int $a_record_id
     * @throws ilOrgUnitTypePluginException
     * @throws ilOrgUnitTypeException
     */
    public function assignAdvancedMDRecord(int $a_record_id): void
    {
        if (!in_array($a_record_id, $this->getAssignedAdvancedMDRecordIds())) {
            if (!in_array($a_record_id, self::getAvailableAdvancedMDRecordIds())) {
                throw new ilOrgUnitTypeException("AdvancedMDRecord with ID {$a_record_id} cannot be assigned to OrgUnit types");
            }
            /** @var ilOrgUnitTypeHookPlugin $plugin */
            $disallowed = array();
            $titles = array();
            foreach ($this->getActivePlugins() as $plugin) {
                if (!$plugin->allowAssignAdvancedMDRecord($this->getId(), $a_record_id)) {
                    $disallowed[] = $plugin;
                    $titles[] = $plugin->getPluginName();
                }
            }
            if (count($disallowed)) {
                $msg = sprintf($this->lng->txt('orgu_type_msg_assign_amd_prevented'), implode(', ', $titles));
                throw new ilOrgUnitTypePluginException($msg, $disallowed);
            }
            $record_ids = $this->getAssignedAdvancedMDRecordIds();
            $record_ids[] = $a_record_id;
            $this->db->insert('orgu_types_adv_md_rec', array(
                'type_id' => array('integer', $this->getId()),
                'rec_id' => array('integer', $a_record_id),
            ));
            // We need to update each OrgUnit from this type and map the selected records to object_id
            foreach ($this->getOrgUnitIds() as $orgu_id) {
                ilAdvancedMDRecord::saveObjRecSelection($orgu_id, 'orgu_type', $record_ids);
            }
            $this->amd_records_assigned = []; // Force reload of assigned objects
        }
    }

    /**
     * Deassign a given AdvancedMD record from this type.
     * @param int $a_record_id
     * @throws ilOrgUnitTypePluginException
     */
    public function deassignAdvancedMdRecord(int $a_record_id): void
    {
        $record_ids = $this->getAssignedAdvancedMDRecordIds();
        $key = array_search($a_record_id, $record_ids);
        if ($key !== false) {
            /** @var ilOrgUnitTypeHookPlugin $plugin */
            $disallowed = array();
            $titles = array();
            foreach ($this->getActivePlugins() as $plugin) {
                if (!$plugin->allowDeassignAdvancedMDRecord($this->getId(), $a_record_id)) {
                    $disallowed[] = $plugin;
                    $titles[] = $plugin->getPluginName();
                }
            }
            if (count($disallowed)) {
                $msg = sprintf($this->lng->txt('orgu_type_msg_deassign_amd_prevented'), implode(', ', $titles));
                throw new ilOrgUnitTypePluginException($msg, $disallowed);
            }
            unset($record_ids[$key]);
            $sql = 'DELETE FROM orgu_types_adv_md_rec
                    WHERE type_id = ' . $this->db->quote($this->getId(), 'integer') . '
                    AND rec_id = ' . $this->db->quote($a_record_id, 'integer');
            $this->db->query($sql);
            // We need to update each OrgUnit from this type and map the selected records to object_id
            foreach ($this->getOrgUnitIds() as $orgu_id) {
                ilAdvancedMDRecord::saveObjRecSelection($orgu_id, 'orgu_type', $record_ids);
            }
            $this->amd_records_assigned = []; // Force reload of assigned objects
        }
    }


    /**
     * Protected
     */

    /**
     * Helper method to return a translation for a given member and language
     * @param $a_member
     * @param $a_lang_code
     * @return null|string
     */
    protected function getTranslation(string $a_member, string $a_lang_code): ?string
    {
        $lang = ($a_lang_code) ? $a_lang_code : $this->user->getLanguage();
        $trans_obj = $this->loadTranslation($lang);
        if (!is_null($trans_obj)) {
            $translation = $trans_obj->getMember($a_member);
            // If the translation does exist but is an empty string and there was no lang code given,
            // substitute default language anyway because an empty string provides no information
            if (!$a_lang_code && !$translation) {
                $trans_obj = $this->loadTranslation($this->getDefaultLang());

                return $trans_obj->getMember($a_member);
            }

            return $translation;
        } else {
            // If no lang code was given and there was no translation found, return string in default language
            if (!$a_lang_code) {
                $trans_obj = $this->loadTranslation($this->getDefaultLang());

                return $trans_obj->getMember($a_member);
            }

            return null;
        }
    }

    /**
     * Helper method to set a translation for a given member and language
     * @param string $a_member
     * @param string $a_value
     * @param string $a_lang_code
     * @throws ilOrgUnitTypePluginException
     */
    protected function setTranslation(string $a_member, string $a_value, string $a_lang_code): void
    {
        $a_value = trim($a_value);
        // If the value is identical, quit early and do not execute plugin checks
        $existing_translation = $this->getTranslation($a_member, $a_lang_code);
        if ($existing_translation === $a_value) {
            return;
        }
        // #19 Title should be unique per language
        //        if ($a_value && $a_member == 'title') {
        //            if (ilOrgUnitTypeTranslation::exists($this->getId(), 'title', $a_lang_code, $a_value)) {
        //                throw new ilOrgUnitTypeException($this->lng->txt('orgu_type_msg_title_already_exists'));
        //            }
        //        }
        $disallowed = [];
        $titles = [];
        /** @var ilOrgUnitTypeHookPlugin $plugin */
        foreach ($this->getActivePlugins() as $plugin) {
            $allowed = true;
            if ($a_member === 'title') {
                $allowed = $plugin->allowSetTitle($this->getId(), $a_lang_code, $a_value);
            } else {
                if ($a_member === 'description') {
                    $allowed = $plugin->allowSetDescription($this->getId(), $a_lang_code, $a_value);
                }
            }
            if (!$allowed) {
                $disallowed[] = $plugin;
                $titles[] = $plugin->getPluginName();
            }
        }
        if (count($disallowed)) {
            $msg = sprintf($this->lng->txt('orgu_type_msg_setting_member_prevented'), $a_value, implode(', ', $titles));
            throw new ilOrgUnitTypePluginException($msg, $disallowed);
        }
        $trans_obj = $this->loadTranslation($a_lang_code);
        if (!is_null($trans_obj)) {
            $trans_obj->setMember($a_member, $a_value);
        } else {
            $trans_obj = new ilOrgUnitTypeTranslation();
            $trans_obj->setOrguTypeId($this->getId());
            $trans_obj->setLang($a_lang_code);
            $trans_obj->setMember($a_member, $a_value);
            $this->translations[$a_lang_code] = $trans_obj;
            // Create language object here if this type is already in DB.
            // Otherwise, translations are created when calling create() on this object.
            if ($this->getId()) {
                $trans_obj->create();
            }
        }
    }

    /**
     * Get array of all acitve plugins for the ilOrgUnitTypeHook plugin slot
     * @return array
     */
    public function getActivePlugins(): array
    {
        return iterator_to_array($this->component_factory->getActivePluginsInSlot("orgutypehk"));
    }

    /**
     * Helper function to load a translation.
     * Returns translation object from cache or null, if no translation exists for the given code.
     * @param string $a_lang_code A language code
     * @return ilOrgUnitTypeTranslation|null
     */
    protected function loadTranslation(string $a_lang_code): ?ilOrgUnitTypeTranslation
    {
        if (isset($this->translations[$a_lang_code])) {
            return $this->translations[$a_lang_code];
        } else {
            $trans_obj = ilOrgUnitTypeTranslation::getInstance($this->getId(), $a_lang_code);
            if (!is_null($trans_obj)) {
                $this->translations[$a_lang_code] = $trans_obj;

                return $trans_obj;
            }
        }

        return null;
    }

    /**
     * Read object data from database
     * @throws ilOrgUnitTypeException
     */
    protected function read(): void
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = ' . $this->db->quote($this->id, 'integer');
        $set = $this->db->query($sql);
        if (!$this->db->numRows($set)) {
            throw new ilOrgUnitTypeException("OrgUnit type with id {$this->id} does not exist in database");
        }
        $rec = $this->db->fetchObject($set);
        $this->default_lang = $rec->default_lang; // Don't use Setter because of unnecessary plugin checks
        $this->setCreateDate($rec->create_date);
        $this->setLastUpdate($rec->last_update);
        $this->setOwner($rec->owner);
        $this->icon = $rec->icon ?? '';
    }

    /**
     * Helper function to check if this type can be updated
     * @return bool
     */
    protected function updateable(): bool
    {
        foreach ($this->getActivePlugins() as $plugin) {
            if (!$plugin->allowUpdate($this->getId())) {
                return false;
            }
        }

        return true;
    }


    /**
     * Getters & Setters
     */

    /**
     * @param string[] $translations
     */
    public function setTranslations(array $translations)
    {
        $this->translations = $translations;
    }

    /**
     * Returns the loaded translation objects
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Returns all existing translation objects
     * @return array
     */
    public function getAllTranslations(): array
    {
        $translations = ilOrgUnitTypeTranslation::getAllTranslations($this->getId());
        /** @var ilOrgUnitTypeTranslation $trans */
        foreach ($translations as $trans) {
            $this->translations[$trans->getLang()] = $trans;
        }

        return $this->translations;
    }

    public function setOwner(int $owner)
    {
        $this->owner = $owner;
    }

    public function getOwner(): int
    {
        return $this->owner;
    }

    public function setLastUpdate(string $last_update)
    {
        $this->last_update = $last_update;
    }

    public function getLastUpdate(): string
    {
        return $this->last_update;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function withIconIdentifier(string $identifier): self
    {
        $clone = clone $this;
        $clone->icon = $identifier;
        return $clone;
    }

    public function getIconIdentifier(): string
    {
        return $this->icon;
    }

    /**
     * @param string $default_lang
     * @throws ilOrgUnitTypePluginException
     */
    public function setDefaultLang(string $default_lang): void
    {
        // If the new default_lang is identical, quit early and do not execute plugin checks
        if ($this->default_lang == $default_lang) {
            return;
        }
        $disallowed = array();
        $titles = array();
        /**
         * @var ilOrgUnitTypeHookPlugin $plugin
         */
        foreach ($this->getActivePlugins() as $plugin) {
            if (!$plugin->allowSetDefaultLanguage($this->getId(), $default_lang)) {
                $disallowed[] = $plugin;
                $titles[] = $plugin->getPluginName();
            }
        }
        if (count($disallowed)) {
            $msg = sprintf(
                $this->lng->txt('orgu_type_msg_setting_default_lang_prevented'),
                $default_lang,
                implode(', ', $titles)
            );
            throw new ilOrgUnitTypePluginException($msg, $disallowed);
        }

        $this->default_lang = $default_lang;
    }

    public function getDefaultLang(): string
    {
        return $this->default_lang;
    }

    public function setCreateDate(string $create_date)
    {
        $this->create_date = $create_date;
    }

    public function getCreateDate(): string
    {
        return $this->create_date;
    }

    public function removeIconFromIrss(string $identifier): void
    {
        if($rid = $this->irss->manage()->find($identifier)) {
            $this->irss->manage()->remove($rid, new ilOrgUnitTypeStakeholder());
        }
    }
}
