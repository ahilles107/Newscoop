<?php
require_once($_SERVER['DOCUMENT_ROOT']. "/$ADMIN_DIR/pub/issues/sections/section_common.php");

list($access, $User) = check_basic_access($_REQUEST);
if (!$access) {
	header("Location: /$ADMIN/logout.php");
	exit;
}

if (!$User->hasPermission('ManageSection')) {
	CampsiteInterface::DisplayError("You do not have the right to add sections.");	
	exit;
}

$Pub = Input::Get('Pub', 'int', 0);
$Issue = Input::Get('Issue', 'int', 0);
$Language = Input::Get('Language', 'int', 0);

if (!Input::IsValid()) {
	CampsiteInterface::DisplayError(array('Invalid input: $1', Input::GetErrorString()), $_SERVER['REQUEST_URI']);
	exit;		
}
$publicationObj =& new Publication($Pub);
$issueObj =& new Issue($Pub, $Language, $Issue);
$newSectionNumber = Section::GetUnusedSectionId($Pub, $Issue, $Language);

## added by sebastian
if (function_exists ("incModFile")) {
	incModFile ();
}

$topArray = array('Pub' => $publicationObj, 'Issue' => $issueObj);
CampsiteInterface::ContentTop('Add new section', $topArray);

?>
<P>
<CENTER>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="6" CLASS="table_input" ALIGN="CENTER">
<FORM NAME="dialog" METHOD="POST" ACTION="do_add.php" >
<TR>
	<TD COLSPAN="2">
		<B><?php  putGS("Add new section"); ?></B>
		<HR NOSHADE SIZE="1" COLOR="BLACK">
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Name"); ?>:</TD>
	<TD>
	<INPUT TYPE="TEXT" class="input_text" NAME="cName" SIZE="32" MAXLENGTH="64">
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Number"); ?>:</TD>
	<TD>
	<INPUT TYPE="TEXT" class="input_text" NAME="cNumber" VALUE="<?php  p($newSectionNumber); ?>" SIZE="5" MAXLENGTH="5">
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("URL Name"); ?>:</TD>
	<TD>
	<INPUT TYPE="TEXT" class="input_text" NAME="cShortName" SIZE="32" MAXLENGTH="32">
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Subscriptions"); ?>:</TD>
	<TD>
	<INPUT TYPE="checkbox" NAME="cSubs" class="input_checkbox"> <?php  putGS("Add section to all subscriptions."); ?>
	</TD>
</TR>

<TR>
	<TD COLSPAN="2">
	<DIV ALIGN="CENTER">
	<INPUT TYPE="HIDDEN" NAME="Pub" VALUE="<?php  p($Pub); ?>">
	<INPUT TYPE="HIDDEN" NAME="Issue" VALUE="<?php  p($Issue); ?>">
	<INPUT TYPE="HIDDEN" NAME="Language" VALUE="<?php  p($Language); ?>">
	<INPUT TYPE="submit" class="button" NAME="Save" VALUE="<?php  putGS('Save changes'); ?>">
	<INPUT TYPE="button" class="button" NAME="Cancel" VALUE="<?php  putGS('Cancel'); ?>" ONCLICK="location.href='/admin/pub/issues/sections/?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Language=<?php  p($Language); ?>'">
	</DIV>
	</TD>
</TR>
</TABLE></CENTER>
</FORM>
<P>

<?php CampsiteInterface::CopyrightNotice(); ?>