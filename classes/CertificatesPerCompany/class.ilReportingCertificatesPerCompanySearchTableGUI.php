<?php
require_once(dirname(dirname(__FILE__)) . '/class.ilReportingSearchTableGUI.php');

/**
 * TableGUI ilReportingCertificatesPerCompanyTableGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 *
 */
class ilReportingCertificatesPerCompanySearchTableGUI extends ilReportingSearchTableGUI {

	/**
	 * @param ilReportingGUI $a_parent_obj
	 * @param string               $a_parent_cmd
	 */
	function __construct(ilReportingGUI $a_parent_obj, $a_parent_cmd) {
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setExportFormats();
	}

	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		$cols['company_title'] =  array('txt' => $this->pl->txt('company'), 'default' => true);
		$cols['course_title'] = array('txt' => $this->pl->txt('course'), 'default' => true);
		$cols['course_id'] = array('txt' => $this->pl->txt('course_id'), 'default' => true);
        $cols['course_ext_id'] = array('txt' => $this->pl->txt('course_ext_id'), 'default' => true);
        $cols['course_type'] = array('txt' => $this->pl->txt('course_type'), 'default' => true);
        $cols['division'] =  array('txt' => $this->pl->txt('devision'), 'default' => true);
        $cols['amount_of_certificates'] = array('txt' => $this->pl->txt('amount_of_certificates'), 'default' => true);
		return $cols;
	}


    /**
     * Init filter for searching the users
     */
    public function initFilter() {
	    global $ilUser;

	    //Company
	    $arr_orgunits = array(0 => '');
	    foreach(ilObjOrgUnitTree::_getInstance()->getAllOrgunitsOnLevelX(1) as $orgunit_id){
		    $ilObjOrgUnit = new ilObjOrgUnit($orgunit_id);
		    $arr_orgunits[$orgunit_id] = $ilObjOrgUnit->getTitle();
	    }
	    asort($arr_orgunits);
	    $item = new ilMultiSelectSearchInputGUI($this->pl->txt('company'), 'company');
	    $item->setOptions($arr_orgunits);
	    $this->addFilterItemWithValue($item);
	    // Reporting-Date
	    $item = new ilDateTimeInputGUI($this->pl->txt('reporting_date'), 'reporting_date');
	    $item->setMode(2); //Input
	    //FIXME!!
	    if(!$item->getDate()) {
		    $item->setDate(new ilDateTime(time(),IL_CAL_UNIX));
	    }
	    $this->addFilterItemWithValue($item);
	    //Course Type
	    $item = new ilSelectInputGUI($this->pl->txt('course_type'), 'course_type');
	    $arr_crs_type[0] = '...';
	    $arr_crs_type[srOffering::COURSETYPE_SEMINAR] = $this->pl->txt('course_type_'.srOffering::COURSETYPE_SEMINAR);
	    $arr_crs_type[srOffering::COURSETYPE_MODULEUNIT] = $this->pl->txt('course_type_'.srOffering::COURSETYPE_MODULEUNIT);
	    $arr_crs_type[srOffering::COURSETYPE_ELEARNING] = $this->pl->txt('course_type_'.srOffering::COURSETYPE_ELEARNING);
	    $item->setOptions($arr_crs_type);
	    $this->addFilterItemWithValue($item);
		//
	    $item = new ilTextInputGUI($this->pl->txt('course_id'), 'course_id');
		$this->addFilterItemWithValue($item);
	    //
	    $item = new ilTextInputGUI($this->pl->txt('course_ext_id'), 'course_ext_id');
	    $this->addFilterItemWithValue($item);
	    //Divistion
	    $item = new ilMultiSelectSearchInputGUI($this->pl->txt('devision'), 'devision');
	    $order_by = 'title_en'; //Default Ordering title_en!
	    if($ilUser->getLanguage() == 'de') {
		    $order_by = 'title_de';
	    }
	    /** @var $emDevision emDevision */
	    foreach(emDevision::orderBy($order_by, "ASC")->get() as $emDevision) {
		    if($ilUser->getLanguage() == 'de') {
			    $arr_select_devision[$emDevision->getId()] = $emDevision->getTitleDe();
		    } else {
			    $arr_select_devision[$emDevision->getId()] = $emDevision->getTitleEn();
		    }
	    }
	    $item->setOptions($arr_select_devision);
	    $this->addFilterItemWithValue($item);
	    //Certificate Type
	    $input = new ilSelectInputGUI($this->pl->txt('certificate_type'), 'certificate_type');
	    //$opt[- 1] = $this->pl->txt("all");
	    $opt[srCertificateType::TYPE_CERT] = $this->pl->txt('certificate_type_' . srCertificateType::TYPE_CERT);
	    //$opt[srCertificateType::TYPE_CONF] = $this->pl->txt('certificate_type_' . srCertificateType::TYPE_CONF);
	    //$opt[srCertificateType::TYPE_EXT] = $this->pl->txt('certificate_type_' . srCertificateType::TYPE_EXT);
	    $input->setOptions($opt);
	    $this->addFilterItemWithValue($input);
	    parent::initFilter();
    }

	public function setExportFormats() {
		parent::setExportFormats(array(self::EXPORT_EXCEL, self::EXPORT_CSV));
	}
}

?>