<?php
/*
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
 * SVN : $URL: http://svn.centreon.com/tags/centreon-2.4.0/www/include/eventLogs/GetXmlLog.php $
 * SVN : $Id: GetXmlLog.php 13739 2012-11-12 09:27:33Z avilau $
 *
 */

 	ini_set("display_errors", "Off");

	/*
	 * XML tag
	 */
	stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ? header("Content-type: application/xhtml+xml") : header("Content-type: text/xml");
    header('Content-Disposition: attachment; filename="eventLogs-' . time() . '.xml"');
	/** ****************************
	 * Include configurations files
	 */
	include_once "@CENTREON_ETC@/centreon.conf.php";

	/*
	 * Require Classes
	 */
	require_once $centreon_path . "www/include/eventLogs/common-Func.php";
	require_once $centreon_path . "www/class/centreonDB.class.php";
	require_once $centreon_path . "www/class/centreonSession.class.php";
	require_once $centreon_path . "www/class/centreon.class.php";

	centreonSession::start();
	$oreon = $_SESSION["centreon"];

	/**
	 * Language informations init
	 */
	$locale = $oreon->user->get_lang();
	putenv("LANG=$locale");
	setlocale(LC_ALL, $locale);
	bindtextdomain("messages", $centreon_path . "/www/locale/");
	bind_textdomain_codeset("messages", "UTF-8");
	textdomain("messages");

	/**
	 * Connect to DB
	 */
	$pearDB 	= new CentreonDB();
	$pearDBO 	= new CentreonDB("centstorage");
	if ($oreon->broker->getBroker() == "ndo") {
		$pearDBndo 	= new CentreonDB("ndo");
		define("STATUS_OK", "OK");
	    define("STATUS_WARNING", "WARNING");
	    define("STATUS_CRITICAL", "CRITICAL");
	    define("STATUS_UNKNOWN", "UNKNOWN");
	    define("STATUS_PENDING", "PENDING");
	    define("STATUS_UP", "UP");
	    define("STATUS_DOWN", "DOWN");
	    define("STATUS_UNREACHABLE", "UNREACHABLE");
	    define("TYPE_SOFT", "SOFT");
	    define("TYPE_HARD", "HARD");
	} elseif ($oreon->broker->getBroker() == "broker") {
	    define("STATUS_OK", 0);
	    define("STATUS_WARNING", 1);
	    define("STATUS_CRITICAL", 2);
	    define("STATUS_UNKNOWN", 3);
	    define("STATUS_PENDING", 4);
	    define("STATUS_UP", 0);
	    define("STATUS_DOWN", 1);
	    define("STATUS_UNREACHABLE", 2);
	    define("TYPE_SOFT", 0);
	    define("TYPE_HARD", 1);
	}

	/**
	 * Include Access Class
	 */
	include_once $centreon_path . "www/class/centreonACL.class.php";
	include_once $centreon_path . "www/class/centreonXML.class.php";
	include_once $centreon_path . "www/class/centreonGMT.class.php";

	include_once $centreon_path . "www/include/common/common-Func.php";

	/*
	 * Start XML document root
	 */
	$buffer = new CentreonXML();
	$buffer->startElement("root");

 	/*
	 * Security check
	 */
	(isset($_GET["lang"])) ? $lang_ = htmlentities($_GET["lang"], ENT_QUOTES, "UTF-8") : $lang_ = "-1";
	(isset($_GET["id"])) ? $openid = htmlentities($_GET["id"], ENT_QUOTES, "UTF-8") : $openid = "-1";
	(isset($_GET["sid"])) ? $sid = htmlentities($_GET["sid"], ENT_QUOTES, "UTF-8") : $sid = "-1";

 	/*
	 * Init GMT class
	 */
	$centreonGMT = new CentreonGMT($pearDB);
	$centreonGMT->getMyGMTFromSession($sid, $pearDB);

	/*
	 * Check Session
	 */
	$contact_id = check_session($sid, $pearDB);

	$is_admin = isUserAdmin($sid);
	if (isset($sid) && $sid){
		$access = new CentreonAcl($contact_id, $is_admin);
		$lca = array("LcaHost" => $access->getHostServices(($oreon->broker->getBroker() == "ndo" ? $pearDBndo : $pearDBO), null, 1), "LcaHostGroup" => $access->getHostGroups(), "LcaSG" => $access->getServiceGroups());
	}

	(isset($_GET["num"])) ? $num = htmlentities($_GET["num"]) : $num = "0";
	(isset($_GET["limit"])) ? $limit = htmlentities($_GET["limit"]) : $limit = "30";
	(isset($_GET["StartDate"])) ? $StartDate = htmlentities($_GET["StartDate"]) : $StartDate = "";
	(isset($_GET["EndDate"])) ? $EndDate = htmlentities($_GET["EndDate"]) : $EndDate = "";
	(isset($_GET["StartTime"])) ? $StartTime = htmlentities($_GET["StartTime"]) : $StartTime = "";
	(isset($_GET["EndTime"])) ? $EndTime = htmlentities($_GET["EndTime"]) : $EndTime = "";
	(isset($_GET["period"])) ? $auto_period = htmlentities($_GET["period"]) : $auto_period = "-1";
	(isset($_GET["multi"])) ? $multi = htmlentities($_GET["multi"]) : $multi = "-1";

	(isset($_GET["up"])) ? set_user_param($contact_id, $pearDB, "log_filter_host_up", htmlentities($_GET["up"])) : $up = "true";
	(isset($_GET["down"])) ? set_user_param($contact_id, $pearDB, "log_filter_host_down", htmlentities($_GET["down"])) : $down = "true";
	(isset($_GET["unreachable"])) ? set_user_param($contact_id, $pearDB, "log_filter_host_unreachable", htmlentities($_GET["unreachable"])) : $unreachable = "true";
	(isset($_GET["ok"])) ? set_user_param($contact_id, $pearDB, "log_filter_svc_ok", htmlentities($_GET["ok"])) : $ok = "true";
	(isset($_GET["warning"])) ? set_user_param($contact_id, $pearDB, "log_filter_svc_warning", htmlentities($_GET["warning"])) : $warning = "true";
	(isset($_GET["critical"])) ? set_user_param($contact_id, $pearDB, "log_filter_svc_critical", htmlentities($_GET["critical"])) : $critical = "true";
	(isset($_GET["unknown"])) ? set_user_param($contact_id, $pearDB, "log_filter_svc_unknown", htmlentities($_GET["unknown"])) : $unknown = "true";

	(isset($_GET["notification"])) ? set_user_param($contact_id, $pearDB, "log_filter_notif", htmlentities($_GET["notification"])) : $notification = "false";
	(isset($_GET["alert"])) ? set_user_param($contact_id, $pearDB, "log_filter_alert", htmlentities($_GET["alert"])) : $alert = "true";
	(isset($_GET["error"])) ? set_user_param($contact_id, $pearDB, "log_filter_error", htmlentities($_GET["error"])) : $error = "false";
	(isset($_GET["oh"])) ? set_user_param($contact_id, $pearDB, "log_filter_oh", htmlentities($_GET["oh"])) : $oh = "false";

	(isset($_GET["search_H"])) ? set_user_param($contact_id, $pearDB, "search_H", htmlentities($_GET["search_H"])) : $search_H = "VIDE";
	(isset($_GET["search_S"])) ? set_user_param($contact_id, $pearDB, "search_S", htmlentities($_GET["search_S"])) : $search_S = "VIDE";
	(isset($_GET["search_service"])) ? $search_service = htmlentities($_GET["search_service"], ENT_QUOTES, "UTF-8") : $search_service = "";
	(isset($_GET["export"])) ? $export = htmlentities($_GET["export"], ENT_QUOTES, "UTF-8") : $export = 0;

	$start = 0;
    $end = 0;
	if ($contact_id){
		$user_params = get_user_param($contact_id, $pearDB);

		if (!isset($user_params["log_filter_host"]))
			$user_params["log_filter_host"] = 1;
		if (!isset($user_params["log_filter_svc"]))
			$user_params["log_filter_svc"] = 1;
		if (!isset($user_params["log_filter_host_down"]))
			$user_params["log_filter_host_down"] = 1;
		if (!isset($user_params["log_filter_host_up"]))
			$user_params["log_filter_host_up"] = 1;
		if (!isset($user_params["log_filter_host_unreachable"]))
			$user_params["log_filter_host_unreachable"] = 1;
		if (!isset($user_params["log_filter_svc_ok"]))
			$user_params["log_filter_svc_ok"] = 1;
		if (!isset($user_params["log_filter_svc_warning"]))
			$user_params["log_filter_svc_warning"] = 1;
		if (!isset($user_params["log_filter_svc_critical"]))
			$user_params["log_filter_svc_critical"] = 1;
		if (!isset($user_params["log_filter_svc_unknown"]))
			$user_params["log_filter_svc_unknown"] = 1;
		if (!isset($user_params["log_filter_notif"]))
			$user_params["log_filter_notif"] = 1;
		if (!isset($user_params["log_filter_error"]))
			$user_params["log_filter_error"] = 1;
		if (!isset($user_params["log_filter_alert"]))
			$user_params["log_filter_alert"] = 1;

		if (!isset($user_params["search_H"]))
			$user_params["search_H"] = "";
		if (!isset($user_params["search_S"]))
			$user_params["search_S"] = "";

		$alert = $user_params["log_filter_alert"];
		$notification = $user_params["log_filter_notif"];
		$error = $user_params["log_filter_error"];
		$unknown = $user_params["log_filter_svc_unknown"];
		$unreachable = $user_params["log_filter_host_unreachable"];
		$up = $user_params["log_filter_host_up"];
		$ok = $user_params["log_filter_svc_ok"];
		$down = $user_params["log_filter_host_down"];
		$warning = $user_params["log_filter_svc_warning"];
		$critical = $user_params["log_filter_svc_critical"];
		$oh = $user_params["log_filter_oh"];

		$search_H = $user_params["search_H"];
		$search_S = $user_params["search_S"];
	}

	if ($StartDate != "" && $StartTime == "") {
		$StartTime = "00:00";
	}

	if ($EndDate != "" && $EndTime == "") {
		$EndTime = "00:00";
	}

	if ($StartDate != "") {
		preg_match("/^([0-9]*)\/([0-9]*)\/([0-9]*)/", $StartDate, $matchesD);
		preg_match("/^([0-9]*):([0-9]*)/", $StartTime, $matchesT);
		$start = mktime($matchesT[1], $matchesT[2], "0", $matchesD[1], $matchesD[2], $matchesD[3], -1) ;
	}
	if ($EndDate !=  "") {
		preg_match("/^([0-9]*)\/([0-9]*)\/([0-9]*)/", $EndDate, $matchesD);
		preg_match("/^([0-9]*):([0-9]*)/", $EndTime, $matchesT);
		$end = mktime($matchesT[1], $matchesT[2], "0", $matchesD[1], $matchesD[2], $matchesD[3], -1) ;
	}

	$period = 86400;
	if ($auto_period > 0) {
		$period = $auto_period;
		$start = time() - ($period);
		$end = time();
	}

	$general_opt = getStatusColor($pearDB);

	$tab_color_service 	= array(STATUS_OK => $general_opt["color_ok"], STATUS_WARNING => $general_opt["color_warning"], STATUS_CRITICAL => $general_opt["color_critical"], STATUS_UNKNOWN => $general_opt["color_unknown"], STATUS_PENDING => $general_opt["color_pending"]);
	$tab_color_host		= array(STATUS_UP => $general_opt["color_up"], STATUS_DOWN => $general_opt["color_down"], STATUS_UNREACHABLE => $general_opt["color_unreachable"]);

	$tab_type 			= array("1" => "HARD", "0" => "SOFT");
	$tab_class 			= array("0" => "list_one", "1" => "list_two");
	$tab_status_host 	= array("0" => "UP", "1" => "DOWN", "2" => "UNREACHABLE");
	$tab_status_service = array("0" => "OK", "1" => "WARNING", "2" => "CRITICAL", "3" => "UNKNOWN");

	/*
	 * Create IP Cache
	 */
	if ($export) {
		$HostCache = array();
		$DBRESULT = $pearDB->query("SELECT host_name, host_address FROM host WHERE host_register = '1'");
		while ($h = $DBRESULT->fetchRow()) {
			$HostCache[$h["host_name"]] = $h["host_address"];
		}
		$DBRESULT->free();
	}


	$logs = array();

	/*
	 * Print infos..
	 */
	$buffer->startElement("infos");
	$buffer->writeElement("multi", $multi);
	$buffer->writeElement("sid", $sid);
	$buffer->writeElement("opid", $openid);
	$buffer->writeElement("start", $start);
	$buffer->writeElement("end", $end);
	$buffer->writeElement("notification", $notification);
	$buffer->writeElement("alert", $alert);
	$buffer->writeElement("error", $error);
	$buffer->writeElement("up", $up);
	$buffer->writeElement("down", $down);
	$buffer->writeElement("unreachable", $unreachable);
	$buffer->writeElement("ok", $ok);
	$buffer->writeElement("warning", $warning);
	$buffer->writeElement("critical", $critical);
	$buffer->writeElement("unknown", $unknown);
	$buffer->writeElement("oh", $oh);
	$buffer->writeElement("search_H", $search_H);
	$buffer->writeElement("search_S", $search_S);
	$buffer->endElement();

	$msg_type_set = array ();
	if ($alert == 'true')
		array_push ($msg_type_set, "'0'");
	if ($alert == 'true')
		array_push ($msg_type_set, "'1'");
	if ($notification == 'true')
		array_push ($msg_type_set, "'2'");
	if ($notification== 'true')
		array_push ($msg_type_set, "'3'");
	if ($error == 'true')
		array_push ($msg_type_set, "'4'");

	$msg_req = '';
	$suffix_order = " ORDER BY ctime DESC, host_name ASC, log_id DESC, service_description ASC ";

	$host_msg_status_set = array();
	if ($up == 'true')
		array_push($host_msg_status_set, "'".STATUS_UP."'");
	if ($down == 'true' )
		array_push($host_msg_status_set, "'".STATUS_DOWN."'");
	if ($unreachable == 'true' )
		array_push($host_msg_status_set, "'".STATUS_UNREACHABLE."'");

    $svc_msg_status_set = array();
	if ($ok == 'true')
		array_push($svc_msg_status_set, "'".STATUS_OK."'");
	if ($warning == 'true')
		array_push($svc_msg_status_set, "'".STATUS_WARNING."'");
	if ($critical == 'true')
		array_push($svc_msg_status_set, "'".STATUS_CRITICAL."'");
	if ($unknown == 'true')
		array_push($svc_msg_status_set, "'".STATUS_UNKNOWN."'");

	$flag_begin = 0;
	if ($notification == 'true') {
	    if (count($host_msg_status_set)) {
		    $msg_req .= "(";
		    $flag_begin = 1;
		    $msg_req .= " (`msg_type` = '3' ";
			$msg_req .= " AND `status` IN (" . implode(',', $host_msg_status_set)."))";
			$msg_req .= ") ";
		}
		if (count($svc_msg_status_set)) {
            if ($flag_begin == 0) {
                $msg_req .= "(";
            } else {
                $msg_req .= " OR ";
            }
            $msg_req .= " (`msg_type` = '2' ";
			$msg_req .= " AND `status` IN (" . implode(',', $svc_msg_status_set)."))";
			if ($flag_begin == 0) {
			    $msg_req .= ") ";
			}
			$flag_begin = 1;
		}
	}
	if ($alert == 'true') {
	    if (count($host_msg_status_set)) {
	        if ($flag_begin) {
                $msg_req .= " OR ";
            }
	        if ($oh == true) {
                $msg_req .= " ( ";
                $flag_oh = true;
            }
		    $flag_begin = 1;
		    $msg_req .= " ((`msg_type` IN ('1', '10', '11') ";
			$msg_req .= " AND `status` IN (" . implode(',', $host_msg_status_set).")) ";
			$msg_req .= ") ";
		}
		if (count($svc_msg_status_set)) {
            if ($flag_begin) {
                $msg_req .= " OR ";
            }
            if ($oh == true && !isset($flag_oh)) {
                $msg_req .= " ( ";
            }
            $flag_begin = 1;
            $msg_req .= " ((`msg_type` IN ('0', '10', '11') ";
			$msg_req .= " AND `status` IN (" . implode(',', $svc_msg_status_set).")) ";
			$msg_req .= ") ";
		}
		if ($flag_begin) {
		    $msg_req .= ")";
		}
		if ((count($host_msg_status_set) || count($svc_msg_status_set)) && $oh == 'true') {
		    $msg_req .= " AND ";
		}
		if ($oh == 'true') {
			$flag_begin = 1;
		    $msg_req .= " `type` = '".TYPE_HARD."' ";
		}
	}
	if ($error == 'true') {
	    if ($flag_begin == 0) {
			$msg_req .= "AND ";
		} else
			$msg_req .= " OR ";
		$msg_req .= " (`msg_type` IN ('4') AND `status` IS NULL) ";
	}
	if ($flag_begin) {
        $msg_req = " AND (".$msg_req.") ";
	}


    $tab_id = preg_split("/\,/", $openid);
    $tab_host_name = array();
    $tab_svc = array();

    foreach ($tab_id as $openid) {
        $tab_tmp = preg_split("/\_/", $openid);
        $id = "";
        $hostId = "";
        if (isset($tab_tmp[1]))
            $id = $tab_tmp[1];
        if (isset($tab_tmp[2])) {
            $hostId = $tab_tmp[2];
        }
        if ($id == "") {
            continue;
        }

        $type = $tab_tmp[0];
        if ($type == "HG" && (isset($lca["LcaHostGroup"][$id]) || $is_admin)){
            # Get hosts from hostgroups
            $hosts = getMyHostGroupHosts($id);
            foreach ($hosts as $h_id) {
                if (isset($lca["LcaHost"][$h_id])) {
                    $host_name = getMyHostName($h_id);
                    $tab_host_name[] = $host_name;
                    $tab_svc[$host_name] = $lca["LcaHost"][$h_id];
                }
            }
        } else if ($type == 'ST' && (isset($lca["LcaSG"][$id]) || $is_admin)){
            $services = getMyServiceGroupServices($id);
            foreach ($services as $svc_id => $svc_name) {
                $tab_tmp = preg_split("/\_/", $svc_id);
                $tmp_host_id = $tab_tmp[0];
                $tmp_service_id = $tab_tmp[1];
                $tab = preg_split("/\:/", $svc_name);
                            $host_name = $tab[3];
                if (isset($lca["LcaHost"][$tmp_host_id][$tmp_service_id])) {
                    $tab_svc[$host_name][$tmp_service_id] = $lca["LcaHost"][$tmp_host_id][$tmp_service_id];
                }
            }
        } else if ($type == "HH" && isset($lca["LcaHost"][$id])) {
            $host_name = getMyHostName($id);
            $tab_host_name[] = $host_name;
            $tab_svc[$host_name] = $lca["LcaHost"][$id];
        } else if ($type == "HS" && isset($lca["LcaHost"][$hostId][$id])) {
            $host_name = getMyHostName($hostId);
            $tab_svc[$host_name][$id] = $lca["LcaHost"][$hostId][$id];
        } else if ($type == "MS") {
            $tab_svc["_Module_Meta"][$id] = "meta_".$id;
        }
    }

    $req = "SELECT SQL_CALC_FOUND_ROWS * FROM `".($oreon->broker->getBroker() == "ndo" ? "log" : "logs")."` WHERE `ctime` > '$start' AND `ctime` <= '$end' $msg_req";

    /*
        * Add Host
        */
        $str_unitH = "";
    $str_unitH_append = "";

        foreach ($tab_host_name as $host_name ) {
            $str_unitH .= $str_unitH_append . "'$host_name'";
            $str_unitH_append = ", ";
        }
    if ($str_unitH != "") {
        $str_unitH = "(`host_name` IN ($str_unitH) AND service_description IS NULL)";
    }

    /*
     * Add services
     */
    $flag = 0;
    $str_unitSVC = "";
    if (count($tab_svc) > 0 && ($ok == 'true' || $warning == 'true' || $critical == 'true' || $unknown == 'true')) {
        $req_append = "";
        foreach ($tab_svc as $host_name => $services) {
            $str = "";
            $str_append = "";
            foreach ($services as $svc_id => $svc_name) {
                $str .= $str_append . "'$svc_name'";
                $str_append = ", ";
            }
            if ($str != "") {
                $str_unitSVC .= $req_append . " (`host_name` = '".$host_name."' AND `service_description` IN ($str)) ";
                $req_append = " OR";
            }
        }
        if ($str_unitH != "" && $str_unitSVC != "") {
            $str_unitSVC = " OR " . $str_unitSVC;
        }
    }
    if ($str_unitH != "" || $str_unitSVC != "") {
        $req .= " AND (".$str_unitH.$str_unitSVC.")";
    }
    if ($str_unitH  == "" && $str_unitSVC == "" && !isset($_GET['export'])) {
        $req = "";
    }


	/*
	 * calculate size before limit for pagination
	 */

	if (isset($req) && $req) {

		/*
		 * Add Suffix for order
		 */
		$req .= $suffix_order;

        if ($num < 0)
            $num = 0;

        if (isset($csv_flag) && ($csv_flag == 1)) {
                $DBRESULT = $pearDBO->query($req . " LIMIT 0,64000"); //limit a little less than 2^16 which is excel maximum number of lines
        } else {
                $DBRESULT = $pearDBO->query($req . " LIMIT " . $num * $limit . ", " . $limit);
        }
        $rows = $pearDBO->numberRows();

        if (!($DBRESULT->numRows()) && ($num != 0)) {
                $DBRESULT = $pearDBO->query($req . " LIMIT " . (floor($rows / $limit) * $limit) . ", " . $limit);
        }

        require_once $centreon_path . "www/include/common/checkPagination.php";

		/*
		 * pagination
		 */

		$pageArr = array();
		$istart = 0;

		for ($i = 5, $istart = $num; $istart > 0 && $i > 0; $i--)
			$istart--;

		for ($i2 = 0, $iend = $num; ( $iend <  ($rows / $limit -1)) && ( $i2 < (5 + $i)); $i2++)
			$iend++;

		for ($i = $istart; $i <= $iend; $i++)
			$pageArr[$i] = array("url_page"=>"&num=$i&limit=".$limit, "label_page"=>($i +1),"num"=> $i);

		if ($i > 1){
			foreach ($pageArr as $key => $tab) {
				$buffer->startElement("page");
				if ($tab["num"] == $num)
					$buffer->writeElement("selected", "1");
				else
					$buffer->writeElement("selected", "0");
				$buffer->writeElement("num", $tab["num"]);
				$buffer->writeElement("url_page", $tab["url_page"]);
				$buffer->writeElement("label_page", $tab["label_page"]);
				$buffer->endElement();
			}
		}

		$prev = $num - 1;
		$next = $num + 1;

		if ($num > 0) {
			$buffer->startElement("first");
			$buffer->writeAttribute("show", "true");
			$buffer->text("0");
			$buffer->endElement();
		} else {
			$buffer->startElement("first");
			$buffer->writeAttribute("show", "false");
			$buffer->text("none");
			$buffer->endElement();
		}

		if ($num > 1) {
			$buffer->startElement("prev");
			$buffer->writeAttribute("show", "true");
			$buffer->text($prev);
			$buffer->endElement();
		} else {
			$buffer->startElement("prev");
			$buffer->writeAttribute("show", "false");
			$buffer->text("none");
			$buffer->endElement();
		}

		if ($num < $page_max - 1) {
			$buffer->startElement("next");
			$buffer->writeAttribute("show", "true");
			$buffer->text($next);
			$buffer->endElement();
		} else {
			$buffer->startElement("next");
			$buffer->writeAttribute("show", "false");
			$buffer->text("none");
			$buffer->endElement();
		}

		$last = $page_max - 1;

		if ($num < $page_max-1) {
			$buffer->startElement("last");
			$buffer->writeAttribute("show", "true");
			$buffer->text($last);
			$buffer->endElement();
		} else {
			$buffer->startElement("last");
			$buffer->writeAttribute("show", "false");
			$buffer->text("none");
			$buffer->endElement();
		}

		/*
		 * Full Request
		 */
		$cpts = 0;
		while ($log = $DBRESULT->fetchRow()) {
			$buffer->startElement("line");
			$buffer->writeElement("msg_type", $log["msg_type"]);
			$displayType = $log['type'];
			if (isset($tab_type[$log['type']])) {
			    $displayType = $tab_type[$log['type']];
			}
			$log["msg_type"] > 1 ? $buffer->writeElement("retry", "") : $buffer->writeElement("retry", $log["retry"]);
			$log["msg_type"] == 2 || $log["msg_type"] == 3 ? $buffer->writeElement("type", "NOTIF") : $buffer->writeElement("type", $displayType);

			/*
			 * Color initialisation for services and hosts status
			 */
			$color = '';
		    if (isset($log["status"])) {
                if (isset($tab_color_service[$log["status"]]) && isset($log["service_description"]) && $log["service_description"] != "") {
                    $color = $tab_color_service[$log["status"]];
                } else if (isset($tab_color_host[$log["status"]])) {
                    $color = $tab_color_host[$log["status"]];
                }
            }

			/*
			 * Variable initialisation to color "INITIAL STATE" on envent logs
			 */
	        if ($log["output"] == "" && $log["status"] != "")
	        	$log["output"] = "INITIAL STATE";

			$buffer->startElement("status");
			$buffer->writeAttribute("color", $color);
			$displayStatus = $log["status"];
			if ($log['service_description'] && isset($tab_status_service[$log['status']])) {
			    $displayStatus = $tab_status_service[$log['status']];
			} elseif (isset($tab_status_host[$log['status']])) {
			    $displayStatus = $tab_status_host[$log['status']];
			}
			$buffer->text($displayStatus);
			$buffer->endElement();
			if ($log["host_name"] == "_Module_Meta") {
				preg_match('/meta_([0-9]*)/', $log["service_description"], $matches);
				$DBRESULT2 = $pearDB->query("SELECT * FROM meta_service WHERE meta_id = '".$matches[1]."'");
				$meta = $DBRESULT2->fetchRow();
				$DBRESULT2->free();
				$buffer->writeElement("host_name", $log["host_name"], false);
				$buffer->writeElement("service_description", $meta["meta_name"], false);
				unset($meta);
			} else {
				$buffer->writeElement("host_name", $log["host_name"], false);
				if ($export)
					$buffer->writeElement("address", $HostCache[$log["host_name"]], false);
				$buffer->writeElement("service_description", $log["service_description"], false);
			}
			$buffer->writeElement("class", $tab_class[$cpts % 2]);
			$buffer->writeElement("date", $centreonGMT->getDate(_("Y/m/d"), $log["ctime"]));
			$buffer->writeElement("time", $centreonGMT->getDate(_("H:i:s"), $log["ctime"]));
			$buffer->writeElement("output", $log["output"]);
			$buffer->writeElement("contact", $log["notification_contact"], false);
			$buffer->writeElement("contact_cmd", $log["notification_cmd"], false);
			$buffer->endElement();
			$cpts++;
		}
	}

	/*
	 * Translation for Menu.
	 */
	$buffer->startElement("lang");
	$buffer->writeElement("ty", _("Message Type"), 0);
	$buffer->writeElement("n", _("Notifications"), 0);
	$buffer->writeElement("a", _("Alerts"), 0);
	$buffer->writeElement("e", _("Errors"), 0);
	$buffer->writeElement("s", _("Status"), 0);
	$buffer->writeElement("do", _("Down"), 0);
	$buffer->writeElement("up", _("Up"), 0);
	$buffer->writeElement("un", _("Unreachable"), 0);
	$buffer->writeElement("w", _("Warning"), 0);
	$buffer->writeElement("ok", _("Ok"), 0);
	$buffer->writeElement("cr", _("Critical"), 0);
	$buffer->writeElement("uk", _("Unknown"), 0);
	$buffer->writeElement("oh", _("Hard Only"), 0);
	$buffer->writeElement("sch", _("Search"), 0);

	/*
	 * Translation for tables.
	 */
	$buffer->writeElement("d", _("Day"), 0);
	$buffer->writeElement("t", _("Time"), 0);
	$buffer->writeElement("h", _("Host Status"), 0);
	$buffer->writeElement("sc", _("Service Status"), 0);
	$buffer->writeElement("T", _("Type"), 0);
	$buffer->writeElement("R", _("Retry"), 0);
	$buffer->writeElement("o", _("Output"), 0);
	$buffer->writeElement("c", _("Contact"), 0);
	$buffer->writeElement("C", _("Command"), 0);

	$buffer->endElement();
	$buffer->endElement();
	$buffer->output();

	/*
	 * Saves user's period selection
	 */
	if ($period != "-1") {
		set_user_param($contact_id, $pearDB, "log_filter_period", $period);
	} else {
		set_user_param($contact_id, $pearDB, "log_filter_period", "0");
	}
?>
