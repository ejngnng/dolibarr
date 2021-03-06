<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2017  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Remy Younes             <ryounes@gmail.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2016  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	    \file       htdocs/admin/mails_templates.php
 *		\ingroup    setup
 *		\brief      Page to administer data tables
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

$langs->load("errors");
$langs->load("admin");
$langs->load("main");
$langs->load("mails");

$action=GETPOST('action','alpha')?GETPOST('action','alpha'):'view';
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$rowid=GETPOST('rowid','alpha');

$allowed=$user->admin;
if (! $allowed) accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset','alpha');
$listlimit=GETPOST('listlimit','alpha')>0?GETPOST('listlimit','alpha'):1000;
$active = 1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('emailtemplates'));

// Name of SQL tables of dictionaries
$tabname=array();
$tabname[25]= MAIN_DB_PREFIX."c_email_templates";

// Requests to extract data
$tabsql=array();
$tabsql[25]= "SELECT rowid   as rowid, label, type_template, private, position, topic, content_lines, content, active FROM ".MAIN_DB_PREFIX."c_email_templates WHERE entity IN (".getEntity('email_template',1).")";

// Criteria to sort dictionaries
$tabsqlsort=array();
$tabsqlsort[25]="label ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield=array();
$tabfield[25]= "label,type_template,private,position,topic,content";
if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) $tabfield[25].=',content_lines';

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue=array();
$tabfieldvalue[25]= "label,type_template,private,position,topic,content";
if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) $tabfieldvalue[25].=',content_lines';

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert=array();
$tabfieldinsert[25]= "label,type_template,private,position,topic,content";
if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) $tabfieldinsert[25].=',content_lines';
$tabfieldinsert[25].=',entity';     // Must be at end because not into other arrays

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid=array();
$tabrowid[25]= "";

// Condition to show dictionary in setup page
$tabcond=array();
$tabcond[25]= true;

// List of help for fields
// Set MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES to allow edit of template for lines
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
$formmail=new FormMail($db);
if (empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES))
{
    $tmp=FormMail::getAvailableSubstitKey('formemail');
    $tmp['__(AnyTranslationKey)__']='__(AnyTranslationKey)__';
    $helpsubstit = $langs->trans("AvailableVariables").':<br>'.implode('<br>', $tmp);
    $helpsubstitforlines = $langs->trans("AvailableVariables").':<br>'.implode('<br>', $tmp);
}
else
{
    $tmp=FormMail::getAvailableSubstitKey('formemailwithlines');
    $tmp['__(AnyTranslationKey)__']='__(AnyTranslationKey)__';
    $helpsubstit = $langs->trans("AvailableVariables").':<br>'.implode('<br>', $tmp);
    $tmp=FormMail::getAvailableSubstitKey('formemailforlines');
    $helpsubstitforlines = $langs->trans("AvailableVariables").':<br>'.implode('<br>', $tmp);
}


$tabhelp=array();
$tabhelp[25] = array('topic'=>$helpsubstit,'content'=>$helpsubstit,'content_lines'=>$helpsubstitforlines,'type_template'=>$langs->trans("TemplateForElement"),'private'=>$langs->trans("TemplateIsVisibleByOwnerOnly"), 'position'=>$langs->trans("PositionIntoComboList"));

// List of check for fields (NOT USED YET)
$tabfieldcheck=array();
$tabfieldcheck[25] = array();


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
$sourceList=array();

// We save list of template email Dolibarr can manage. This list can found by a grep into code on "->param['models']"
$elementList = array();
if ($conf->propal->enabled) $elementList['propal_send']=$langs->trans('MailToSendProposal');
if ($conf->commande->enabled) $elementList['order_send']=$langs->trans('MailToSendOrder');
if ($conf->facture->enabled) $elementList['facture_send']=$langs->trans('MailToSendInvoice');
if ($conf->expedition->enabled) $elementList['shipping_send']=$langs->trans('MailToSendShipment');
if ($conf->ficheinter->enabled) $elementList['fichinter_send']=$langs->trans('MailToSendIntervention');
if ($conf->supplier_proposal->enabled) $elementList['supplier_proposal_send']=$langs->trans('MailToSendSupplierRequestForQuotation');
if ($conf->fournisseur->enabled) $elementList['order_supplier_send']=$langs->trans('MailToSendSupplierOrder');
if ($conf->fournisseur->enabled) $elementList['invoice_supplier_send']=$langs->trans('MailToSendSupplierInvoice');
if ($conf->societe->enabled) $elementList['thirdparty']=$langs->trans('MailToThirdparty');
if ($conf->contrat->enabled) $elementList['contract']=$langs->trans('MailToSendContract');

