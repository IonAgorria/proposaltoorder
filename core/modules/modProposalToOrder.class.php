<?php
/*
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup	proposaltoorder	modProposalToOrder module
 * 	\file		core/modules/modProposalToOrder.class.php
 * 	\ingroup	proposaltoorder
 * 	\brief		Converts project proposals to project orders
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module ProposalToOrder
 */
class modProposalToOrder extends DolibarrModules
{

    /**
    *         Constructor. Define names, constants, directories, boxes, permissions
    *
    *         @param        DoliDB                $db        Database handler
    */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for modul.
        $this->numero = 674501;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'proposaltoorder';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        $this->family = "projects";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Converts Proposal to Order in a project";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = '3.5.0-r0.1';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto='propal';

        //Triggers/Hooks
        $this->module_parts = array(
            'hooks' => array('propalcard'), // Set here all hooks context you want to support
        );


        // Data directories to create when module is enabled.
        $this->dirs = array();
        $r=0;

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        //$this->config_page_url = array("proposaltoorder.php@proposaltoorder");

        // Dependencies
        $this->depends = array();       // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();                // List of modules id to disable if this one is disabled
        $this->phpmin = array(5,0);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,2);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("proposaltoorder@proposaltoorder");

        // Constants
        $this->const = array();         // List of parameters
        // Array to add new pages in new tabs
        $this->tabs = array();

        // Boxes
        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();         // List of boxes
        $r=0;

        // Permissions
        $this->rights_class = 'proposaltoorder';    // Permission key
        $this->rights = array();            // Permission array used by this module
    }

    /**
     *      Function called when module is enabled.
     *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *      It also creates data directories.
     *      @return     int             1 if OK, 0 if KO
     */
    function init()
    {
        $sql = array();

        $result=$this->load_tables();

        return $this->_init($sql);
    }

    /**
     *      Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     *      Data directories are not deleted.
     *      @return     int             1 if OK, 0 if KO
     */
    function remove()
    {
        $sql = array();

        return $this->_remove($sql);
    }


    /**
     *      Create tables, keys and data required by module
     *      Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     *      and create data commands must be stored in directory /mymodule/sql/
     *      This function is called by this->init
     *
     *      @return     int     <=0 if KO, >0 if OK
     */
    function load_tables()
    {
        return 1;
    }
}
?>
