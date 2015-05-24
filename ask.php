<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: login.php" );
	}
	
	// Connect to database
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
	
	// When a button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'post' ] ) ) 
		{
			// Get information
			$title = mysqli_real_escape_string( $db, $_POST[ 'title' ] );
			$description = mysqli_real_escape_string( $db, $_POST[ 'description' ] );
			$category = mysqli_real_escape_string( $db, $_POST[ 'category' ] );
			$username = $_SESSION[ 'login_user' ];
			$tags = mysqli_real_escape_string( $db, $_POST[ 'tags' ] );
			$tags = preg_replace( '/\s+/', '', $tags );
			$tagsArray = explode( ',', $tags );
			
			// If none of them is empty
			if( strlen( $title ) > 0 && strlen( $description ) > 0 && strlen( $tags ) > 0 )
			{
				$sql = "INSERT INTO Entry VALUES(0, '$title', CURRENT_TIMESTAMP, '$description', 'Q', 0, 0, 0, '$username', '$category')";
				$result = mysqli_query( $db, $sql );
				$entryID = mysqli_insert_id( $db );
				
				$sql2 = "INSERT INTO Event VALUES(0, '$username', 'post_question', CURRENT_TIMESTAMP)";
				mysqli_query( $db, $sql2 );
				
				$sql3 = "SELECT points from EventType where event_type='post_question'";
				$sql4 = "SELECT rep from User where username='$username'";
				$points = mysqli_query( $db, $sql3 );
				$pointValue = mysqli_fetch_assoc( $points );
				$rep = mysqli_query( $db, $sql4 );
				$repValue = mysqli_fetch_assoc( $rep );
				
				$totalRep = $pointValue[ 'points' ] + $repValue[ 'rep' ];
				
				$sql5 = "UPDATE User SET rep=$totalRep WHERE username='$username'";
				mysqli_query( $db, $sql5 );
				
				foreach( $tagsArray as $tag )
				{
					$sql = "INSERT INTO Tag VALUES( '$tag', NULL )";
					mysqli_query( $db, $sql );
					
					$sql = "INSERT INTO entry_tag VALUES( '$tag', $entryID )";
					mysqli_query( $db, $sql );
				}
				
				if( $result )
				{
					header( "location: question.php?id=$entryID" );
				}
				else
				{
					header( "location: ask.php?err=1" );
				}
			}
		}
		else if( isset( $_POST[ 'back' ] ) ) 
		{
			header( "location: welcome.php" );
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateForm()
{
	// If a field is empty, give error message
	var title = document.forms["Ask"]["title"].value;
	var description = document.forms["Ask"]["description"].value;
	var tags = document.forms["Ask"]["tags"].value;
	
	if( title == null || title == "" )
	{
		alert( "ERROR: Title can't be empty!" );
		return false;
	}
	
	if( description == null || description == "" )
	{
		alert( "ERROR: Description can't be empty!" );
		return false;
	}
	
	if( tags == null || tags == "" )
	{
		alert( "ERROR: Tags field can't be empty!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Ask a Question</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<?php 
			// If user tried registering with an already existing username
			if( $_GET['err'] == 1 )
			{
				echo "<p><font color=red><b>ERROR: Something went wrong, please try again!</b></font></p>";
			}
		?>
		
		<table>
		<form name="Ask" method="post">
			<tr>
			<td>Title: </td><td><input type="text" size=50 maxlength=50 name="title" /></td>
			</tr>
			<tr>
			<td>Description: </td><td><textarea rows="4" cols="50" name="description"></textarea></td>
			</tr>
			<tr>
			<td>Category: </td><td>
			<select name="category">
			<?php
				// Create combo boxes for categories
				$sql2 = "SELECT catID, name FROM Category WHERE catID NOT IN(SELECT childCatID FROM sub_category)";
				$categories = mysqli_query( $db, $sql2 );
				mysqli_data_seek( $categories, 0 );
				while( $tuple = mysqli_fetch_assoc( $categories ) ) 
				{
					$sql3 = "SELECT catID, name FROM Category, sub_category ".
							"WHERE catID=childCatID and parentCatID=".$tuple["catID"];
					$result = mysqli_query( $db, $sql3 );
					
					echo "<option value=\"".$tuple["catID"]."\">".$tuple["name"]."</option>";
						
					mysqli_data_seek( $result, 0 );
					while( $tuple2 = mysqli_fetch_assoc( $result ) ) 
					{
						echo "<option value=\"".$tuple2["catID"]."\">-- ".$tuple2["name"]."</option>";
					}
				}
			?>
			</select></td>
			</tr>
			<tr>
			<td>Choose tags (put comma to separate): </td><td><input type="text" size=50 name="tags" /></td>
			</tr>
			<tr>
			<td colspan=2>
			<hr style="background:black; border:0; height:3px" />
			</td>
			</tr>
			<tr>
			<td align="center" colspan=2>
			<input type="submit" onclick="return validateForm()" name="post" value="Post Question" />
			<input type="submit" name="back" value="Back" />
			</td>
			</tr>
		</form>
		</table>
	</div>
	</body>
</html>