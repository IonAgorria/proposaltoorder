<?php
/* Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       actions_proposaltoorder.class.php
 *      \brief      File of hooks for module
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

class ActionsProposalToOrder // extends CommonObject
{
    /**
     * Checks if there is some line that was manually created inside the object
     *
     */
    private function check_libre($object) {
        foreach($object->lines as $array_line)
        {
            $fk_product = $array_line->fk_product;
            if (empty($fk_product)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prints to js console
     *
     */
    private function debug_print($data) {
        print '<script>console.log("debug: '.$data.'");</script>';
    }

    /**
     * This hook is called when NO form is active
     *
     */
    function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $confirm=GETPOST('confirm','alpha');
        //Check if user confirmed the validation
        if ($action == "confirm_validate" && $confirm == "yes") {
            $langs->load("proposaltoorder@proposaltoorder");
            $some_libre = $this->check_libre($object);
            //print '<script type="text/javascript" src="../includes/jquery/js/jquery-latest.min.js"></script>';
            //print '<script type="text/javascript" src="../includes/jquery/js/jquery-ui-latest.custom.min.js"></script>';
            $text = $langs->trans('PopupContent');
            $height = 180;
            if ($some_libre) {
                $text = $text."</br></br><p style=\"color:red\">".$langs->trans('ManualWarning')."<p>";
                $height  = 210;
            }
            $form = new Form($db);
            $formconfirm=$form->formconfirm(
                dol_buildpath('proposaltoorder/convert.php', 2).'?id='.$object->id.'&return='.$_SERVER["PHP_SELF"], //page Url of page to call if confirmation is OK
                $langs->trans('PopupTitle'), //title of popup
                $text, //question of popup
                "convert", //action in the URL, &action=
                "", //formquestion An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
                "", //pre(?) selectedchoice "" or "no" or "yes"
                1, //useajax 0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=preoutput confirm box with div id=dialog-confirm-xxx
                $height);
            print $formconfirm;
        }
        return 1;
    }
}
?>