$parameters=array('elementList'=>$elementList);
$reshook=$hookmanager->executeHooks('emailElementlist',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook == 0) {
	foreach ($hookmanager->resArray as $item => $value) {
		$elementList[$item] = $value;
	}
}

$id = 25;


/*
 * Actions
 */

if (GETPOST('button_removefilter') || GETPOST('button_removefilter.x') || GETPOST('button_removefilter_x'))
{
    //$search_country_id = '';
}

// Actions add or modify an entry into a dictionary
if (GETPOST('actionadd') || GETPOST('actionmodify'))
{
    $listfield=explode(',', str_replace(' ', '',$tabfield[$id]));
    $listfieldinsert=explode(',',$tabfieldinsert[$id]);
    $listfieldmodify=explode(',',$tabfieldinsert[$id]);
    $listfieldvalue=explode(',',$tabfieldvalue[$id]);

    // Check that all fields are filled
    $ok=1;
    foreach ($listfield as $f => $value)
    {
        if ($value == 'content') continue;
        if ($value == 'content_lines') continue;
        if ($value == 'content') $value='content-'.$rowid;
        if ($value == 'content_lines') $value='content_lines-'.$rowid;

        if (! isset($_POST[$value]) || $_POST[$value]=='')
        {
            $ok=0;
            $fieldnamekey=$listfield[$f];
            // We take translate key of field
            if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label'))  $fieldnamekey='Label';
            if ($fieldnamekey == 'libelle_facture') $fieldnamekey = 'LabelOnDocuments';
            if ($fieldnamekey == 'code') $fieldnamekey = 'Code';
            if ($fieldnamekey == 'note') $fieldnamekey = 'Note';
            if ($fieldnamekey == 'type') $fieldnamekey = 'Type';

            setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
        }
    }

    // Si verif ok et action add, on ajoute la ligne
    if ($ok && GETPOST('actionadd'))
    {
        if ($tabrowid[$id])
        {
            // Recupere id libre pour insertion
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$id].") newid from ".$tabname[$id];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);

            } else {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO ".$tabname[$id]." (";
        // List of fields
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $tabrowid[$id].",";
        $sql.= $tabfieldinsert[$id];
        $sql.=",active)";
        $sql.= " VALUES(";

        // List of values
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
        	$sql.= $newid.",";
        $i=0;
        foreach ($listfieldinsert as $f => $value)
        {
            //var_dump($i.' - '.$listfieldvalue[$i].' - '.$_POST[$listfieldvalue[$i]].' - '.$value);
            if ($value == 'entity') {
            	$_POST[$listfieldvalue[$i]] = $conf->entity;
            }
            if ($i) $sql.=",";
            if ($value == 'private' && ! is_numeric($_POST[$listfieldvalue[$i]])) $_POST[$listfieldvalue[$i]]='0';
            if ($value == 'position' && ! is_numeric($_POST[$listfieldvalue[$i]])) $_POST[$listfieldvalue[$i]]='1';
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";  // For vat, we want/accept code = ''
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.=",1)";

        dol_syslog("actionadd", LOG_DEBUG);
        $result = $db->query($sql);
        if ($result)	// Add is ok
        {
            setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
        	$_POST=array('id'=>$id);	// Clean $_POST array, we keep only
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
            }
            else {
                dol_print_error($db);
            }
        }
    }

    // Si verif ok et action modify, on modifie la ligne
    if ($ok && GETPOST('actionmodify'))
    {
        if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
        else { $rowidcol="rowid"; }

        // Modify entry
        $sql = "UPDATE ".$tabname[$id]." SET ";
        // Modifie valeur des champs
        if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldmodify))
        {
            $sql.= $tabrowid[$id]."=";
            $sql.= "'".$db->escape($rowid)."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field)
        {
            if ($field == 'content') $_POST['content']=$_POST['content-'.$rowid];
            if ($field == 'content_lines') $_POST['content_lines']=$_POST['content_lines-'.$rowid];
            if ($field == 'entity') {
            	$_POST[$listfieldvalue[$i]] = $conf->entity;
            }
            if ($i) $sql.=",";
            $sql.= $field."=";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";  // For vat, we want/accept code = ''
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$rowid."'";

        dol_syslog("actionmodify", LOG_DEBUG);
        //print $sql;
        $resql = $db->query($sql);
        if (! $resql)
        {
            setEventMessages($db->error(), null, 'errors');
        }
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol."='".$rowid."'";

    dol_syslog("delete", LOG_DEBUG);
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
        }
        else
        {
            dol_print_error($db);
        }
    }
}

