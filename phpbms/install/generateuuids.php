<?php
/*
 $Rev: 427 $ | $LastChangedBy: nate $
 $LastChangedDate: 2008-08-13 12:09:00 -0600 (Wed, 13 Aug 2008) $
 +-------------------------------------------------------------------------+
 | Copyright (c) 2004 - 2007, Kreotek LLC                                  |
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
define("APP_DEBUG",false);
define("noStartup",true);

require("install_include.php");
require("../include/session.php");
require("../include/common_functions.php");


class generateUUIDS extends installUpdateBase{

    var $userList;
    var $roleList;
    var $tabledefList;


    function process(){

        $this->phpbmsSession = new phpbmsSession;

        if($this->phpbmsSession->loadDBSettings(false)){

            @ include_once("include/db.php");

            $this->db = new db(false);
            $this->db->stopOnError = false;
            $this->db->showError = false;
            $this->db->logError = false;

        } else
            return $this->returnJSON(false, "Could not open session.php file");

        if(!$this->db->connect())
            return $this->returnJSON(false, "Could not connect to database ".$this->db->getError());

        if(!$this->db->selectSchema())
            return $this->returnJSON(false, "Could not open database schema '".MYSQL_DATABASE."'");


        $this->roleList = $this->generateUUIDList("roles");
        $this->roleList[-100] = "Admin";
        $this->roleList[0] = "";

        $this->userList = $this->generateUUIDList("users");
        $this->userList[0] ="";

        $this->tabledefList = $this->generateUUIDList("tabledefs");

        //function calls for all we have to do go here
        //======================================================================
        //$this->updateFields("rolestousers", array("userid"=>$this->userList, "roleid"=>$this->roleList));
        //$this->updateFields("tablecolumns", array("tabledefid"=>$this->tabledefList, "roleid"=>$this->roleList));
        //$this->updateFields("tablefindoptions", array("tabledefid"=>$this->tabledefList, "roleid"=>$this->roleList));
        $this->updateFields("tablegroupings", array("tabledefid"=>$this->tabledefList, "roleid"=>$this->roleList));


        return $this->returnJSON(true, "UUID's Generated");

    }//endfunction process


    function generateUUIDList($table, $whereclause = ""){

        $querystatement = "
            SELECT
                `id`,
                `uuid`
            FROM
                `".$table."`";

        if($whereclause)
            $querystatement .= "
                WHERE ".$whereclause;

        $queryresult = $this->db->query($querystatement);

        $list = array();
        while($therecord = $this->db->fetchArray($queryresult))
            $list[$therecord["id"]] = $therecord["uuid"];

        return $list;

    }//end function generateUUDIList


    function updateFields($table, $fields){

        $fieldClause ="`id` ";

        foreach($fields as $key=>$value)
            $fieldClause .= ", `".$key."`";

        $querystatement = "
            SELECT
                ".$fieldClause."
            FROM
                `".$table."`";

        $queryresult = $this->db->query($querystatement);

        while($therecord = $this->db->fetchArray($queryresult)){

            $updateClause = "";

            foreach($fields as $key=>$value)
                $updateClause .= ", `$key` = '".$value[$therecord[$key]]."'";

            $updateClause = substr($updateClause, 1);

            $updatestatement = "
                UPDATE
                    `".$table."`
                SET
                    $updateClause
                WHERE
                    `id` = ".$therecord["id"]."
            ";

//echo $updatestatement."<br />";
            $this->db->query($updatestatement);

        }//endwhile

    }//end function updateField


}//end class updateAjax


// START PROCESSING
//==============================================================================

$genUUIDS = new generateUUIDS();

echo $genUUIDS->process();
