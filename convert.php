<?php
/*
 * Copyright (C) 2014      Ion Agorria          <cubexed@gmail.com>
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
 *  \file       convert.php
 *  \ingroup    proposaltoorder
 *  \brief      ProposalToOrder conversion code
 */

//TODO: Copy dates

//include depending of root or custom directory
$result=@include("../main.inc.php");
if (! $result) {
    $result=@include("../../main.inc.php");
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$booking_import = dol_include_once("/booking/class/booking_extra.class.php"); //Load BookingExtra for date handling

if (! empty($conf->projet->enabled))
{
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

function convert_propal($db, $propal_id) {
    global $user;
    //Load the propal object instance and fetch the data from DB
    $propal = new Propal($db);
    $ret = $propal->fetch($propal_id);
    if ($ret < 0) dol_print_error('',$propal->error);
    if ($ret > 0) {
        $created_orders = array(); //List of suppliers that have a order associated, format (fourn_socid => order_id)
        //Iterate each line in propal
        foreach($propal->lines as $array_line)
        {
            $fk_product = $array_line->fk_product;
            if (!empty($fk_product)) {
                $product = new Product($db);
                $product->fetch($fk_product);
                //product from line has ID, lets get supplier
                $product_fourn = new ProductFournisseur($db);
                $ret = $product_fourn->find_min_price_product_fournisseur($product->id);
                if ($ret > 0) {
                    //We found the supplier for this product, lets check if we have a already existing
                    //order for this supplier, if not, create a new one
                    $fourn_socid = $product_fourn->fourn_id;
                    //Create a supplier order object instance
                    $order_supplier = new CommandeFournisseur($db);
                    if (array_key_exists($fourn_socid, $created_orders)) {
                        //Get the existing order
                        $order_id = $created_orders[$fourn_socid];
                        $order_supplier->fetch($order_id);
                    } else {
                        //Put the important values to the empty order
                        $order_supplier->socid = $fourn_socid;
                        //Create to db
                        $order_supplier->create($user);
                        //Get the id
                        $order_id = $order_supplier->id;
                        //Assign the project which is linked this order
                        $order_supplier->setProject($propal->fk_project, $order_id);
                        $created_orders[$fourn_socid] = $order_id;
                    }
                    order_supplier_addline($db, $order_supplier, $product, $array_line);
                }
            }
        }
        foreach($created_orders as $fourn_socid => $order_id){
            $order_supplier = new CommandeFournisseur($db);
            $order_supplier->fetch($order_id);
            $order_supplier->update_price(); //Update the prices and stuff...
        }
    }
}

function order_supplier_addline($db, $order_supplier, $product, $propal_line)
{
    global $langs;

    //Get the data and put into variables
    $fk_commande        = $order_supplier->id;
    $fk_product         = $propal_line->fk_product;
    $label              = $product->label; //Product object has more details
    $desc               = $product->description;  //Product object has more details
    $product_type       = $propal_line->product_type;
    $qty                = $propal_line->qty;
    $tva_tx             = $propal_line->tva_tx;
    $remise_percent     = $propal_line->remise_percent;
    $subprice           = $propal_line->subprice;
    $ref                = $propal_line->product_ref;
    $total_ht           = $propal_line->total_ht;
    $total_tva          = $propal_line->total_tva;
    $total_ttc          = $propal_line->total_ttc;

    //These are special db values that are not "normally" accesible from class interface, so we use raw sql access
    $sql = 'SELECT pd.rowid,';
    $sql.= ' pd.localtax1_tx, pd.localtax1_type, pd.total_localtax1,';
    $sql.= ' pd.localtax2_tx, pd.localtax2_type, pd.total_localtax2';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pd';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pd.fk_product = p.rowid';
    $sql.= ' WHERE pd.rowid = '.$propal_line->rowid;

    $result = $db->query($sql);
    if ($result)
    {
        $objp = $db->fetch_object($result);
        $txlocaltax1        = $objp->localtax1_tx;
        $txlocaltax2        = $objp->localtax2_tx;
        $localtax1_type     = $objp->localtax1_type;
        $localtax2_type     = $objp->localtax2_type;
        $total_localtax1    = $objp->total_localtax1;
        $total_localtax2    = $objp->total_localtax2;
        $db->free($result);
    }

    //Insert the values
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
    $sql.= " (fk_commande, label, description,";
    $sql.= " fk_product, product_type,";
    $sql.= " qty, tva_tx, localtax1_tx, localtax2_tx, remise_percent, subprice, ref,";
    $sql.= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
    $sql.= " localtax1_type, localtax2_type";
    $sql.= ") VALUES (".$fk_commande.", '" . $label . "','" . $desc . "',";
    if ($fk_product) { $sql.= $fk_product.","; }
    else { $sql.= "null,"; }
    $sql.= "'".$product_type."',";
    $sql.= "'".$qty."', ".$tva_tx.", ".$txlocaltax1.", ".$txlocaltax2.", ".$remise_percent.",'".$subprice."','".$ref."',";
    $sql.= "'".$total_ht."',";
    $sql.= "'".$total_tva."',";
    $sql.= "'".$total_localtax1."',";
    $sql.= "'".$total_localtax2."',";
    $sql.= "'".$total_ttc."',";
    $sql.= "'".$localtax1_type."',";
    $sql.= "'".$localtax2_type."'";
    $sql.= ")";

    dol_syslog("order_supplier_addline sql=".$sql);
    $resql=$db->query($sql);

    if ($resql)
    {
        $rowid = $db->last_insert_id(MAIN_DB_PREFIX.'commande_fournisseurdet');
        $order_supplier->rowid = $rowid;
        if (!($booking_import < 0)) {
            //Fix the date formatting
            $chars = array(" ", "-", ":");
            $date_start = str_replace($chars, "", $propal_line->date_start);
            $date_end = str_replace($chars, "", $propal_line->date_end);
            if (!empty($date_start) || !empty($date_end)) {
                //The main copy has finished, now copy the dates too
                $date_extra = new BookingExtra($db, 0); //0 indicates the object type as "order", 1 is "facture"
                $date_extra->fk_ref_id                = $rowid;
                $date_extra->fk_product_fournisseur   = $fk_product;
                $date_extra->date_start               = $date_start;
                $date_extra->date_end                 = $date_end;
                $date_extra->create();
            }
        }
        // Trigger disabled because POST data is incomplete compared to original trigger and causes unpredictable behavior
        // if (! $notrigger)
        // {
        //     global $conf, $langs, $user;
        //     // Appel des triggers
        //     include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        //     $interface=new Interfaces($db);
        //     $result=$interface->run_triggers('LINEORDER_SUPPLIER_CREATE',$order_supplier,$user,$langs,$conf);
        //     if ($result < 0) {
        //         $error++; $errors=$interface->errors;
        //     }
        //     // Fin appel triggers
        // }
        $db->commit();
        return 1;
    }
    else
    {
        $error=$db->error();
        $db->rollback();
        dol_syslog("order_supplier_addline ".$error, LOG_ERR);
        return -1;
    }
}

//Load from GET/POST
$mesg = ''; //Disable msg error when nothing happened
$id = GETPOST('id','int');
$action = GETPOST('action');
$confirm = GETPOST('confirm');
$return = GETPOST('return');
if ($id > 0 && $confirm == "yes" && $action == "convert" && !empty($return)) {
    convert_propal($db, $id);
}
$db->close();
//Send the browser back where it came from
print  '<script type="text/javascript">window.location = "'.$return.'?id='.$id.'";</script>';