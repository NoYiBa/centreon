<?php
/**
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

/**
 *
 * Class for Access Control List management
 * @author jmathis
 *
 */
class CentreonACL
{
 	private $userID; /* ID of the user */
 	private $parentTemplates = null;
 	public $admin; /* Flag that tells us if the user is admin or not */
 	private $accessGroups = array(); /* Access groups the user belongs to */
 	private $resourceGroups = array(); /* Resource groups the user belongs to */
 	public  $hostGroups = array(); /* Hostgroups the user can see */
 	protected $pollers = array(); /* Pollers the user can see */
 	private $hostGroupsAlias = array(); /* Hostgroups by alias the user can see */
 	private $serviceGroups = array(); /* Servicegroups the user can see */
 	private $serviceGroupsAlias = array(); /* Servicegroups by alias the user can see */
 	private $serviceCategories = array(); /* Service categories the user can see */
    private $hostCategories = array();
 	private $actions = array(); /* Actions the user can do */
 	private $hostGroupsFilter = array();
 	private $serviceGroupsFilter = array();
 	private $serviceCategoriesFilter = array();
 	public  $topology = array();
 	public  $topologyStr = "";
 	private $metaServices = array();
 	private $metaServiceStr = "";

 	/*
 	 *  Constructor that takes the user_id
 	 */
 	function CentreonACL($user_id, $is_admin = null)
 	{
 		$this->userID = $user_id;

 		if (!isset($is_admin)) {
 			$localPearDB = new CentreonDB();
 			$rq = "SELECT contact_admin FROM `contact` WHERE contact_id = '".$user_id."' LIMIT 1";
 			$RES = $localPearDB->query($rq);
 			$row = $RES->fetchRow();
 			$this->admin = $row['contact_admin'];
 		} else {
 			$this->admin = $is_admin;
 		}

 		if (!$this->admin) {
	 		$this->setAccessGroups();
	 		$this->setResourceGroups();
	 		$this->setHostGroups();
	 		$this->setPollers();
	 		$this->setServiceGroups();
	 		$this->setServiceCategories();
            $this->setHostCategories();
	 		$this->setMetaServices();
	 		$this->setActions();
 		}

		$this->setTopology();
 		$this->getACLStr();

 	}

 	/*
 	 *  Function that will reset ACL
 	 */
 	private function resetACL()
 	{
 	    $this->parentTemplates = null;
 		$this->accessGroups = array();
	 	$this->resourceGroups = array();
	 	$this->hostGroups = array();
	 	$this->serviceGroups = array();
	 	$this->serviceCategories = array();
	 	$this->actions = array();
	 	$this->topology = array();
	 	$this->pollers = array();
	 	$this->setAccessGroups();
 		$this->setResourceGroups();
 		$this->setHostGroups();
 		$this->setPollers();
 		$this->setServiceGroups();
 		$this->setServiceCategories();
        $this->setHostCategories();
 		$this->setMetaServices();
 		$this->setTopology();
 		$this->getACLStr();
 		$this->setActions();
 	}

 	/*
 	 *  Function that will check whether or not the user needs to rebuild his ACL
 	 */
 	private function checkUpdateACL()
 	{
 		global $pearDB;

 		if (is_null($this->parentTemplates)) {
 		    $this->loadParentTemplates();
 		}

 		if (!$this->admin) {
	 		$query = "SELECT update_acl FROM session WHERE update_acl = '1' AND user_id IN (" . join(', ', $this->parentTemplates) . ")";
	 		$DBRES = $pearDB->query($query);
	 		if ($DBRES->numRows()) {
	 			$pearDB->query("UPDATE session SET update_acl = '0' WHERE user_id IN (" . join(', ', $this->parentTemplates) . ")");
	 			$this->resetACL();
	 		}
 		}
 	}

 	/*
 	 *  Setter functions
 	 */

 	/*
 	 *  Access groups Setter
 	 */
 	private function setAccessGroups()
 	{
 		global $pearDB;

 		if (is_null($this->parentTemplates)) {
            $this->loadParentTemplates();
 		}

        if (count($this->parentTemplates) != 0)
        {
            $query = "SELECT acl.acl_group_id, acl.acl_group_name " .
                    "FROM acl_groups acl, acl_group_contacts_relations agcr " .
                    "WHERE acl.acl_group_id = agcr.acl_group_id " .
                    "AND agcr.contact_contact_id IN (" . join(', ', $this->parentTemplates) . ") " .
                    "AND acl.acl_group_activate = '1' " .
                    "ORDER BY acl.acl_group_name ASC";
            $DBRESULT = $pearDB->query($query);
            while ($row = $DBRESULT->fetchRow()) {
                $this->accessGroups[$row['acl_group_id']] = $row['acl_group_name'];
            }
            $DBRESULT->free();

            $query = "SELECT acl.acl_group_id, acl.acl_group_name " .
                    "FROM acl_groups acl, acl_group_contactgroups_relations agcgr, contactgroup_contact_relation cgcr " .
                    "WHERE acl.acl_group_id = agcgr.acl_group_id " .
                    "AND cgcr.contactgroup_cg_id = agcgr.cg_cg_id " .
                    "AND cgcr.contact_contact_id IN (" . join(', ', $this->parentTemplates) . ") " .
                    "AND acl.acl_group_activate = '1' " .
                    "ORDER BY acl.acl_group_name ASC";

            $DBRESULT = $pearDB->query($query);
            while ($row = $DBRESULT->fetchRow()) {
                $this->accessGroups[$row['acl_group_id']] = $row['acl_group_name'];
            }
            $DBRESULT->free();
        }
 	}

