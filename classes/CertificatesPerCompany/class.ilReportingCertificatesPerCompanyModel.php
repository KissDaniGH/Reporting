<?php
require_once(dirname(dirname(__FILE__)) . '/class.ilReportingModel.php');

/**
 * Class ilReportingCertificatesPerCompanyModel
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 *
 */
class ilReportingCertificatesPerCompanyModel extends ilReportingModel {

    public function __construct() {
        parent::__construct();
	    $this->pl = new ilReportingPlugin();
    }

    /**
     * Search users
     * @param array $filters
     * @return array
     */
    public function getSearchData(array $filters) {
	    global $ilUser;

	    //default
	    $ilLanguage = new ilLanguage($ilUser->getLanguage());
	    $ilLanguage->loadLanguageModule("ui_uihk_reporting");

	    $sql  = 'SELECT company.title as company_title,
				content_course.title as course_title,
				CONCAT(LEFT(LPAD(sr_ssi_crs_additions.course_id,6,"0"),3),".",RIGHT(LPAD(sr_ssi_crs_additions.course_id,6,"0"),3)) as course_id,
				sr_ssi_crs_additions.ext_course_id as course_ext_id,
				offering.course_ref_id, COUNT(cert_obj.id),
				CASE sr_ssi_crs_additions.course_type
					WHEN "1" THEN "'.$ilLanguage->txt('ui_uihk_reporting_course_type_1').'"
					WHEN "2" THEN "'.$ilLanguage->txt('ui_uihk_reporting_course_type_2').'"
				END AS course_type,
				CASE "'.$ilUser->getLanguage().'"
					WHEN "de" THEN devision.title_de
					WHEN "en" THEN devision.title_en
				END AS division,
				COUNT(cert_obj.id) as amount_of_certificates
				FROM sr_cert_obj as cert_obj
				INNER JOIN (SELECT ref_id, MAX(file_version) AS MaxFileVersion, user_id FROM sr_cert_obj GROUP BY ref_id, user_id) groupedcerts ON cert_obj.ref_id = groupedcerts.ref_id AND cert_obj.file_version = groupedcerts.MaxFileVersion AND groupedcerts.user_id =  cert_obj.user_id

				INNER JOIN sr_cert_definition ON sr_cert_definition.ref_id = cert_obj.ref_id

				INNER JOIN usr_data as user_of_certificate on user_of_certificate.usr_id = cert_obj.user_id

				INNER JOIN sr_user_history on sr_user_history.history_user_id = cert_obj.user_id AND sr_user_history.history_object_type = 3
				INNER JOIN object_reference as company_ref on company_ref.ref_id = sr_user_history.history_object_id
				INNER JOIN object_data as company on company.obj_id = company_ref.obj_id

				INNER JOIN sr_em_user_status as offering_user_status on offering_user_status.user_id = cert_obj.user_id AND offering_user_status.course_ref = cert_obj.ref_id
				INNER JOIN sr_em_offering as offering on offering.course_ref_id = offering_user_status.course_ref

				INNER JOIN object_reference as content_course_ref on content_course_ref.ref_id = offering.content_course_ref_id
				INNER JOIN object_data as content_course on content_course.obj_id = content_course_ref.obj_id

				INNER JOIN sr_ssi_crs_additions on sr_ssi_crs_additions.obj_id = content_course.obj_id
				LEFT JOIN sr_em_devision AS devision ON devision.id = sr_ssi_crs_additions.devision_id

				WHERE cert_obj.id > 0';

				if($filters["company[]"] && is_array($filters['company[]']) && count($filters['company[]'])) {
				    $sql  .= ' AND '.$this->db->in('company_ref.ref_id',$filters['company[]'], false, 'integer');
			    }
			    if ($filters['course_title']) {
				    $sql  .= ' AND content_course.title LIKE ' . $this->db->quote('%' . str_replace('*', '%', $filters['course_title']) . '%', 'text');
			    }
	            if($filters["course_id"]) {
		            $sql .= ' AND CONCAT(LEFT(LPAD(sr_ssi_crs_additions.course_id,6,"0"),3),".",RIGHT(LPAD(sr_ssi_crs_additions.course_id,6,"0"),3)) LIKE '.$this->db->quote('%'.str_replace('*','%',$filters['course_id'].'%'), 'text');
	            }
	            if($filters["course_ext_id"]) {
		            $sql  .= ' AND sr_ssi_crs_additions.ext_course_id LIKE '.$this->db->quote('%'.str_replace('*','%',$filters['course_ext_id'].'%'), "text");
	            }
		        if($filters["devision[]"] && is_array($filters['devision[]']) && count($filters['devision[]'])) {
			        $sql  .= ' AND '.$this->db->in('devision.id',$filters['devision[]'], false, 'integer');
		        }
				if($filters["course_type"]) {
					$sql  .= ' AND sr_ssi_crs_additions.course_type = '.$this->db->quote($filters['course_type'], 'integer');
				}
	            if($filters['certificate_type']) {
		            $sql  .= ' AND sr_cert_definition.type_id = '.$this->db->quote($filters['certificate_type'], 'integer');
	            }
			    if($filters['reporting_date']) {
				     $sql  .= ' AND sr_user_history.history_start_date <= '.$this->db->quote($filters['reporting_date'], 'date');
				     $sql .= ' AND (sr_user_history.history_end_date IS NULL OR sr_user_history.history_end_date >= '.$this->db->quote($filters["reporting_date"], 'date').')';
				     $sql .= ' AND cert_obj.valid_from >= ' . $this->db->quote(strtotime($filters['reporting_date']), 'integer');
				     $sql .= ' AND (cert_obj.valid_to IS NULL OR cert_obj.valid_to <= ' . $this->db->quote(strtotime($filters['reporting_date']), 'integer').')';
			    }

	            //FIXME in the public version this should be configurable
	            //Permission
	            $orgus = ilObjOrgUnitTree::_getInstance()->getOrgusWhereUserHasPermissionForOperation("ssi_report_employee");
	            $sql .= count($orgus)?' AND company_ref.ref_id IN('.implode(',', $orgus).')':' AND FALSE';

				    /*
				if ($this->pl->getConfigObject()->getValue('restricted_user_access') == ilReportingConfig::RESTRICTED_BY_LOCAL_READABILITY) {
					$refIds = $this->getRefIdsWhereUserCanAdministrateUsers();
					if (count($refIds)) {
						$sql .= ' AND user_of_certificate.time_limit_owner IN (' . implode(',', $refIds) .')';
					} else {
						$sql .= ' AND user_of_certificate.time_limit_owner IN (0)';
					}
				} elseif ($this->pl->getConfigObject()->getValue('restricted_user_access') == ilReportingConfig::RESTRICTED_BY_ORG_UNITS) {
					//TODO: check if this is performant enough.
					$users = $this->pl->getRestrictedByOrgUnitsUsers();
					$sql .= count($users)?' AND user_of_certificate.usr_id IN('.implode(',', $users).')':' AND FALSE';
				} */

	            $sql .= ' GROUP BY company.obj_id , sr_ssi_crs_additions.course_id';
		        return $this->buildRecords($sql);
    }

