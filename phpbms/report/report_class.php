<?php
/*
 $Rev: 290 $ | $LastChangedBy: brieb $
 $LastChangedDate: 2007-08-27 18:15:00 -0600 (Mon, 27 Aug 2007) $
 +-------------------------------------------------------------------------+
 | Copyright (c) 2004 - 2010, Kreotek LLC                                  |
 | All rights reserved.                                                    |
 +-------------------------------------------------------------------------+
 |                                                                         |
 | Redistribution and use in source and binary forms, with or without      |
 | modification, are permitted provided that the following conditions are  |
 | met:                                                                    |
 |                                                                         |
 | - Redistributions of source code must retain the above copyright        |
 |   notice, this list of conditions and the following disclaimer.         |
 |                                                                         |
 | - Redistributions in binary form must reproduce the above copyright     |
 |   notice, this list of conditions and the following disclaimer in the   |
 |   documentation and/or other materials provided with the distribution.  |
 |                                                                         |
 | - Neither the name of Kreotek LLC nor the names of its contributore may |
 |   be used to endorse or promote products derived from this software     |
 |   without specific prior written permission.                            |
 |                                                                         |
 | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A |
 | PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT      |
 | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 |                                                                         |
 +-------------------------------------------------------------------------+
*/

/**
 * Basic reporting class
 *
 * The phpbmsReport class handles basic processing of reports and report type
 * functions. It is designed to be extended by a specifc report. It provides
 * functions for retrieving the where clause, sort order and group by fields
 * as well as retrieving report settings
 * @author Brian Rieb <brieb@kreotek.com>
 */
class phpbmsReport{

    /**
     * $db
     * @var object the database object
     */
    var $db;

    /**
     * $whereClasue
     * @var string whereclause used to filter results
     */
    var $whereClause = "";

    /**
     * $sortOrder
     * @var string sortorder for results
     */
    var $sortOrder = "";

    /**
     * $groupBy
     * @var string sort order for result SQL
     */
    var $groupBy = "";

    /**
     * $reportOutput
     * @var string output generated by report
     */
    var $reportOutput = "";

    /**
     * $settings
     * @var array array of report settings
     */
    var $settings = array();

    /**
     * $reportUUID;
     * @var string UUID of report record
     */
    var $reportUUID = "";

    /**
     * $tabledefUUID
     * @var string UUID of table definition that initiated report
     */
    var $tabledefUUID = "";


    /**
     * function phpbmsReport
     *
     * Initialization Function
     *
     * @param object $db database object
     * @param string $reportUUID UUID of report record
     * @param string $tabledefUUID UUID of table definition that initiated report
     */
    function phpBMSReport($db, $reportUUID, $tabledefUUID){

        $this->db = $db;

        $this->reportUUID = mysql_real_escape_string($reportUUID);

        $this->tabledefUUID = mysql_real_escape_string($tabledefUUID);

        if($reportUUID)
            $this->retrieveReportSettings();

    }//end function init


    /**
     * function retrieveReportSettings()
     *
     * Retrieves settings for specific report from database and stores them in
     *  settings array.
     */
    function retrieveReportSettings(){

        $querystatement = "
            SELECT
                `name`,
                `value`,
                `type`
            FROM
                reportsettings
            WHERE
                reportuuid = '".$this->reportUUID."'";

        $queryresult = $this->db->query($querystatement);

        while($therecord = $this->db->fetchArray($queryresult)){

            switch($therecord["type"]){

                case "int":
                    $therecord["value"] = (int) $therecord["value"];
                    break;

                case "bool":
                    $therecord["value"] = (bool) $therecord["value"];
                    break;

                case "real":
                    $therecord["value"] = (real) $therecord["value"];
                    break;

            }//endswitch

            $this->settings[$therecord["name"]] = $therecord["value"];

        }//endwhile

    }//end function retrieveReportSettings

    /**
     * function setupFromPrintScreen
     *
     * Retrieves session information pertaining to printing as set by the print
     * screen.
     */
    function setupFromPrintScreen(){

        if(isset($_SESSION["printing"]["sortorder"]))
            $this->sortOrder = $_SESSION["printing"]["sortorder"];

        if(isset($_SESSION["printing"]["whereclause"])){

            if(strpos($_SESSION["printing"]["whereclause"],"WHERE") === 0)
                $this->whereClause = substr($this->whereClause, 5);

            $this->whereClause = $_SESSION["printing"]["whereclause"];

        }//endif

        //backwards compatibility
        if(strpos($this->whereClause, "where ") === 0)
            $this->whereClause = substr($this->whereClause, 6);

    }//end function setupFromPrintScreen

    /**
     * function getTableDefInfo
     *
     * Retrieves pertinent table definition information
     * @return array table definition record information
     */
    function getTableDefInfo(){

        $querystatement = "
            SELECT
                *
            FROM
                tabledefs
            WHERE
                uuid = '".$this->tabledefUUID."'";

        $queryresult = $this->db->query($querystatement);

        return $this->db->fetchArray($queryresult);

    }//end function getTableDefInfo

    /**
     * function assembleSQL
     *
     * assembles record query
     *
     * @param string $querystatement SELECT and FROM clauses of SQL statement
     *
     * @return string Retruns full SQL statement
     */
    function assembleSQL($querystatement){

        if($this->whereClause)
            $querystatement .= "
                WHERE
                    ".$this->whereClause;

        if($this->groupBy)
            $querystatement .= "
                GROUP BY
                    ".$this->groupBy;

        if($this->sortOrder)
            $querystatement .= "
                ORDER BY
                    ".$this->sortOrder;
            return $querystatement;

    }//end function assembleSQL


    /**
     * function showNoRecords
     *
     * Outputs simple no records error
     */
    function showNoRecords(){

        ?>
        <h1 id="noRecord">No Records</h1>
        <p>No valid records for this report.</p>
        <?php

    }//end function showNoRecords


    /**
     * function addingRecordDefaultSettings
     *
     * Creates an array of settings associative arrays for use by the system when
     * a new report record is added that references the file containing this class
     *
     * @retrun array of settings. Each setting should itself be
     * an associative array containing the following
     * name: name of the setting
     * defaultvalue: default value for setting
     * type: (string, text, int, real, bool) type for value of setting
     * required: (0,1) whether the setting is required or not
     * description: brief description for what this setting is used for.
     */
    function addingRecordDefaultSettings(){

        $settings[] = array(
            "name"=>"reportTitle",
            "defaultValue"=>"Report",
            "type"=>"string",
            "required"=>0,
            "description"=>"Report Title"
        );

        return $settings;

    }//endfunction addingRecordDefaultSettings

}//end class

/**
 * function checkForReportArguments
 *
 * used before class instatiation to make sure POST arguments are set correctly
 */
function checkForReportArguments(){

    if(!isset($_GET["tid"]))
            $error = new appError(200,"URL variable missing: tid");

    if(!isset($_GET["rid"]))
            $error = new appError(200,"URL variable missing: rid");

}//end function checkForReportArguments
?>