// activate
if ($action == $acts[0])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE ".$rowidcol."='".$rowid."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}

// disable
if ($action == $acts[1])
{
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($rowid) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE ".$rowidcol."='".$rowid."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}



/*
 * View
 */

$form = new Form($db);
$formadmin=new FormAdmin($db);

llxHeader();

$titre=$langs->trans("EMailsSetup");
$linkback='';
$titlepicto='title_setup';

print load_fiche_titre($titre,$linkback,$titlepicto);

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/mails.php";
$head[$h][1] = $langs->trans("OutGoingEmailSetup");
$head[$h][2] = 'common';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/mails_templates.php";
$head[$h][1] = $langs->trans("DictionaryEMailTemplates");
$head[$h][2] = 'templates';
$h++;


dol_fiche_head($head, 'templates', '', -1);

// Confirmation de la suppression de la ligne
if ($action == 'delete')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&code='.$_GET["code"].'&id='.$id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
}
//var_dump($elementList);

// Complete requete recherche valeurs avec critere de tri
$sql=$tabsql[$id];

if ($search_country_id > 0)
{
    if (preg_match('/ WHERE /',$sql)) $sql.= " AND ";
    else $sql.=" WHERE ";
    $sql.= " c.rowid = ".$search_country_id;
}

if ($sortfield)
{
    // If sort order is "country", we use country_code instead
	if ($sortfield == 'country') $sortfield='country_code';
    $sql.= " ORDER BY ".$sortfield;
    if ($sortorder)
    {
        $sql.=" ".strtoupper($sortorder);
    }
    $sql.=", ";
    // Clear the required sort criteria for the tabsqlsort to be able to force it with selected value
    $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.' '.$sortorder.',/i','',$tabsqlsort[$id]);
    $tabsqlsort[$id]=preg_replace('/([a-z]+\.)?'.$sortfield.',/i','',$tabsqlsort[$id]);
}
else {
    $sql.=" ORDER BY ";
}
$sql.=$tabsqlsort[$id];
$sql.=$db->plimit($listlimit+1,$offset);
//print $sql;

$fieldlist=explode(',',$tabfield[$id]);

print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from','alpha')).'">';

print '<table class="noborder" width="100%">';

// Form to add a new line
$alabelisused=0;
$var=false;

$fieldlist=explode(',',$tabfield[$id]);

