<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	$username = $_SESSION[ 'login_user' ];
	
	// Connect to database
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
	
	// Check if a question id is entered as parameter
	if( isset( $_GET[ 'id' ] ) )
	{
		$postID = $_GET[ 'id' ];
	}
	else
	{
		header( "location: welcome.php" );
	}
	
	// Check if entry id is valid
	$result = mysqli_query( $db, "select entryType from Entry where entryID=$postID" );
	if( mysqli_num_rows( $result ) === 0 )
	{
		// entry does not exist
		header( "location: welcome.php" );
	}
	
	// Check if entry is closed
	$result2 = mysqli_query( $db, "select entryID from closed_by where entryID=$postID" );
	if( mysqli_num_rows( $result2 ) !== 0 )
	{
		// entry is closed
		header( "location: question.php?id=$postID&err=1" );
	}
	
	$row = mysqli_fetch_assoc( $result );
	$entryType = $row['entryType'];
	
	// Check if user has permission to edit this entry
	$result1 = mysqli_query( $db, "call canEditEntry($postID,'$username',@result);" );
	$result2 = mysqli_query( $db, "select @result as res;" );
	$canEdit = mysqli_fetch_assoc( $result2 );
	$canEdit = $canEdit['res'];
	
	if( $canEdit == 0 )
	{
		// user does not have permission
		header( "location: question.php?id=$postID" );
	}
	
	// Check if user has permission to close this entry
	if( $entryType == 'Q' )
	{
		$result1 = mysqli_query( $db, "call canCloseEntry($postID,'$username',@result);" );
		$result2 = mysqli_query( $db, "select @result as res;" );
		$canClose = mysqli_fetch_assoc( $result2 );
		$canClose = $canClose['res'];
	}
	
	// Check if user has permission to delete this entry
	$result1 = mysqli_query( $db, "call canDeleteEntry($postID,'$username',@result);" );
	$result2 = mysqli_query( $db, "select @result as res;" );
	$canDelete = mysqli_fetch_assoc( $result2 );
	$canDelete = $canDelete['res'];
	
	// When a button is pressed
	if( $_SERVER[ "REQUEST_METHOD" ] == "POST" )
	{
		if ( isset( $_POST[ 'confirm' ]) )
		{
			$content = mysql_real_escape_string( $_POST[ 'content' ] );
			$description = mysql_real_escape_string( $_POST[ 'reason' ] );
			mysqli_query( $db, "update Entry set text='$content' where entryID=$postID" );
			mysqli_query( $db, "insert into edits values($postID, '$username', CURRENT_TIMESTAMP, '$description')" );
			header( "location: question.php?id=$postID" );
		}
		else if( isset( $_POST[ 'close' ] ) ) 
		{
			if( $canClose != 0 )
			{
				$description = mysql_real_escape_string( $_POST[ 'reason' ] );
				mysqli_query( $db, "insert into closed_by values($postID, '$username', CURRENT_TIMESTAMP, '$description')" );
				
				header( "location: question.php?id=$postID" );
			}
		}
		else if( isset( $_POST[ 'delete' ] ) ) 
		{
			if( $canDelete != 0 )
			{
				if( $entryType == 'Q' )
				{
					$result = mysqli_query( $db, "call deleteEntry($postID);" );
					
					if( $result )
						header( "location: welcome.php?delete=1" );
					else
						header( "location: welcome.php?delete=2" );
				}
				else
				{
					// if entry is not a question but an answer or comment, find the redirection entry ID
					$row = mysqli_fetch_assoc( $result );
					if( $entryType == 'A' || $entryType == 'Q_C' )
					{
						// Parent entry is question itself
						$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=$postID" );
						$row = mysqli_fetch_assoc( $result );
					}
					else
					{
						// Parent entry is an answer, its parent is question
						$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=$postID" );
						$row = mysqli_fetch_assoc( $result );
						$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=".$row['parentEntryID'] );
						$row = mysqli_fetch_assoc( $result );
					}
					
					$result = mysqli_query( $db, "call deleteEntry($postID);" );
					header( "location: question.php?id=".$row['parentEntryID']."&delete=1" );
				}
			}
		}
		else if( isset( $_POST[ 'cancel' ] ) ) 
		{
			header( "location: question.php?id=$postID" );
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateForm()
{
	// If username or password is empty, give error message
	var content = document.forms["EditEntry"]["content"].value;
	
	if( content == null || content == "" )
	{
		alert( "ERROR: Content can't be empty!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Edit Entry</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<table>
		<tr>
		<td>Edit entry:</td><td>Previous:</td>
		</tr>
		<form name="EditEntry" method="post">
		<tr>
		<?php
			$result = mysqli_query( $db, "select text from Entry where entryID=$postID" );
			$content = mysqli_fetch_assoc( $result );
			
			echo "<td><textarea rows=\"4\" cols=\"50\" name=\"content\">".$content['text']."</textarea></td>\n";
			echo "<td><textarea rows=\"4\" cols=\"50\" readonly=\"readonly\">".$content['text']."</textarea></td>\n";
		?>
		</tr>
		<tr>
		<td colspan=2>
		Reason to edit: <input type="text" size=50 maxlength=250 name="reason" />
		</td>
		</tr>
		<tr>
		<td align="center" colspan=2>
		<input type="submit" onclick="return validateForm()" name="confirm" value="Confirm" />
		<input type="submit" name="cancel" value="Cancel" />
		<?php 
			if( $canClose != 0 && $entryType === "Q" )
				echo "<input type=\"submit\" style=\"font-weight:bold; color:red;\" name=\"close\" value=\"Close Entry!\" />";
				
			if( $canDelete != 0 )
				echo "<input type=\"submit\" style=\"font-weight:bold; color:red;\" name=\"delete\" value=\"Delete Entry!\" />";
		?>
		</td>
		</tr>
		</form>
		</table>
	</div>
	</body>
</html>