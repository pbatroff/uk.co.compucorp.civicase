<?php

use Civi\CCase\Utils as CiviCaseUtils;
use CRM_Civicase_APIHelpers_CasesByContactInvolved as CasesByContactInvolved;

/**
 * Case.getdetailscount API
 *
 * Provides a count of cases but properly respects filters unlike `getcount`.
 *
 * @param array $params
 * @return array API result
 * @throws API_Exception
 */
function civicrm_api3_case_getdetailscount($params) {
  $params['options'] = CRM_Utils_Array::value('options', $params, []);
  $params['options']['is_count'] = 1;

  // Remove unnecesary parameters:
  unset($params['return'], $params['sequential']);

  $filters = getdetailsfilter($params);
  $query = _civicrm_api3_advanced_get2('Case', $filters['params']);
  $query->merge($filters['sql']);
  // $query->clearSelectFields();
  $query->setSelectFields(['COUNT(DISTINCT(a.id))' => 'c']);
  $results = $query->run();

  return array_shift($results)['c'];
}

function getdetailsfilter($params) {
  $resultMetadata = [];
  $params += ['return' => []];
  if (is_string($params['return'])) {
    $params['return'] = explode(',', str_replace(' ', '', $params['return']));
  }
  $toReturn = $params['return'];
  $params['options'] = CRM_Utils_Array::value('options', $params, []);
  $extraReturnProperties = [
    'activity_summary', 'last_update', 'activity_count', 'category_count',
    'unread_email_count', 'related_case_ids',
  ];
  $params['return'] = array_diff($params['return'], $extraReturnProperties);

  // Support additional sort params.
  $sql = _civicrm_api3_case_getdetails_extrasort2($params);

  // Add clause to search by manager.
  if (!empty($params['case_manager'])) {
    CRM_Civicase_APIHelpers_CasesByManager::filter($sql, $params['case_manager']);
  }

  if (!empty($params['has_role'])) {
    _civicrm_api3_case_getdetails_handle_role_filters2($sql, $params);
  }

  // Add clause to search by non manager role and non client.
  if (!empty($params['contact_involved'])) {
    CasesByContactInvolved::filter(
      $sql,
      $params['contact_involved'],
      $params['has_activities_for_involved_contact']
    );

    if (!$params['options']['is_count']) {
      $sql->groupBy('a.id');
    }
  }

  if (!empty($params['exclude_for_client_id'])) {
    $sql->where('a.id NOT IN (SELECT case_id FROM civicrm_case_contact WHERE contact_id = #contact_id)', [
      '#contact_id' => $params['exclude_for_client_id'],
    ]);
  }

  // Filter deleted contacts from results.
  if (isset($params['contact_is_deleted'])) {
    $isDeleted = (int) $params['contact_is_deleted'];
    $sql->where("a.id IN (SELECT case_id FROM civicrm_case_contact ccc, civicrm_contact cc WHERE ccc.contact_id = cc.id AND cc.is_deleted = $isDeleted)");
  }

  // Set page number dynamically based on selected record.
  if (!empty($params['options']['page_of_record'])) {
    $prParams = ['sequential' => 1] + $params;
    $prParams['return'] = ['id'];
    $prParams['options']['limit'] = $prParams['options']['offset'] = 0;
    foreach (CRM_Utils_Array::value('values', civicrm_api3_case_get($prParams), []) as $num => $case) {
      if ($case['id'] == $params['options']['page_of_record']) {
        $resultMetadata['page'] = floor($num / $params['options']['limit']) + 1;
        $params['options']['offset'] = $params['options']['limit'] * ($resultMetadata['page'] - 1);
        break;
      }
    }
  }

  return [
    'params' => $params,
    'sql' => $sql,
  ];
}

class HelloSql extends \Civi\API\Api3SelectQuery {
  public function clearSelectFields() {
    // $this->entityFieldNames = [];
    // $this->apiFieldSpec = [];
  }

  public function setSelectFields($fields) {
    $this->selectFields = $fields;
  }
}