    public function getReportData(array $ids, array $filters) {
		global $ilUser;

	    $sql  = "SELECT usr.usr_id AS id, obj.title, CONCAT_WS(' > ', gp.title, p.title) AS path,
	             usr.firstname, usr.lastname, usr.active, usr.country, usr.department, ut.status_changed, ut.status AS user_status
	             FROM object_data as obj
	             INNER JOIN object_reference AS ref ON (ref.obj_id = obj.obj_id)
	             INNER JOIN object_data AS crs_member_role ON crs_member_role.title LIKE CONCAT('il_crs_member_', ref.ref_id)
				 INNER JOIN rbac_ua ON rbac_ua.rol_id = crs_member_role.obj_id
				 INNER JOIN usr_data AS usr ON (usr.usr_id = rbac_ua.usr_id)
				 INNER JOIN tree AS t1 ON (ref.ref_id = t1.child)
				 INNER JOIN object_reference ref2 ON (ref2.ref_id = t1.parent)
				 INNER JOIN object_data AS p ON (ref2.obj_id = p.obj_id)
				 LEFT JOIN tree AS t2 ON (ref2.ref_id = t2.child)
				 LEFT JOIN object_reference AS ref3 ON (ref3.ref_id = t2.parent)
				 LEFT JOIN object_data AS gp ON (ref3.obj_id = gp.obj_id)
				 LEFT JOIN ut_lp_marks AS ut ON (ut.obj_id = obj.obj_id AND ut.usr_id = usr.usr_id)
                 WHERE obj.type = " . $this->db->quote('crs', 'text') . " AND ref.deleted IS NULL ";
        if (count($ids)) {
            $sql .= " AND usr.usr_id IN (" . implode(',', $ids) . ")";
        }
        if ($this->pl->getConfigObject()->getValue('restricted_user_access') == ilReportingConfig::RESTRICTED_BY_LOCAL_READABILITY) {
            $refIds = $this->getRefIdsWhereUserCanAdministrateUsers();
            if (count($refIds)) {
                $sql .= ' AND usr.time_limit_owner IN (' . implode(',', $refIds) .')';
            } else {
                $sql .= ' AND usr.time_limit_owner IN (0)';
            }
        } elseif ($this->pl->getConfigObject()->getValue('restricted_user_access') == ilReportingConfig::RESTRICTED_BY_ORG_UNITS) {
	        //TODO: check if this is performant enough.
			$users = $this->pl->getRestrictedByOrgUnitsUsers();
	        $sql .= count($users)?' AND usr.usr_id IN('.implode(',', $users).') ':' AND FALSE ';
        }
        if (count($filters)) {
            if ($filters['status'] != '') {
                $sql .= ' AND ut.status = ' . $this->db->quote(($filters['status']-1), 'text');
            }
            if ($date = $filters['status_changed_from']) {
                $sql .= ' AND ut.status_changed >= ' . $this->db->quote($date, 'date');
            }
            if ($date = $filters['status_changed_to']) {
                /** @var $date ilDateTime */
                $date->increment(ilDateTime::DAY, 1);
                $sql .= ' AND ut.status_changed <= ' . $this->db->quote($date, 'date');
                $date->increment(ilDateTime::DAY, -1);
            }
        }
        $sql .= " ORDER BY usr.usr_id, usr.lastname, usr.firstname";
//        echo $sql; die();
        return $this->buildRecords($sql);
    }



}