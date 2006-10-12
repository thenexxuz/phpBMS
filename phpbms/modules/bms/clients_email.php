<?php 
/*
 +-------------------------------------------------------------------------+
 | Copyright (c) 2005, Kreotek LLC                                         |
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
	include("../../include/session.php");
	include("../../include/common_functions.php");
	include("../../include/fields.php");

	include("include/clients_email_include.php");
	
	
	$thecommand="showoptions";
	if(isset($_POST["command"])) $thecommand=$_POST["command"];
	
	switch($thecommand){
		case "send email":
			 // first build the where clause
			switch($_POST["therecords"]){
				case "selected":
					$whereclause="WHERE ";
					foreach($_SESSION["emailids"] as $id)
						$whereclause.="clients.id=".$id." or ";
					$whereclause=substr($whereclause,0,strlen($whereclause)-3);					
				break;
				case "savedsearch":
					$querystatement="SELECT sqlclause FROM usersearches WHERE id=".$_POST["savedsearches"];
					$queryresult=mysql_query($querystatement,$dblink);
					$therecord=mysql_fetch_array($queryresult);
					$whereclause="WHERE ".$therecord["sqlclause"];
				break;
				case "all":
					$whereclause="";
				break;						
			}//end switch
			//next the from:
			$_SESSION["massemail"]["from"]=str_replace("]",">",str_replace("[","<",$_POST["ds-email"]));			
			$_SESSION["massemail"]["whereclause"]=$whereclause;
			$_SESSION["massemail"]["subject"]=$_POST["subject"];
			$_SESSION["massemail"]["body"]=$_POST["body"];
			$_SESSION["massemail"]["savedproject"]=$_POST["pid"];
			
			$querystatement="SELECT id,email, if(clients.lastname!=\"\",concat(clients.lastname,\", \",clients.firstname,if(clients.company!=\"\",concat(\" (\",clients.company,\")\"),\"\")),clients.company) AS name FROM clients ".$whereclause;
			$sendqueryresult=mysql_query($querystatement,$dblink);
			if(!$sendqueryresult) reportError(300,"Error with: ".$querystatement);
			
		break;
		case "delete project":
			deleteProject($_POST["projectid"]);
			$statusmessage="Project Deleted";
			$thecommand="showoptions";
		case "showoptions":
			$therecord["emailto"]="selected";
			$therecord["emailfrom"]=$_SESSION["userinfo"]["id"];
			$therecord["subject"]="";
			$therecord["body"]="";
			$therecord["id"]="";
		break;
		case "load project":
			$therecord=loadProject($_POST["projectid"]);
			$statusmessage="Project Loaded";
			$thecommand="showoptions";
		break;
		case "save project":
			$id=saveProject(addSlashesToArray($_POST));
			$statusmessage="Project Saved";
			$therecord=loadProject($id);
			$thecommand="showoptions";
		break;
		
		case "done":
		case "cancel":
			header("Location: ".$_SESSION["app_path"]."search.php?id=2");
		break;
	}
	
	
?><?PHP $pageTitle="Client/Prospect E-Mail"?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo $pageTitle ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="<?php echo $_SESSION["app_path"] ?>common/stylesheet/<?php echo $_SESSION["stylesheet"] ?>/base.css" rel="stylesheet" type="text/css">
<script language="JavaScript" src="../../common/javascript/fields.js"></script>
<script language="JavaScript" src="../../common/javascript/autofill.js"></script>
<script language="JavaScript" src="javascript/clientemail.js"></script>
</head>
<body><?php include("../../menu.php")?>
	<div class="bodyline">
		<h1><?php echo $pageTitle?></h1>
		<form action="<?php echo $_SERVER["PHP_SELF"] ?>" method="post" name="theform" id="theform">
	<?php if($thecommand=="showoptions") { ?>
		<input type="hidden" name="pid" id="pid" value="<?php echo $therecord["id"]?>" />
		<fieldset>
			<label for="savedsearches" id="showsavedsearches" style="display:none;float:right;width:48%">
				load e-mail addresses from saved search...<br>
				<?php showSavedSearches($therecord["emailto"]); ?>
			</label>
			<label for="therecords" style="margin-right:49%">
				to<br />			
				<select id="therecords" name="therecords" onChange="showSavedSearches(this);" style="width:100%;">
					<option value="selected" <?php if ($therecord["emailto"]=="selected") echo "selected"?>>e-mail addresses from selected records (<?php echo count($_SESSION["emailids"]) ?> record<?php if(count($_SESSION["emailids"])>1) echo "s"?>)</option>
					<option value="savedsearch" <?php if ($therecord["emailto"]!="selected" AND $therecord["emailto"]!="all") echo "selected"?>>e-mail addresses from saved search...</option>
					<option value="all" <?php if ($therecord["emailto"]=="all") echo "selected"?>>e-mail addresses from all records</option>
				</select>
				<?php if($therecord["emailto"]!="selected" AND $therecord["emailto"]!="all"){
					?>
						<script language="javascript">
							thediv=getObjectFromID("showsavedsearches");
							thediv.style.display="block";
						</script>
					<?php
					}?>
			</label>
			<label for="email">
				from<br />
				<?PHP 
				if(is_numeric($therecord["emailfrom"]))
					$theid=$therecord["emailfrom"];
				else
					$theid=0;			
				autofill("email",$theid,9,"users.id","concat(users.firstname,\" \",users.lastname,\" [\",users.email,\"]\")","\"\"","users.revoked=0 AND users.email!=\"\"",Array("style"=>"width:50%","maxlength"=>"64"),false,"",false) ?>
				
				<?php 
					if(!is_numeric($therecord["emailfrom"])){
					?><script language="javascript">
							thefield=getObjectFromID("ds-email");
							thefield.value="<?php echo $therecord["emailfrom"] ?>";
					</script>
					<?php
					}
				?>
			</label>
			<label for="subject">
				subject<br />
				<input type="text" name="subject" id="subject" maxlength="128" style="width:99%" value="<?php echo htmlQuotes($therecord["subject"])?>"/>
			</label>
		</fieldset>
		<fieldset>
			<div>
				add field<br>
				<?php showClientFields(); ?>
				<input type="button" name="addfield" id="addfield" value="add field" class="smallButtons" onClick="addField()" />
				<input type="button" name="adddate" id="adddate" value="add today's date" class="smallButtons" onClick="insertAtCursor('body','[[todays_date]]')" />
			</div>
			<div>
				<textarea class="mono" rows="15" name="body" id="body" cols="30" style="width:99%"><?php echo $therecord["body"]?></textarea>
			</div>
		</fieldset>
		
		<div class="box">
			<div style="float:left">
				<input type="button" name="loademail"	id="loademil" value="load project..." class="Buttons" onClick="showSavedProjects();" />
				<input type="button" name="saveproject"	id="saveproject" value="save project..." class="Buttons" style="margin-right:10px;" onClick="return saveProject();" />
				<input type="hidden" name="savename"	id="savename" value="" />
				<input type="hidden" name="projectid"	id="projectid" value="" />
			</div>
			<div align="right">
				<input type="submit" name="command" 	id="sendemail" value="send email" class="Buttons" style="width:90px;" />
				<input type="submit" name="command" 	id="cancel" value="cancel" class="Buttons" style="width:90px;" />
				<input type="submit" name="command" 	id="othercommand" value="" class="Buttons" style="display:none;" />				
			</div>
		</div>
		
		<div id="loadedprojects" style="display:none;">
			<div><?php showSavedProjects()?></div>
			<div align="right">
				<input type="button" name="deleteproject" id="deleteproject" value="delete project" class="Buttons" disabled="true" onClick="deleteProject()">
				<input type="button" name="loadproject" id="loadproject" value="load project" class="Buttons" disabled="true" onClick="loadProject()">
				<input type="button" name="closeproject" id="closeproject" value="cancel" onClick="hideSavedProjects()" class="Buttons">
			</div>
		</div>
<?php } elseif($thecommand=="send email"){?>
		<script language="javascript">		
			ids=new Array();			
			emails=new Array();
			names= new Array();
			<?php 
				while($therecord=mysql_fetch_array($sendqueryresult)){
					echo "ids[ids.length]=".$therecord["id"].";\n";
					echo "names[names.length]=\"".$therecord["name"]."\";\n";
					echo "emails[emails.length]=\"".$therecord["email"]."\";\n";
				}			
			?>			
		</script>
		
		<div class="box">
			<div style="float:right;">
				<div align=right>
				<input type="button" id="beginprocessing" name="beginprocessing" value=" Begin Processing " class="Buttons" onClick="processEmails();" style="width:150px;font-size:12px;">
				</div>
				<div align=right>
				<strong><span id="amountprocessed">0</span> / <?php echo mysql_num_rows($sendqueryresult)?> records processed</strong>
				</div>
				<div align=right><input type="submit" name="command" id="done" value="done" class="Buttons" style="width:150px;"></div>
			</div>
			
			<div style="margin-right:200px;">
				<div>
					<table id="results" cellpadding="0" cellspacing="0" border="0" class="querytable">
						<tr>
							<th nowrap class="queryheader"><strong>#</strong></th>
							<th nowrap class="queryheader"><strong>Client ID</strong></th>
							<th nowrap class="queryheader" align="left"><strong>Name</strong></th>
							<th nowrap class="queryheader"><strong>E-Mail Address</strong></th>
							<th nowrap width=50% align="right" class="queryheader"><strong>Status</strong></th>
						</tr>
					</table>
				</div>
				<div>&nbsp;</div>
				<div>&nbsp;</div>
			</div>
			
		</div>

<?php } ?>
	</form>
	</div>
	<?php include("../../footer.php");?>
</body>
</html>