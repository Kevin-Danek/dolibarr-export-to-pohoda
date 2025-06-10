<?php

include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

class modExportPohoda extends DolibarrModules
{
    function __construct($db) {
        global $langs;
        $this->db = $db;

        $this->numero = 104001;
        $this->rights_class = 'exportpohoda';
        $this->family = "interface";
        $this->name = 'Export to Pohoda';
        $this->description = "Creates an XML export for Pohoda";
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_EXPORTPOHODA';
        $this->picto = 'payment';

        $this->dirs = array("/exportpohoda/temp");
        $this->config_page_url = array();
        $this->langfiles = array();
        $this->phpmin = array(7, 0);
        $this->need_dolibarr_version = array(12, 0);
        $this->warnings = array();
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array();
        $this->const = array();
        $this->tabs = array();
        $this->dictionaries = array();
        $this->boxes = array();
        $this->cronjobs = array();

        $this->menu = array(
            array(
                'fk_menu' => "fk_mainmenu=tools",                      // 0 = top menu
                'type' => 'left',                     // Top-level menu
                'titre' => 'Export To Pohoda',
                'mainmenu' => 'tools',           // Unique ID for the menu
                'leftmenu' => 'exportpohoda',                   // Only used for left submenus
                'url' => '/exportpohoda/export.php',     // Link to your module page
                'langs' => 'exportpohoda@exportpohoda',     // For translation (optional)
                'position' => 0,                  // Order in the top bar
                'enabled' => '$conf->exportpohoda->enabled', // Show if module is enabled
                'perms' => '1',                     // Or use permission like $user->rights->mymodule->read
                'target' => '',
                'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
                'user' => 0                         // 0=all, 1=internal users only, 2=external users only
            )
        );


    }
}
