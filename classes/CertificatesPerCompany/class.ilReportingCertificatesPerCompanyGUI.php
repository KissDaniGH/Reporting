<?php
require_once(dirname(dirname(__FILE__)) . '/class.ilReportingGUI.php');
require_once('class.ilReportingCertificatesPerCompanySearchTableGUI.php');
require_once('class.ilReportingCertificatesPerCompanyModel.php');

/**
 * GUI-Class ilReportingCertificatesPerCompanyGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 *
 * @ilCtrl_IsCalledBy ilReportingCertificatesPerCompanyGUI: ilRouterGUI
 */
class ilReportingCertificatesPerCompanyGUI extends ilReportingGUI {

    function __construct() {
		parent::__construct();
        $this->model = new ilReportingCertificatesPerCompanyModel();
	}

	public function executeCommand() {
        parent::executeCommand();
    }


    /**
     * Display table for searching the users
     */
    public function search() {
        //$this->tpl->setTitle($this->pl->txt('report_view_on_company'));
        $this->table = new ilReportingCertificatesPerCompanySearchTableGUI($this, 'search');
        //$this->table->setTitle($this->pl->txt('search_users'));
        parent::search();
    }


    public function applyFilterSearch() {
        $this->table = new ilReportingCertificatesPerCompanySearchTableGUI($this, $this->getStandardCmd());
        parent::applyFilterSearch();
    }

    public function resetFilterSearch() {
        $this->table = new ilReportingCertificatesPerCompanySearchTableGUI($this, $this->getStandardCmd());
        parent::resetFilterSearch();
    }

    public function getAvailableExports() {
        $exports = array(
            self::EXPORT_EXCEL_FORMATTED => 'export_custom_excel',
        );
        if ($this->isActiveJasperReports()) $exports[self::EXPORT_PDF] = 'export_pdf';
        return $exports;
    }

}

?>