if ($action != 'edit')
{
    // Line for title
    print '<tr class="liste_titre">';
    foreach ($fieldlist as $field => $value)
    {
        // Determine le nom du champ par rapport aux noms possibles
        // dans les dictionnaires de donnees
        $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
        $valuetoshow=$langs->trans($valuetoshow);   // try to translate
        $align="left";
        if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
        if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
        if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
        if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label"); }
        if ($fieldlist[$field]=='type_template')   { $valuetoshow=$langs->trans("TypeOfTemplate"); }
    	if ($fieldlist[$field]=='content')         { $valuetoshow=''; }
    	if ($fieldlist[$field]=='content_lines')   { $valuetoshow=''; }

        if ($valuetoshow != '')
        {
            print '<td align="'.$align.'">';
        	if (! empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i',$tabhelp[$id][$value])) print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1,$valuetoshow).'</a>';
        	else if (! empty($tabhelp[$id][$value]))
        	{
        	    if (in_array($value, array('topic'))) print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2, $value);   // Tooltip on click
        	    else print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2);                             // Tooltip on hover
        	}
        	else print $valuetoshow;
            print '</td>';
         }
         if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
    }

    print '<td colspan="3">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    print '</td>';
    print '</tr>';

    // Line to enter new values
    print "<tr ".$bcnd[$var].">";

    $obj = new stdClass();
    // If data was already input, we define them in obj to populate input fields.
    if (GETPOST('actionadd'))
    {
        foreach ($fieldlist as $key=>$val)
        {
            if (GETPOST($val) != '')
            	$obj->$val=GETPOST($val);
        }
    }

    $tmpaction = 'create';
    $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
    $reshook=$hookmanager->executeHooks('createDictionaryFieldlist',$parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
    $error=$hookmanager->error; $errors=$hookmanager->errors;

    if (empty($reshook))
    {
    	if ($tabname[$id] == MAIN_DB_PREFIX.'c_email_templates' && $action == 'edit')
    	{
    		fieldList($fieldlist,$obj,$tabname[$id],'hide');
    	}
    	else
    	{
    		fieldList($fieldlist,$obj,$tabname[$id],'add');
    	}
    }

    print '<td align="right" colspan="3">';
    print '</td>';
    print "</tr>";

    $fieldsforcontent = array('content');
    if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES))
    {
        $fieldsforcontent = array('content', 'content_lines');
    }
    foreach ($fieldsforcontent as $tmpfieldlist)
    {
        print '<tr class="impair nodrag nodrop nohover"><td colspan="5">';
        if ($tmpfieldlist == 'content') print '<strong>'.$form->textwithpicto($langs->trans("Content"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong><br>';
        if ($tmpfieldlist == 'content_lines') print '<strong>'.$form->textwithpicto($langs->trans("ContentForLines"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong><br>';

        if ($context != 'hide')
        {
            //print '<textarea cols="3" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'</textarea>';
            $okforextended=true;
            if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) $okforextended=false;
            $doleditor = new DolEditor($tmpfieldlist, (! empty($obj->{$tmpfieldlist})?$obj->{$tmpfieldlist}:''), '', 120, 'dolibarr_mailings', 'In', 0, false, $okforextended, ROWS_4, '90%');
            print $doleditor->Create(1);
        }
        else print '&nbsp;';
        print '</td>';
        if ($tmpfieldlist == 'content')
        {
            print '<td align="center" colspan="3" rowspan="'.(count($fieldsforcontent)).'">';
            if ($action != 'edit')
            {
                print '<input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'">';
            }
            print '</td>';
        }
        //else print '<td></td>';
        print '</tr>';
    }



    $colspan=count($fieldlist)+1;
    print '<tr><td colspan="'.$colspan.'">&nbsp;</td></tr>';	// Keep &nbsp; to have a line with enough height
}


// List of available record in database
dol_syslog("htdocs/admin/dict", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $var=true;

    $param = '&id='.$id;
    $paramwithsearch = $param;
    if ($sortorder) $paramwithsearch.= '&sortorder='.$sortorder;
    if ($sortfield) $paramwithsearch.= '&sortfield='.$sortfield;
    if (GETPOST('from')) $paramwithsearch.= '&from='.GETPOST('from','alpha');

    // There is several pages
    if ($num > $listlimit)
    {
        print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
        print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page+1).'</span></li>');
        print '</td></tr>';
    }

    // Title of lines
    print '<tr class="liste_titre'.($action != 'edit' ? ' liste_titre_add' : '').'">';
    foreach ($fieldlist as $field => $value)
    {
        // Determine le nom du champ par rapport aux noms possibles
        // dans les dictionnaires de donnees
        $showfield=1;							  	// By defaut
        $align="left";
        $sortable=1;
        $valuetoshow='';
        /*
        $tmparray=getLabelOfField($fieldlist[$field]);
        $showfield=$tmp['showfield'];
        $valuetoshow=$tmp['valuetoshow'];
        $align=$tmp['align'];
        $sortable=$tmp['sortable'];
		*/
        $valuetoshow=ucfirst($fieldlist[$field]);   // By defaut
        $valuetoshow=$langs->trans($valuetoshow);   // try to translate
        if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
        if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
        if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label"); }
    	if ($fieldlist[$field]=='type_template')   { $valuetoshow=$langs->trans("TypeOfTemplate"); }
		if ($fieldlist[$field]=='content')         { $valuetoshow=$langs->trans("Content"); $showfield=0;}
		if ($fieldlist[$field]=='content_lines')   { $valuetoshow=$langs->trans("ContentLines"); $showfield=0; }

        // Affiche nom du champ
        if ($showfield)
        {
            if (! empty($tabhelp[$id][$value]))
            {
                if (in_array($value, array('topic'))) $valuetoshow = $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2);   // Tooltip on hover
                else $valuetoshow = $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2);                             // Tooltip on hover
            }
            print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable?$fieldlist[$field]:''), ($page?'page='.$page.'&':''), $param, "align=".$align, $sortfield, $sortorder);
        }
    }

    print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page?'page='.$page.'&':''), $param, 'align="center"', $sortfield, $sortorder);
    print getTitleFieldOfList('');
    print getTitleFieldOfList('');
    print '</tr>';

    // Title line with search boxes
    print '<tr class="liste_titre">';
    $filterfound=0;
    foreach ($fieldlist as $field => $value)
    {
        if (! in_array($field, array('content', 'content_lines'))) print '<td class="liste_titre"></td>';
    }
    if (empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"></td>';
    print '</tr>';

    if ($num)
    {
        // Lines with values
        while ($i < $num)
        {

            $obj = $db->fetch_object($resql);
            //print_r($obj);
            print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';
            if ($action == 'edit' && ($rowid == (! empty($obj->rowid)?$obj->rowid:$obj->code)))
            {
                $tmpaction='edit';
                $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                $reshook=$hookmanager->executeHooks('editDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
                $error=$hookmanager->error; $errors=$hookmanager->errors;

                // Show fields
                if (empty($reshook)) fieldList($fieldlist,$obj,$tabname[$id],'edit');

                print '<td colspan="3" align="center">';
                print '<input type="hidden" name="page" value="'.$page.'">';
                print '<input type="hidden" name="rowid" value="'.$rowid.'">';
                print '<input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
                print '<div name="'.(! empty($obj->rowid)?$obj->rowid:$obj->code).'"></div>';
                print '<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'">';
                print '</td>';

                $fieldsforcontent = array('content');
                if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES))
                {
                    $fieldsforcontent = array('content', 'content_lines');
                }
                foreach ($fieldsforcontent as $tmpfieldlist)
                {
                    $showfield = 1;
                    $align = "left";
                    $valuetoshow = $obj->{$tmpfieldlist};

                    $class = 'tddict';
                    // Show value for field
                    if ($showfield) {

                        print '</tr><tr class="oddeven" nohover tr-'.$tmpfieldlist.'-'.$rowid.' "><td colspan="5">'; // To create an artificial CR for the current tr we are on
                        $okforextended = true;
                        if (empty($conf->global->FCKEDITOR_ENABLE_MAIL))
                            $okforextended = false;
                            $doleditor = new DolEditor($tmpfieldlist.'-'.$rowid, (! empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : ''), '', 140, 'dolibarr_mailings', 'In', 0, false, $okforextended, ROWS_6, '90%');
                            print $doleditor->Create(1);
                            print '</td>';
                            print '<td></td><td></td><td></td>';

                    }
                }
            }
            else
            {
              	$tmpaction = 'view';
                $parameters=array('var'=>$var, 'fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
                $reshook=$hookmanager->executeHooks('viewDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks

                $error=$hookmanager->error; $errors=$hookmanager->errors;

                if (empty($reshook))
                {
                    foreach ($fieldlist as $field => $value)
                    {
                        if (in_array($fieldlist[$field], array('content','content_lines'))) continue;
                        $showfield=1;
                    	$align="left";
                        $valuetoshow=$obj->{$fieldlist[$field]};
                        if ($value == 'type_template')
                        {
                            $valuetoshow = isset($elementList[$valuetoshow])?$elementList[$valuetoshow]:$valuetoshow;
                        }

                        $class='tddict';
						// Show value for field
						if ($showfield)
						{
                            print '<!-- '.$fieldlist[$field].' --><td align="'.$align.'" class="'.$class.'">'.$valuetoshow.'</td>';
						}
                    }
                }

                // Can an entry be erased or disabled ?
                $iserasable=1;$canbedisabled=1;$canbemodified=1;	// true by default
                $canbemodified=$iserasable;

                $url = $_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(! empty($obj->rowid)?$obj->rowid:(! empty($obj->code)?$obj->code:'')).'&code='.(! empty($obj->code)?urlencode($obj->code):'');
                if ($param) $url .= '&'.$param;
                $url.='&';

                // Active
                print '<td align="center" class="nowrap">';
                if ($canbedisabled) print '<a href="'.$url.'action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                else
             	{
             		if (in_array($obj->code, array('AC_OTH','AC_OTH_AUTO'))) print $langs->trans("AlwaysActive");
             		else if (isset($obj->type) && in_array($obj->type, array('systemauto')) && empty($obj->active)) print $langs->trans("Deprecated");
              		else if (isset($obj->type) && in_array($obj->type, array('system')) && ! empty($obj->active) && $obj->code != 'AC_OTH') print $langs->trans("UsedOnlyWithTypeOption");
                	else print $langs->trans("AlwaysActive");
                }
                print "</td>";

                // Modify link
                if ($canbemodified) print '<td align="center"><a class="reposition" href="'.$url.'action=edit">'.img_edit().'</a></td>';
                else print '<td>&nbsp;</td>';

                // Delete link
                if ($iserasable)
                {
                    print '<td align="center">';
                    if ($user->admin) print '<a href="'.$url.'action=delete">'.img_delete().'</a>';
                    //else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
                    print '</td>';
                }
                else print '<td>&nbsp;</td>';

                /*
                $fieldsforcontent = array('content');
                if (! empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES))
                {
                    $fieldsforcontent = array('content', 'content_lines');
                }
                foreach ($fieldsforcontent as $tmpfieldlist)
                {
                    $showfield = 1;
                    $align = "left";
                    $valuetoshow = $obj->{$tmpfieldlist};

                    $class = 'tddict';
                    // Show value for field
                    if ($showfield) {

                        print '</tr><tr class="oddeven" nohover tr-'.$tmpfieldlist.'-'.$i.' "><td colspan="5">'; // To create an artificial CR for the current tr we are on
                        $okforextended = true;
                        if (empty($conf->global->FCKEDITOR_ENABLE_MAIL))
                            $okforextended = false;
                        $doleditor = new DolEditor($tmpfieldlist.'-'.$i, (! empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : ''), '', 140, 'dolibarr_mailings', 'In', 0, false, $okforextended, ROWS_6, '90%', 1);
                        print $doleditor->Create(1);
                        print '</td>';
                        print '<td></td><td></td><td></td>';

                    }
                }*/
            }
            print "</tr>\n";


            $i++;
        }
    }
}
else {
    dol_print_error($db);
}

print '</table>';

print '</form>';


dol_fiche_end();

llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *  @param		string	$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		void
 */
function fieldList($fieldlist, $obj='', $tabname='', $context='')
{
	global $conf,$langs,$db;
	global $form;
	global $region_id;
	global $elementList,$sourceList,$localtax_typeList;
	global $bc;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'lang')
		{
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'lang');
			print '</td>';
		}
		// Le type de template
		elseif ($fieldlist[$field] == 'type_template')
		{
			print '<td>';
			print $form->selectarray('type_template', $elementList,(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif (in_array($fieldlist[$field], array('content','content_lines'))) continue;
		else
		{
			print '<td>';
			$size=''; $class='';
			if ($fieldlist[$field]=='code') $class='maxwidth100';
			if ($fieldlist[$field]=='private') $class='maxwidth50';
			if ($fieldlist[$field]=='position') $class='maxwidth50';
			if ($fieldlist[$field]=='libelle') $class='quatrevingtpercent';
			if ($fieldlist[$field]=='topic') $class='quatrevingtpercent';
			if ($fieldlist[$field]=='sortorder' || $fieldlist[$field]=='sens' || $fieldlist[$field]=='category_type') $size='size="2" ';
			print '<input type="text" '.$size.'class="flat'.($class?' '.$class:'').'" value="'.(isset($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}