function _civicrm_api3_advanced_get2($entityName, $params) {
  $options = _civicrm_api3_get_options_from_params($params);

  $query = new HelloSql($entityName, CRM_Utils_Array::value('check_permissions', $params, FALSE));
  $query->where = $params;
  if (!$options['is_count']) {
    $query->select = array_keys(array_filter($options['return']));
    $query->orderBy = $options['sort'];
  }
  $query->limit = $options['limit'];
  $query->offset = $options['offset'];

  return $query;
}

function _civicrm_api3_case_getdetails_join_client2(CRM_Utils_SQL_Select $sql, array $params) {
  _civicase_prepare_param_for_filtering2($params, 'contact');

  $contactFilter = CRM_Core_DAO::createSQLFilter('case_client.contact_id', $params['contact']);

  $sql->join('case_client', "
    LEFT JOIN civicrm_case_contact AS case_client
    ON case_client.case_id = civicrm_case.id
    AND $contactFilter
  ");
}

function _civicrm_api3_case_getdetails_join_relationships2(CRM_Utils_SQL_Select $sql, array $params, $options = []) {
  _civicase_prepare_param_for_filtering2($params, 'contact');

  $contactFilter = CRM_Core_DAO::createSQLFilter('case_relationship.contact_id_b', $params['contact']);
  $joinClause = "
    {$options['joinType']} civicrm_relationship AS case_relationship
    ON case_relationship.case_id = civicrm_case.id
    AND case_relationship.is_active = 1
    AND $contactFilter
  ";

  if (!empty($params['role_type'])) {
    _civicase_prepare_param_for_filtering2($params, 'role_type');

    $roleTypeFilter = CRM_Core_DAO::createSQLFilter('case_relationship.relationship_type_id', $params['role_type']);
    $joinClause .= "AND $roleTypeFilter";
  }

  $sql->join('civicrm_relationship', $joinClause);
}

function _civicase_prepare_param_for_filtering2(array &$params, $paramName) {
  if (!is_array($params[$paramName])) {
    $params[$paramName] = [
      '=' => $params[$paramName],
    ];
  }
}

function _civicrm_api3_case_getdetails_handle_role_filters2(CRM_Utils_SQL_Select $sql, array $params) {
  $hasRole = $params['has_role'];
  $canBeAClient = !isset($hasRole['can_be_client']) || $hasRole['can_be_client'];
  $hasOtherRolesThanClient = isset($hasRole['role_type']);
  $isAllCaseRolesTrue = $hasRole['all_case_roles_selected'];
  $roleSubQuery = new CRM_Utils_SQL_Select('civicrm_case');

  $roleSubQuery->select('civicrm_case.id');
  $roleSubQuery->groupBy('civicrm_case.id');

  if ($canBeAClient) {
    _civicrm_api3_case_getdetails_join_client2($roleSubQuery, $hasRole);

    if ($hasOtherRolesThanClient || $isAllCaseRolesTrue) {
      _civicrm_api3_case_getdetails_join_relationships2($roleSubQuery, $hasRole, [
        'joinType' => 'LEFT JOIN',
      ]);

      $roleSubQuery->where('case_relationship.case_id IS NOT NULL
      OR case_client.case_id IS NOT NULL');
    }
    else {
      $roleSubQuery->where('case_client.case_id IS NOT NULL');
    }
  }
  else {
    _civicrm_api3_case_getdetails_join_relationships2($roleSubQuery, $hasRole, [
      'joinType' => 'JOIN',
    ]);
  }

  $roleSubQueryString = $roleSubQuery->toSql();

  $sql->join('case_roles', "
    JOIN ($roleSubQueryString) AS case_roles
    ON case_roles.id = a.id
  ");
}

class MyMy extends CRM_Utils_SQL_Select {
  public function clearSelect() {
    $this->select = [];
  }
}

/**
 * Support extra sorting in case.getdetails.
 *
 * @param array $params
 *   Parameters.
 *
 * @return \CRM_Utils_SQL_Select
 *   Sql Select query.
 *
 * @throws \API_Exception
 */
function _civicrm_api3_case_getdetails_extrasort2(array &$params) {
  $sql = new MyMy(NULL);
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['sort'])) {
    $sort = explode(', ', $options['sort']);

    // For each one of our special fields we swap it for the placeholder (1)
    // so it will be ignored by the case api.
    foreach ($sort as $index => &$sortString) {
      // Get sort field and direction.
      list($sortField, $dir) = array_pad(explode(' ', $sortString), 2, 'ASC');
      list($sortJoin, $sortField) = array_pad(explode('.', $sortField), 2, 'id');
      // Sort by case manager.
      if ($sortJoin == 'case_manager') {
        // Validate inputs.
        if (!array_key_exists($sortField, CRM_Contact_DAO_Contact::fieldKeys()) || ($dir != 'ASC' && $dir != 'DESC')) {
          throw new API_Exception("Unknown field specified for sort. Cannot order by '$sortString'");
        }
        CiviCaseUtils::joinOnRelationship($sql, 'manager');
        $sql->orderBy("manager.$sortField $dir", NULL, $index);
        $sortString = '(1)';
      }
      // Sort by my role.
      elseif ($sortJoin == 'my_role') {
        $me = CRM_Core_Session::getLoggedInContactID();
        // Validate inputs.
        if (!array_key_exists($sortField, CRM_Contact_DAO_RelationshipType::fieldKeys()) || ($dir != 'ASC' && $dir != 'DESC')) {
          throw new API_Exception("Unknown field specified for sort. Cannot order by '$sortString'");
        }
        $sql->join('ccc', 'LEFT JOIN (SELECT * FROM civicrm_case_contact WHERE id IN (SELECT MIN(id) FROM civicrm_case_contact GROUP BY case_id)) AS ccc ON ccc.case_id = a.id');
        $sql->join('my_relationship', "LEFT JOIN civicrm_relationship AS my_relationship ON ccc.contact_id = my_relationship.contact_id_a AND my_relationship.is_active AND my_relationship.contact_id_b = $me AND my_relationship.case_id = a.id");
        $sql->join('my_relationship_type', 'LEFT JOIN civicrm_relationship_type AS my_relationship_type ON my_relationship_type.id = my_relationship.relationship_type_id');
        $sql->orderBy("my_relationship_type.$sortField $dir", NULL, $index);
        $sortString = '(1)';
      }
      // Sort by upcoming activities.
      elseif (strpos($sortString, 'next_activity') === 0) {
        $sortString = '(1)';
        $category = str_replace('next_activity_category_', '', $sortJoin);
        $actClause = '';
        // If we're limiting to a particiular category.
        if ($category != 'next_activity') {
          $actTypes = civicrm_api3('OptionValue', 'get', [
            'sequential' => 1,
            'option_group_id' => "activity_type",
            'options' => ['limit' => 0],
            'grouping' => ['LIKE' => "%$category%"],
          ]);
          $actTypes = implode(',', CRM_Utils_Array::collect('value', $actTypes['values']));
          if (!$actTypes) {
            continue;
          }
          $actClause = "AND activity_type_id IN ($actTypes)";
        }
        $incomplete = implode(',', array_keys(\CRM_Activity_BAO_Activity::getStatusesByType(\CRM_Activity_BAO_Activity::INCOMPLETE)));
        $sql->join($sortJoin, "LEFT JOIN (
            SELECT MIN(activity_date_time) as activity_date_time, case_id
            FROM civicrm_activity, civicrm_case_activity
            WHERE civicrm_activity.id = civicrm_case_activity.activity_id $actClause AND status_id IN ($incomplete) AND is_current_revision = 1 AND is_test <> 1
            GROUP BY case_id
          ) AS $sortJoin ON $sortJoin.case_id = a.id");
        $sql->orderBy("$sortJoin.activity_date_time $dir", NULL, $index);
      }
    }
    // Remove our extra sort params so the basic_get function doesn't see them.
    $params['options']['sort'] = implode(', ', $sort);
    unset($params['option_sort'], $params['option.sort'], $params['sort']);
  }

  return $sql;
}