 	/*
 	 *  Resource groups Setter
 	 */
 	private function setResourceGroups()
 	{
 		global $pearDB;

 		$query = "SELECT acl.acl_res_id, acl.acl_res_name " .
 				"FROM acl_resources acl, acl_res_group_relations argr " .
 				"WHERE acl.acl_res_id = argr.acl_res_id " .
 				"AND argr.acl_group_id IN (".$this->getAccessGroupsString().") " .
 				"AND acl.acl_res_activate = '1' " .
                "ORDER BY acl.acl_res_name ASC";
 		$DBRESULT = $pearDB->query($query);
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->resourceGroups[$row['acl_res_id']] = $row['acl_res_name'];
 		}
 		$DBRESULT->free();
 	}


 	/*
 	 *  Access groups Setter
 	 */
 	private function setHostGroups()
 	{
 		global $pearDB;

 		$query = "SELECT hg.hg_id, hg.hg_name, hg.hg_alias, arhr.acl_res_id " .
 				"FROM hostgroup hg, acl_resources_hg_relations arhr " .
 				"WHERE hg.hg_id = arhr.hg_hg_id " .
 				"AND arhr.acl_res_id IN (".$this->getResourceGroupsString().") " .
 				"AND hg.hg_activate = '1' " .
                "ORDER BY hg.hg_name ASC";
 		$DBRESULT = $pearDB->query($query);
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->hostGroups[$row['hg_id']] = $row['hg_name'];
 			$this->hostGroupsAlias[$row['hg_id']] = $row['hg_alias'];
 			$this->hostGroupsFilter[$row['acl_res_id']][$row['hg_id']] = $row['hg_id'];
 		}
 		$DBRESULT->free();
 	}

	/**
 	 *  Poller Setter
 	 */
 	private function setPollers()
 	{
 		global $pearDB;

 		$query = "SELECT ns.id, ns.name, arpr.acl_res_id " .
 				"FROM nagios_server ns, acl_resources_poller_relations arpr " .
 				"WHERE ns.id = arpr.poller_id " .
 				"AND arpr.acl_res_id IN (".$this->getResourceGroupsString().") " .
 				"AND ns.ns_activate = '1' ".
                "ORDER BY ns.name ASC";
 		$DBRESULT = $pearDB->query($query);
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->pollers[$row['id']] = $row['name'];
 		}
 		$DBRESULT->free();
 	}

 	/**
 	 *  Service groups Setter
 	 */
 	private function setServiceGroups()
 	{
 		global $pearDB;

 		$query = "SELECT sg.sg_id, sg.sg_name, sg.sg_alias, arsr.acl_res_id " .
 				"FROM servicegroup sg, acl_resources_sg_relations arsr " .
 				"WHERE sg.sg_id = arsr.sg_id " .
 				"AND arsr.acl_res_id IN (".$this->getResourceGroupsString().") " .
 				"AND sg.sg_activate = '1' ".
                "ORDER BY sg.sg_name ASC";
 		$DBRESULT = $pearDB->query($query);
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->serviceGroups[$row['sg_id']] = $row['sg_name'];
 			$this->serviceGroupsAlias[$row['sg_id']] = $row['sg_alias'];
 			$this->serviceGroupsFilter[$row['acl_res_id']][$row['sg_id']] = $row['sg_id'];
 		}
 		$DBRESULT->free();
 	}

 	/*
 	 *  Service categories Setter
 	 */
 	private function setServiceCategories()
 	{
 		global $pearDB;

 		$query = "SELECT sc.sc_id, sc.sc_name, arsr.acl_res_id " .
 				"FROM service_categories sc, acl_resources_sc_relations arsr " .
 				"WHERE sc.sc_id = arsr.sc_id " .
 				"AND arsr.acl_res_id IN (".$this->getResourceGroupsString().") " .
 				"AND sc.sc_activate = '1' ".
                "ORDER BY sc.sc_name ASC";

 		$DBRESULT = $pearDB->query($query);
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->serviceCategories[$row['sc_id']] = $row['sc_name'];
 			$this->serviceCategoriesFilter[$row['acl_res_id']][$row['sc_id']] = $row['sc_id'];
 		}
 		$DBRESULT->free();
 	}

    /*
     * Host categories setter
     */
    private function setHostCategories()
    {
        global $pearDB;

        $query = "SELECT hc.hc_id, hc.hc_name, arhr.acl_res_id " .
                "FROM hostcategories hc, acl_resources_hc_relations arhr " .
                "WHERE hc.hc_id = arhr.hc_id " .
                "AND arhr.acl_res_id IN (".$this->getResourceGroupsString().") " .
                "AND hc.hc_activate = '1' " .
                "ORDER BY hc.hc_name ASC";

        $res = $pearDB->query($query);
        while ($row = $res->fetchRow()) {
            $this->hostCategories[$row['hc_id']] = $row['hc_name'];
        }
    }

  	/*
 	 *  Access meta Setter
 	 */
 	private function setMetaServices()
 	{
 		global $pearDB;

 		$query = "SELECT ms.meta_id, ms.meta_name, arsr.acl_res_id " .
 				"FROM meta_service ms, acl_resources_meta_relations arsr " .
 				"WHERE ms.meta_id = arsr.meta_id " .
 				"AND arsr.acl_res_id IN (".$this->getResourceGroupsString().") ".
                "ORDER BY ms.meta_name ASC";
 		$DBRESULT = $pearDB->query($query);
 		$this->metaServiceStr = "";
 		while ($row = $DBRESULT->fetchRow()) {
 			$this->metaServices[$row['meta_id']] = $row['meta_name'];
 			if ($this->metaServiceStr != "")
 				$this->metaServiceStr .= ",";
 			$this->metaServiceStr .= "'".$row['meta_id']."'";
 		}
        if (!$this->metaServiceStr) {
            $this->metaServiceStr = "''";
        }
 		$DBRESULT->free();
 	}

 	/*
 	 *  Actions Setter
 	 */
 	private function setActions()
 	{
		global $pearDB;

		$query = "SELECT ar.acl_action_name " .
				"FROM acl_group_actions_relations agar, acl_actions a, acl_actions_rules ar " .
				"WHERE a.acl_action_id = agar.acl_action_id " .
				"AND agar.acl_action_id = ar.acl_action_rule_id " .
				"AND a.acl_action_activate = '1'" .
				"AND agar.acl_group_id IN (".$this->getAccessGroupsString().") ".
                "ORDER BY ar.acl_action_name ASC";
		$DBRESULT = $pearDB->query($query);
		while ($row = $DBRESULT->fetchRow()) {
			$this->actions[$row['acl_action_name']] = $row['acl_action_name'];
		}
		$DBRESULT->free();
 	}


 	/*
 	 *  Topology setter
 	 */
 	private function setTopology() {
	  	global $pearDB;

	  	if ($this->admin) {
 			$query = "SELECT topology_page FROM topology WHERE topology_page IS NOT NULL";
 			$DBRES = $pearDB->query($query);
 			while ($row = $DBRES->fetchRow()) {
 				$this->topology[$row['topology_page']] = 1;
 			}
			$DBRES->free();
 		} else {
		  	if (count($this->accessGroups) > 0) {
		  	 	/*
		  	 	 * If user is in an access group
		  	 	 */
			  	$str_topo = "";
				$DBRESULT = $pearDB->query(	"SELECT DISTINCT acl_group_topology_relations.acl_topology_id " .
												"FROM `acl_group_topology_relations`, `acl_topology`, `acl_topology_relations` " .
												"WHERE acl_topology_relations.acl_topo_id = acl_topology.acl_topo_id " .
												"AND acl_group_topology_relations.acl_group_id IN (". $this->getAccessGroupsString() .")" .
												"AND acl_topology.acl_topo_activate = '1'");

				if (!$DBRESULT->numRows()){
					$this->topology[1] = 1;
			  		$this->topology[101] = 1;
			  		$this->topology[10101] = 1;
				} else {
					$count = 0;
					$tmp_topo_page = array();
					while ($topo_group = $DBRESULT->fetchRow()) {
						$DBRESULT2 = $pearDB->query(	"SELECT topology_topology_id, acl_topology_relations.access_right " .
				  										"FROM `acl_topology_relations`, acl_topology " .
				  										"WHERE acl_topology_relations.acl_topo_id = '".$topo_group["acl_topology_id"]."' " .
														"AND acl_topology.acl_topo_activate = '1' " .
														"AND acl_topology.acl_topo_id = acl_topology_relations.acl_topo_id");

						while ($topo_page = $DBRESULT2->fetchRow()) {
							if ($str_topo != "") {
								$str_topo .= ", ";
							}
							$str_topo .= $topo_page["topology_topology_id"];
					 		$count++;
                                                        if (!isset($tmp_topo_page[$topo_page['topology_topology_id']]) || !$tmp_topo_page[$topo_page['topology_topology_id']]) {
                                                            $tmp_topo_page[$topo_page["topology_topology_id"]] = $topo_page["access_right"];
                                                        }
						}
						$DBRESULT2->free();
					}
					$DBRESULT->free();
					unset($topo_group);
					unset($topo_page);
					$count ? $ACL = "topology_id IN ($str_topo) AND " : $ACL = "";
					unset($DBRESULT);

					$DBRESULT = $pearDB->query("SELECT topology_page, topology_id FROM topology FORCE INDEX (`PRIMARY`) WHERE $ACL topology_page IS NOT NULL");
					while ($topo_page = $DBRESULT->fetchRow()) {
						$this->topology[$topo_page["topology_page"]] = $tmp_topo_page[$topo_page["topology_id"]];
					}
					$DBRESULT->free();
					unset($topo_page);
					unset($tmp_topo_page);
				}
				unset($DBRESULT);
		  	} else  {
		  		/*
		  		 * If user isn't in an access group
		  		 */
		  		$this->topology[1] = 1;
		  		$this->topology[101] = 1;
		  		$this->topology[10101] = 1;
		  	}
	  	}
 	}

 	/*
 	 *  Getter functions
 	 */

 	/*
 	 * Get ACL by string
 	 */
 	public function getACLStr()
 	{
 		foreach ($this->topology as $key => $tmp) {
	  		if (isset($key) && $key) {
		  		if ($this->topologyStr != "")
		  			$this->topologyStr .= ", ";
		  		$this->topologyStr .= "'".$key."'";
	  		}
	  	}
	  	unset($key);
	  	if (!$this->topologyStr) {
	  		$this->topologyStr = "\'\'";
	  	}
 	}

 	/*
 	 *  Access groups Getter
 	 */
 	public function getAccessGroups() {
 		return ($this->accessGroups);
 	}

 	/*
 	 *  Access groups string Getter
 	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getAccessGroupsString($flag = null, $escape = true) {
 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->accessGroups as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch ($flag) {
 				case "ID" :
 				    $string .= "'".$key."'";
 				    break;
 				case "NAME" :
 				    if ($escape === true) {
 				        $string .= "'".CentreonDB::escape($value)."'";
 				    } else {
 				        $string .= "'".$value."'";
 				    }
 				    break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "'0'";
 		}
 		return $string;
 	}

 	/*
 	 *  Resource groups Getter
 	 */
 	public function getResourceGroups()
 	{
 		return $this->resourceGroups;
 	}

 	/*
 	 *  Resource groups string Getter
 	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getResourceGroupsString($flag = null, $escape = true)
 	{
 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->resourceGroups as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch($flag) {
 				case "ID" :
 				    $string .= "'".$key."'";
 				    break;
 				case "NAME" :
 				    if ($escape === true) {
 				        $string .= "'".CentreonDB::escape($value)."'";
 				    } else {
 				        $string .= "'".$value."'";
 				    }
 				    break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "''";
 		}
 		return $string;
 	}

 	/*
 	 *  Hostgroups Getter
 	 */
 	public function getHostGroups($flag = null)
 	{
 		$this->checkUpdateACL();
 		if (isset($flag) && $flag == "ALIAS") {
 			return $this->hostGroupsAlias;
 		}
 		return $this->hostGroups;
 	}


	/*
 	 *  Poller Getter
 	 */
 	public function getPollers()
 	{
 		return $this->pollers;
 	}

 	/*
 	 *  Hostgroups string Getter
  	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getHostGroupsString($flag = null)
 	{
 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->hostGroups as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch($flag) {
 				case "ID" : $string .= "'".$key."'"; break;
 				case "NAME" : $string .= "'".$value."'"; break;
 				case "ALIAS" : $string .= "'".addslashes($this->hostGroupsAlias[$key])."'"; break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "''";
 		}
 		return $string;
 	}

 	/*
 	 *  Poller string Getter
  	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getPollerString($flag = null, $escape = true)
 	{
 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->pollers as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch ($flag) {
 				case "ID" : $string .= "'".$key."'"; break;
 				case "NAME" :
 				    if ($escape === true) {
 				        $string .= "'".CentreonDB::escape($value)."'";
 				    } else {
 				        $string .= "'".$value."'";
 				    }
 				    break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "''";
 		}
 		return $string;
 	}

 	/*
 	 *  Service groups Getter
 	 */
 	public function getServiceGroups()
 	{
 		return $this->serviceGroups;
 	}

 	/*
 	 *  Service groups string Getter
   	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getServiceGroupsString($flag = null, $escape = true)
 	{
 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->serviceGroups as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch ($flag) {
 				case "ID" :
 				    $string .= "'".$key."'";
 				    break;
 				case "NAME" :
 				    if ($escape === true) {
 				        $string .= "'".CentreonDB::escape($value)."'";
 				    } else {
 				        $string .= "'".$value."'";
 				    }
 				    break;
 				case "ALIAS" :
 				    $string .= "'".$this->serviceGroupsAlias[$key]."'";
 				    break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "''";
 		}
 		return $string;
 	}

 	/*
 	 *  Service categories Getter
 	 */
 	public function getServiceCategories()
 	{
 		return $this->serviceCategories;
 	}

    /**
     * getHostCategories
     *
     * @access public
     * @return void
     */
    public function getHostCategories()
    {
        return $this->hostCategories;
    }

 	/*
 	 *  Service categories string Getter
  	 *  Possible flags :
 	 *  - ID => will return the id's of the element
 	 *  - NAME => will return the names of the element
 	 */
 	public function getServiceCategoriesString($flag = null, $escape = true)
 	{
        global $pearDB;

 		$string = "";
 		$i = 0;
 		if (!isset($flag)) {
 			$flag = "ID";
 		}
 		$flag = strtoupper($flag);
 		foreach ($this->serviceCategories as $key => $value) {
 			if ($i) {
 				$string .= ", ";
 			}
 			switch($flag) {
 				case "ID" :
 				    $string .= "'".$key."'";
 				    break;
 				case "NAME" :
 				    if ($escape === true) {
 				        $string .= "'".$pearDB->escape($value)."'";
 				    } else {
 				        $string .= "'".$value."'";
 				    }
 				    break;
 				default : $string .= "'".$key."'"; break;
 			}
 			$i++;
 		}
 		if (!$i) {
 			$string = "''";
 		}
 		return $string;
 	}

    /**
     * getHostCategoriesString
     *
     * @param mixed $flag
     * @param mixed $escape
     * @access public
     * @return void
     */
    public function getHostCategoriesString($flag = null, $escape = true)
    {
        global $pearDB;

        $string = "";
        $i = 0;
        if (!isset($flag)) {
            $flag = "ID";
        }
        $flag = strtoupper($flag);
        foreach ($this->hostCategories as $key => $value) {
            if ($i) {
                $string .= ", ";
            }
            switch($flag) {
                case "ID" :
                    $string .= "'".$key."'";
                    break;
                case "NAME" :
                    if ($escape === true) {
                        $string .= "'".$pearDB->escape($value)."'";
                    } else {
                        $string .= "'".$value."'";
                    }
                    break;
                default : $string .= "'".$key."'"; break;
            }
            $i++;
        }
        if (!$i) {
            $string = "''";
        }
        return $string;
    }

    /*
     *  Hosts string Getter
     *  Possible flags :
     *  - ID => will return the id's of the element
     *  - NAME => will return the names of the element
     */
    public function getHostsString($flag = null, $pearDBndo, $escape = true)
    {
        $this->checkUpdateACL();

        if (!isset($flag)) {
            $flag = "ID";
        }
        $flag = strtoupper($flag);
        $string = "";
        $i = 0;
        $groupIds = array_keys($this->accessGroups);
        if (count($groupIds)) {
            $query = "SELECT DISTINCT host_name, host_id
                FROM centreon_acl
                WHERE group_id IN (".implode(',', $groupIds).")
                GROUP BY host_name, host_id
                ORDER BY host_name ASC";
            $DBRES = $pearDBndo->query($query);
            while ($row = $DBRES->fetchRow()) {
                if ($i) {
                    $string .= ", ";
                }
                switch ($flag) {
                    case "ID" :
                        $string .= "'".$row['host_id']."'";
                        break;
                    case "NAME" :
                        if ($escape === true) {
                            $string .= "'".CentreonDB::escape($row['host_name'])."'";
                        } else {
                            $string .= "'".$row['host_name']."'";
                        }
                        break;
                    default : $string .= "'".$row['host_id']."'"; break;
                }
                $i++;
            }
        }
        if (!$i) {
            $string = "''";
        }
        return $string;
    }

    /*
     *  Services string Getter
     *  Possible flags :
     *  - ID => will return the id's of the element
     *  - NAME => will return the names of the element
     */
    public function getServicesString($flag = null, $pearDBndo, $escape = true)
    {
        $this->checkUpdateACL();

        if (!isset($flag)) {
            $flag = "ID";
        }
        $flag = strtoupper($flag);
        $string = "";
        $i = 0;
        $groupIds = array_keys($this->accessGroups);
        if (count($groupIds)) {
            $query = "SELECT DISTINCT service_id, service_description
                FROM centreon_acl
                WHERE group_id IN (".implode(',', $groupIds).")";
            $DBRES = $pearDBndo->query($query);
            while ($row = $DBRES->fetchRow()) {
                if ($i) {
                    $string .= ", ";
                }
                switch ($flag) {
                    case "ID" :
                        $string .= "'".$row['service_id']."'";
                        break;
                    case "NAME" :
                        if ($escape === true) {
                            $string .= "'".CentreonDB::escape($row['service_description'])."'";
                        } else {
                            $string .= "'".$row['service_description']."'";
                        }
                        break;
                    default : $string .= "'".$row['service_id']."'"; break;
                }
                $i++;
            }
        }
        if (!$i) {
            $string = "''";
        }
        return $string;
    }

    /**
     * Get authorized host service ids
     *
     * @param $db CentreonDB
     * @return string | return id combinations like '14_26' (hostId_serviceId)
     */
    public function getHostServiceIds($db) {
        $this->checkUpdateACL();
        $groupIds = array_keys($this->accessGroups);
        $string = "";
        if (count($groupIds)) {
            $query = "SELECT DISTINCT host_id, service_id
                FROM centreon_acl
                WHERE group_id IN (".implode(',', $groupIds).")";
            $res = $db->query($query);
            while ($row = $res->fetchRow()) {
                if ($string != "") {
                    $string .= ", ";
                }
                $string .= "'".$row['host_id']."_".$row['service_id']."'";
            }
        }
        if ($string == "") {
            $string = "''";
        }
        return $string;
    }

    /*
     *  Actions Getter
     */
    public function getActions()
    {
        $this->checkUpdateACL();
        return $this->actions;
    }


    public function getTopology()
    {
        $this->checkUpdateACL();
        return $this->topology;
    }

    /**
     *
     * Update topologystr value
     */
    public function updateTopologyStr() {
        $this->setTopology();
        $this->topologyStr = $this->getTopologyString();
    }


    public function getTopologyString()
    {
        $this->checkUpdateACL();
        $string = "";
        $i = 0;

        foreach ($this->topology as $key => $value) {
            if ($i) {
                $string .= ", ";
            }
            $string .= "'".$key."'";
            $i++;
        }

        if (!$i) {
            $string = "''";
        }
        return $string;
    }

    /*
     *  This functions returns a string that forms a condition of a query
     *  i.e : " WHERE host_id IN ('1', '2', '3') "
     *  or : " AND host_id IN ('1', '2', '3') "
     */
    public function queryBuilder($condition, $field, $stringlist)
    {
        $str = "";
        if ($this->admin) {
            return $str;
        }
        $str .= " " . $condition . " " . $field . " IN (".$stringlist.") ";
        return $str;
    }


    /**
     * Function that returns
     *
     * @param string $p
     * @param bool $checkAction
     * @return int | 1 : if user is allowed to access the page
     *               0 : if user is NOT allowed to access the page
     */
    public function page($p, $checkAction = false)
    {
        $this->checkUpdateACL();
        if ($this->admin) {
            return 1;
        } elseif (isset($this->topology[$p])) {
            if ($checkAction && $this->topology[$p] == 2 &&
                isset($_REQUEST['o']) && $_REQUEST['o'] == 'a') {
                return 0;
            }
            return $this->topology[$p];
        }
        return 0;
    }

    /*
     *  Function that checks if the user can execute the action
     *  1 : user can execute it
     *  0 : user CANNOT execute it
     */
    public function checkAction($action)
    {
        $this->checkUpdateACL();
        if ($this->admin || isset($this->actions[$action])) {
            return 1;
        }
        return 0;
    }

    /*
     *  Function that returns the pair host/service by ID if $host_id is NULL
     *  Otherwise, it returns all the services of a specific host
     *
     */
    public function getHostServices($DB, $host_id = null, $get_service_description = null)
    {
        global $pearDB;

        $tab = array();
        if (!isset($host_id)) {
            if ($this->admin) {
                $req = (!is_null($get_service_description)) ? ", s.service_description " : "";
                $query = "SELECT s.service_id, h.host_id $req FROM host_service_relation hsr, host h, service s " .
                    "WHERE hsr.service_service_id = s.service_id " .
                    "AND s.service_activate = '1' " .
                    "AND hsr.host_host_id = h.host_id " .
                    "AND h.host_activate = '1'";
                $DBRESULT = $pearDB->query($query);
                while ($row = $DBRESULT->fetchRow()) {
                    if (!is_null($get_service_description)) {
                        $tab[$row['host_id']][$row['service_id']] = $row['service_description'];
                    } else {
                        $tab[$row['host_id']][$row['service_id']] = 1;
                    }
                }
                $DBRESULT->free();

                // Used By EventLogs page Only
                if (!is_null($get_service_description)) {
                    /*
                    * Get Services attached to hostgroups
                    */
                    $DBRESULT = $pearDB->query("SELECT hgr.host_host_id, s.service_id, s.service_description
                                      FROM hostgroup_relation hgr, service s, host_service_relation hsr" .
                                    " WHERE hsr.hostgroup_hg_id = hgr.hostgroup_hg_id" .
                                    " AND s.service_id = hsr.service_service_id");
                    while ($elem = $DBRESULT->fetchRow()){
                        $tab[$elem['host_host_id']][$elem["service_id"]] = $elem["service_description"];
                    }
                    $DBRESULT->free();
                }
            } else {
                $req = (!is_null($get_service_description)) ? ", service_description " : "";
                $query = "SELECT host_id, service_id $req FROM centreon_acl WHERE group_id IN (".$this->getAccessGroupsString().")";
                $DBRESULT = $DB->query($query);
                while ($row = $DBRESULT->fetchRow()) {
                    if (!is_null($get_service_description)) {
                        $tab[$row['host_id']][$row['service_id']] = $row['service_description'];
                    } else {
                        $tab[$row['host_id']][$row['service_id']] = 1;
                    }
                }
                $DBRESULT->free();
            }
        } else {
            if ($this->admin) {
                $query = "SELECT s.service_id, s.service_description, h.host_id FROM host_service_relation hsr, host h, service s " .
                    "WHERE hsr.service_service_id = s.service_id " .
                    "AND s.service_activate = '1' " .
                    "AND hsr.host_host_id = h.host_id " .
                    "AND h.host_activate = '1' " .
                    "AND h.host_id = '".$host_id."'";
                $DBRESULT = $pearDB->query($query);
                while ($row = $DBRESULT->fetchRow()) {
                    $tab[$row['service_id']] = $row['service_description'];
                }
                $DBRESULT->free();

                /*
                 * Get Services attached to hostgroups
                 */
                $DBRESULT = $pearDB->query("SELECT service_id, service_description FROM hostgroup_relation hgr, service, host_service_relation hsr" .
                        " WHERE hgr.host_host_id = '".$host_id."' AND hsr.hostgroup_hg_id = hgr.hostgroup_hg_id" .
                        " AND service_id = hsr.service_service_id");
                while ($elem = $DBRESULT->fetchRow()){
                    $tab[$elem["service_id"]]	= html_entity_decode($elem["service_description"], ENT_QUOTES, "UTF-8");
                }
                $DBRESULT->free();

            } else {
                $query = "SELECT service_id, service_description FROM centreon_acl WHERE host_id = '".$host_id."' AND group_id IN (".$this->getAccessGroupsString().")";
                $DBRESULT = $DB->query($query);
                while ($row = $DBRESULT->fetchRow()) {
                    $tab[$row['service_id']] = $row['service_description'];
                }
                $DBRESULT->free();
            }
        }
        return $tab;
    }

    /*
     *  Function that returns the pair host/service by NAME if $host_name is NULL
     *  Otherwise, it returns all the services of a specific host
     *
     */
    public function getHostServicesName($pearDBndo, $host_name = null)
    {
        $tab = array();
        if (!isset($host_name)) {
            if ($this->admin) {
                $query = "SELECT DISTINCT host_name, service_description FROM centreon_acl ORDER BY host_name";
            } else {
                $query = "SELECT host_name, service_description FROM centreon_acl WHERE group_id IN (".$this->getAccessGroupsString().") ORDER BY host_name";
            }
            $DBRESULT = $pearDBndo->query($query);
            while ($row = $DBRESULT->fetchRow()) {
                $tab[$row['host_name']][$row['service_description']] = 1;
            }
            $DBRESULT->free();
        } else {
            if ($this->admin) {
                $query = "SELECT service_id, service_description FROM centreon_acl WHERE host_name = '".$host_name."'";
            } else {
                $query = "SELECT service_id, service_description FROM centreon_acl WHERE host_name = '".$host_name."' AND group_id IN (".$this->getAccessGroupsString().")";
            }
            $DBRESULT = $pearDBndo->query($query);
            while ($row = $DBRESULT->fetchRow()) {
                $tab[$row['service_id']] = $row['service_description'];
            }
            $DBRESULT->free();
        }

        return $tab;
    }


    /*
     *  Function  that returns the hosts of a specific hostgroup
     */
    public function getHostgroupHosts($hg_id, $pearDBndo)
    {
        global $pearDB;

        $tab = array();
        $query = "SELECT h.host_id, h.host_name " .
            "FROM hostgroup_relation hgr, host h " .
            "WHERE hgr.hostgroup_hg_id = '".$hg_id."' " .
            "AND hgr.host_host_id = h.host_id " .
            $this->queryBuilder("AND", "h.host_id",  $this->getHostsString("ID", $pearDBndo)).
            " ORDER BY h.host_name ";

        $DBRESULT = $pearDB->query($query);
        while ($row = $DBRESULT->fetchRow()) {
            $tab[$row['host_id']] = $row['host_name'];
        }
        return ($tab);
    }

    /*
     * Function that sets the changed flag to 1 for the cron centAcl.php
     */
    public function updateACL()
    {
        global $pearDB;

        $DBRESULT = $pearDB->query("UPDATE `acl_resources` SET `changed` = '1'");
    }

    /*
     * Funtion that return only metaservice table
     */
    public function getMetaServices()
    {
        return $this->metaServices;
    }

    /*
     * Function that return Metaservice list ('', '', '')
     */
    public function getMetaServiceString()
    {
        return $this->metaServiceStr;
    }

    /**
     * Load the list of parent template
     */
    private function loadParentTemplates()
    {
        global $pearDB;

        /* Get parents template */
        $this->parentTemplates = array();
        $currentContact = $this->userID;
        while ($currentContact != 0) {
            $this->parentTemplates[] = $currentContact;
            $query = 'SELECT contact_template_id
                FROM contact
                WHERE contact_id = ' . $currentContact;
            $res = $pearDB->query($query);
            if (PEAR::isError($res)) {
                $currentContact = 0;
            } else {
                if ($row = $res->fetchRow()) {
                    $currentContact = $row['contact_template_id'];
                } else {
                    $currentContact = 0;
                }
            }
        }
    }

    /**
     * Get DB Name
     *
     * @param string $broker
     * @return string
     */
    public function getNameDBAcl($broker = null)
    {
        global $pearDB, $conf_centreon;
        static $dbname = "";

        if ($dbname != "") {
            return $dbname;
        }

        if (strtolower($broker) == 'broker') {
            $dbname = $conf_centreon["dbcstg"];
        } else {
            $res = $pearDB->query("SELECT db_name FROM cfg_ndo2db WHERE activate = '1' LIMIT 1");
            if (PEAR::isError($res) || !$res->numRows()) { // no broker dbname
                $dbname = null;
            } else {
                $row = $res->fetchRow();
                $dbname = $row['db_name'];
            }
        }
        return $dbname;
    }

    /**
     * build request
     *
     * @param array $options
     * @param bool $hasWhereClause | whether the request already has a where clause
     * @return array
     */
    private function constructRequest($options, $hasWhereClause = false)
    {
        global $pearDB;

        $requests = array();
        $requests['order'] = implode(', ', isset($options['order']) ? $options['order'] : array());
        $requests['fields'] = implode(', ', isset($options['fields']) ? $options['fields'] : array('*'));
        if ($requests['order'] != '') {
            $requests['order'] = " ORDER BY " . $requests['order'];
        }
        $requests['conditions'] = '';
        if (isset($options['conditions']) && is_array($options['conditions'])) {
            $first = true;
            foreach($options['conditions'] as $key => $opvalue)  {
                if ($first) {
                    if ($hasWhereClause) {
                        $clause = ' AND (';
                    } else {
                        $clause = ' WHERE (';
                    }
                    if (is_array($opvalue) && count($opvalue) == 2) {
                        list($op, $value) = $opvalue;
                    } else {
                        $op = " = ";
                        $value = $opvalue;
                    }
                    $first = false;
                } else {
                    if (is_array($opvalue) && count($opvalue) == 3) {
                        list($clause, $op, $value) = $opvalue;
                    } else {
                        $clause = ' AND ';
                        $op = " = ";
                        $value = $opvalue;
                    }
                }

                $requests['conditions'] .= $clause . $key . $op . " '" . $pearDB->escape($value)."' ";
            }
            if (!$first) {
                $requests['conditions'] .= ') ';
            }
        }
        return $requests;
    }

    private function constructKey($res, $options)
    {
        $key = '';
        $separator = '';
        foreach ($options['keys'] as $value) {
            if ($res[$value] == '') {
                return '';
            }
            $key .= $separator . $res[$value];
            $separator = isset($options['keys_separator']) ? $options['keys_separator'] : '_';
        }
        return $key;
    }

    /**
     * constructResult
     *
     * @param mixed $res
     * @param mixed $options
     * @access private
     * @return void
     */
    private function constructResult($res, $options) {
        $result = array();
        if (isset($options['pages'])) {
            list($offset, $limit) = array_map('trim', explode(",", $options['pages']));
        }
        $i = 0;
        while ($elem = $res->fetchRow()) {
            $key = $this->constructKey($elem, $options);
            // no duplicate keys with this
            if ($key != '' && !isset($result[$key])) {
                if (isset($offset) && $i < $offset) {
                    continue;
                }
                if (isset($limit) && isset($offset)
                        && $i > ($offset+$limit)) {
                    break;
                }
                $i++;
                if (isset($options['get_row'])) {
                    $result[$key] = $elem[$options['get_row']];
                } else {
                    $result[$key] = $elem;
                }
            }
        }
        return $result;
    }

    /**
     * Get ServiceGroup from ACL and configuration DB
     */
    public function getServiceGroupAclConf($search = null, $broker=null, $options=null, $sg_empty=null)
    {
        global $pearDB;

        $sg = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "") {
            return $sg;
        }
        if (is_null($options)) {
            $options = array('order' => array('LOWER(sg_name)'),
                    'fields' => array('servicegroup.sg_id', 'servicegroup.sg_name'),
                    'keys' => array('sg_id'),
                    'keys_separator' => '',
                    'get_row' => 'sg_name');
        }
        $request = $this->constructRequest($options);
        $searchSTR = "";
        if ($this->admin) {
            if ($search != "") {
                $searchSTR = " sg_name LIKE '%$search%' AND ";
            }
            $empty_exists = "";
            if (!is_null($sg_empty)) {
                $empty_exists = 'AND EXISTS (SELECT * FROM servicegroup_relation WHERE (servicegroup_relation.servicegroup_sg_id = servicegroup.sg_id AND servicegroup_relation.service_service_id IS NOT NULL))';
            }
            $query = "SELECT " . $request['fields'] . " FROM servicegroup ".
                " WHERE $searchSTR sg_activate = '1' $empty_exists".
                $request['order'];
        } else {
            if ($search != "") {
                $searchSTR = " AND sg_name LIKE '%$search%' ";
            }
# Cant manage empty servicegroup with ACLs. We'll have a problem with acl for conf...
            $groupIds = array_keys($this->accessGroups);
            $query = "(SELECT " . $request['fields'] . " FROM $db_name_acl.centreon_acl, hostgroup_relation, servicegroup_relation, servicegroup " .
                " WHERE $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") " .
                " AND $db_name_acl.centreon_acl.host_id = hostgroup_relation.host_host_id " .
                " AND hostgroup_relation.hostgroup_hg_id = servicegroup_relation.hostgroup_hg_id " .
                " AND servicegroup_relation.service_service_id = $db_name_acl.centreon_acl.service_id " .
                " AND servicegroup_relation.servicegroup_sg_id = servicegroup.sg_id $searchSTR" .
                ") UNION ALL (" .
                " SELECT " . $request['fields'] . " FROM $db_name_acl.centreon_acl, servicegroup_relation, servicegroup " .
                " WHERE $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") " .
                " AND $db_name_acl.centreon_acl.host_id = servicegroup_relation.host_host_id AND $db_name_acl.centreon_acl.service_id = servicegroup_relation.service_service_id " .
                " AND servicegroup_relation.servicegroup_sg_id = servicegroup.sg_id $searchSTR)" .
                $request['order'];
        }
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $sg;
        }
        $sg = $this->constructResult($res, $options);
        return $sg;
    }

    /**
     * Get Services in servicesgroups from ACL and configuration DB
     */
    public function getServiceServiceGroupAclConf($sg_id, $broker=null, $options=null)
    {
        global $pearDB;

        $services = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "") {
            return $services;
        }
        if (is_null($options)) {
            $options = array('order' => array('LOWER(host_name)', 'LOWER(service_description)'),
                    'fields' => array('service.service_description', 'service.service_id', 'host.host_id', 'host.host_name'),
                    'keys' => array('host_id', 'service_id'),
                    'keys_separator' => '_');
        }
        $request = $this->constructRequest($options);
        $from_acl = "";
        $where_acl = "";
        if (!$this->admin) {
            $groupIds = array_keys($this->accessGroups);
            $from_acl = ", $db_name_acl.centreon_acl";
            $where_acl = " AND $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") AND $db_name_acl.centreon_acl.host_id = host.host_id AND $db_name_acl.centreon_acl.service_id = service.service_id";
        }
        $query = "(SELECT " . $request['fields'] . " FROM servicegroup, servicegroup_relation, service, host $from_acl" .
            " WHERE servicegroup.sg_id = '$sg_id'" .
            " AND service.service_activate='1' AND host.host_activate='1' AND servicegroup.sg_id = servicegroup_relation.servicegroup_sg_id" .
            " AND servicegroup_relation.service_service_id = service.service_id" .
            " AND servicegroup_relation.host_host_id = host.host_id" .
            " $where_acl" .
            ") UNION ALL (" .
            " SELECT " . $request['fields'] . " FROM servicegroup, servicegroup_relation, hostgroup_relation, service, host $from_acl" .
            " WHERE servicegroup.sg_id = '$sg_id'" .
            " AND servicegroup.sg_id = servicegroup_relation.servicegroup_sg_id" .
            " AND servicegroup_relation.hostgroup_hg_id = hostgroup_relation.hostgroup_hg_id" .
            " AND hostgroup_relation.host_host_id = host.host_id" .
            " AND servicegroup_relation.service_service_id = service.service_id" .
            " $where_acl)" .
            $request['order'];
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $services;
        }
        $services = $this->constructResult($res, $options);
        return $services;
    }

    /**
     * getHostAclConf
     *
     * @param mixed $search
     * @param mixed $broker
     * @param mixed $options
     * @param bool $host_empty | if host_empty is true,
     *                           hosts with no authorized
     *                           services will be returned
     * @access public
     * @return void
     */
    public function getHostAclConf($search = null, $broker=null, $options=null, $host_empty = false)
    {
        global $pearDB;

        $hosts = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "") {
            return $hosts;
        }

        if (is_null($options)) {
            $options = array('order' => array('LOWER(host.host_name)'),
                    'fields' => array('host.host_id', 'host.host_name'),
                    'keys' => array('host_id'),
                    'keys_separator' => '',
                    'get_row' => 'host_name');
        }
        $request = $this->constructRequest($options);
        $searchSTR = "";
        $empty_exists = "";
        if ($this->admin) {
            if ($search != "") {
                $searchSTR = "(host.host_name LIKE '%$search%' OR host.host_alias LIKE '%$search%') AND";
            }
            if ($host_empty) {
                $empty_exists = 'AND EXISTS (SELECT * FROM host_service_relation, hostgroup_relation WHERE (host_service_relation.host_host_id = host.host_id AND host_service_relation.service_service_id IS NOT NULL) OR (host.host_id = hostgroup_relation.host_host_id AND hostgroup_relation.hostgroup_hg_id = host_service_relation.hostgroup_hg_id AND host_service_relation.service_service_id IS NOT NULL))';
            }
            $query = "SELECT " . $request['fields'] . " FROM host ".
                " WHERE $searchSTR host_activate = '1' AND host_register = '1' $empty_exists".
                $request['order'];
        } else {
            if ($search != "") {
                $searchSTR = " AND (host.host_name LIKE '%$search%' OR host.host_alias LIKE '%$search%')";
            }
            $groupIds = array_keys($this->accessGroups);
            if ($host_empty) {
                $empty_exists = " AND $db_name_acl.centreon_acl.service_id IS NOT NULL";
            }
            $query = "SELECT " . $request['fields'] . " FROM host, $db_name_acl.centreon_acl ".
                " WHERE $searchSTR host.host_activate = '1' AND host.host_register = '1' ".
                " AND $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") " .
                " AND $db_name_acl.centreon_acl.host_id = host.host_id $empty_exists ".
                $request['order'];
        }
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $hosts;
        }
        $hosts = $this->constructResult($res, $options);
        return $hosts;
    }

    public function getHostServiceAclConf($host_id, $broker=null, $options=null) {
        global $pearDB;

        $services = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "")
            return $services;

        if (is_null($options)) {
            $options = array('order' => array('LOWER(service_description)'),
                    'fields' => array('service_id', 'service_description'),
                    'keys' => array('service_id'),
                    'keys_separator' => '',
                    'get_row' => 'service_description');
        }
        $request = $this->constructRequest($options);
        if ($this->admin) {
            $query = "(SELECT " . $request['fields'] . " FROM host_service_relation hsr, host h, service s " .
                " WHERE h.host_id = '" . $host_id . "' AND h.host_activate = '1' AND h.host_register = '1' " .
                " AND h.host_id = hsr.host_host_id " .
                " AND hsr.service_service_id = s.service_id " .
                " AND s.service_activate = '1' " .
                ") UNION ALL (" .
                "SELECT " . $request['fields'] . " FROM host h, hostgroup_relation hgr, service, host_service_relation hsr" .
                " WHERE h.host_id = '" . $host_id . "' AND h.host_activate = '1' AND h.host_register = '1' " .
                " AND h.host_id = hgr.host_host_id " .
                " AND hgr.hostgroup_hg_id = hsr.hostgroup_hg_id" .
                " AND hsr.service_service_id = service.service_id" .
                ") " . $request['order'];
        } else {
            $query = "SELECT " . $request['fields'] . " FROM $db_name_acl.centreon_acl WHERE $db_name_acl.centreon_acl.host_id = '" . $host_id . "' AND $db_name_acl.centreon_acl.group_id IN (" . $this->getAccessGroupsString() . ") " . $request['order'];
        }
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $services;
        }
        $services = $this->constructResult($res, $options);
        return $services;
    }

    /**
     * Get HostGroup from ACL and configuration DB
     */
    public function getHostGroupAclConf($search = null, $broker=null, $options=null, $hg_empty=null)
    {
        global $pearDB;

        $hg = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "") {
            return $hg;
        }
        if (is_null($options)) {
            $options = array('order' => array('LOWER(hg_name)'),
                    'fields' => array('hg_id', 'hg_name'),
                    'keys' => array('hg_id'),
                    'keys_separator' => '',
                    'get_row' => 'hg_name');
        }
        $request = $this->constructRequest($options);
        $searchSTR = "";
        if ($this->admin) {
            if ($search != "") {
                $searchSTR = " hg_name LIKE '%$search%' AND ";
            }
            $empty_exists = "";
            if (!is_null($hg_empty)) {
                $empty_exists = 'AND EXISTS (SELECT * FROM hostgroup_relation WHERE (hostgroup_relation.hostgroup_hg_id = hostgroup.hg_id AND hostgroup_relation.host_host_id IS NOT NULL))';
            }
# We should check if host is activate (maybe)
            $query = "SELECT " . $request['fields'] . " FROM hostgroup ".
                " WHERE $searchSTR hg_activate = '1' $empty_exists".
                $request['order'];
        } else {
            if ($search != "") {
                $searchSTR = " hg_name LIKE '%$search%' AND ";
            }
# Cant manage empty hostgroup with ACLs. We'll have a problem with acl for conf...
            $groupIds = array_keys($this->accessGroups);
            $query = " SELECT " . $request['fields'] . " FROM hostgroup, hostgroup_relation, $db_name_acl.centreon_acl" .
                " WHERE $searchSTR hostgroup_relation.hostgroup_hg_id = hostgroup.hg_id " .
                " AND $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") " .
                " AND $db_name_acl.centreon_acl.host_id = hostgroup_relation.host_host_id " .
                $request['order'];
        }
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $hg;
        }
        $hg = $this->constructResult($res, $options);
        return $hg;
    }

    public function getHostHostGroupAclConf($hg_id, $broker=null, $options=null)
    {
        global $pearDB;

        $hg = array();
        $db_name_acl = $this->getNameDBAcl($broker);
        if (is_null($db_name_acl) || $db_name_acl == "") {
            return $hg;
        }
        if (is_null($options)) {
            $options = array('order' => array('LOWER(host_name)'),
                    'fields' => array('host_id', 'host_name'),
                    'keys' => array('host_id'),
                    'keys_separator' => '',
                    'get_row' => 'host_name');
        }
        $request = $this->constructRequest($options);
        $searchSTR = "";
        if ($this->admin) {
            $searchSTR = " hg_id = '$hg_id' AND ";
# We should check if host is activate (maybe)
            $query = "SELECT " . $request['fields'] . " FROM hostgroup, hostgroup_relation, host ".
                " WHERE $searchSTR hg_activate = '1' ".
                " AND host_activate='1' AND hostgroup_relation.hostgroup_hg_id = hostgroup.hg_id ".
                " AND hostgroup_relation.host_host_id = host.host_id ".
                $request['order'];
        } else {
            $searchSTR = " hg_id = '$hg_id' AND ";
# Cant manage empty hostgroup with ACLs. We'll have a problem with acl for conf...
            $groupIds = array_keys($this->accessGroups);
            $query = " SELECT " . $request['fields'] . " FROM hostgroup, hostgroup_relation, $db_name_acl.centreon_acl" .
                " WHERE $searchSTR hostgroup_relation.hostgroup_hg_id = hostgroup.hg_id " .
                " AND $db_name_acl.centreon_acl.group_id IN (" . implode(',', $groupIds) . ") " .
                " AND $db_name_acl.centreon_acl.host_id = hostgroup_relation.host_host_id " .
                $request['order'];
        }
        $res = $pearDB->query($query);
        if (PEAR::isError($res)) {
            return $hg;
        }
        $hg = $this->constructResult($res, $options);
        return $hg;
    }

    /**
     * getPollerAclConf
     *
     * @access public
     * @param array $options
     * @return void
     */
    public function getPollerAclConf($options = array()) {
        global $pearDB;

        if (!count($options)) {
            $options = array('fields'  => array('id', 'name'),
                             'order'   => array('name'),
                             'keys'    => array('id'));
        }
        $request = $this->constructRequest($options);
        $pollerstring = $this->getPollerString();
        $pollerfilter = "";
        if (!$this->admin && $pollerstring != "''") {
            $pollerfilter = $this->queryBuilder($request['conditions'] ? 'AND' : 'WHERE',
                                                'id',
                                                $pollerstring);
        }

        $sql = "SELECT ".$request['fields'] .
               " FROM nagios_server " .
               $request['conditions'] .
               $pollerfilter .
               $request['order'];
        $res = $pearDB->query($sql);
        $result = $this->constructResult($res, $options);
        return $result;
    }

    /**
     * getContactAclConf
     *
     * @param array $options
     * @access public
     * @return void
     */
    public function getContactAclConf($options = array()) {
        global $pearDB;

        if ($this->admin) {
            $request = $this->constructRequest($options);
            $sql = "SELECT ".$request['fields'].
                   " FROM contact ".
                   $request['conditions'].
                   $request['order'];
        } else {
            $request = $this->constructRequest($options, true);
            $sql = "SELECT ".$request['fields']."
                    FROM acl_group_contacts_relations agcr, contact c
                    WHERE c.contact_id = agcr.contact_contact_id
                    AND agcr.acl_group_id IN (".$this->getAccessGroupsString().") ".
                    $request['conditions'].
                   " UNION ".
                   "SELECT ".$request['fields']."
                    FROM acl_group_contactgroups_relations agccgr, contactgroup_contact_relation ccr, contact c
                    WHERE c.contact_id = ccr.contact_contact_id
                    AND ccr.contactgroup_cg_id = agccgr.cg_cg_id
                    AND agccgr.acl_group_id IN (".$this->getAccessGroupsString().") ".
                    $request['conditions'].
                    $request['order'];
        }
        $res = $pearDB->query($sql);
        $result = $this->constructResult($res, $options);
        return $result;
    }

    /**
     * getContactGroupAclConf
     *
     * @access public
     * @param array $options
     * @return void
     */
    public function getContactGroupAclConf($options = array()) {
        global $pearDB;

        if ($this->admin) {
            $request = $this->constructRequest($options);
            $sql = "SELECT ".$request['fields'].
                   " FROM contactgroup ".
                   $request['conditions'].
                   $request['order'];
        } else {
            $request = $this->constructRequest($options, true);
            $sql = "SELECT ".$request['fields']."
                    FROM acl_group_contactgroups_relations agccgr, contactgroup cg
                    WHERE cg.cg_id = agccgr.cg_cg_id
                    AND cg.cg_type = 'local'
                    AND agccgr.acl_group_id IN (".$this->getAccessGroupsString().") ".
                    $request['conditions'].
                    $request['order'];
        }
        $res = $pearDB->query($sql);
        $result = $this->constructResult($res, $options);
        return $result;
    }
}
?>
