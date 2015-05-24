<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	$username = $_SESSION[ 'login_user' ];
	
	// Get user's accounts
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
						  
	// Check if user is admin
	$sql = "select username from User where username='$username' and userType='Admin'";
	$result = mysqli_query( $db, $sql );
						  
	// When a button is pressed
	if( $_SERVER[ "REQUEST_METHOD" ] == "POST" )
	{
		if( isset( $_POST[ 'logout' ] ) ) 
		{
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'ask' ] ) ) 
		{
			header( "location: ask.php" );
		}
		else if( isset( $_POST[ 'control' ] ) ) 
		{
			header( "location: profile.php" );
		}
		else if ( isset( $_POST[ 'admin' ]) )
		{
			header( "location: admin.php" );
		}
		else if( isset( $_POST[ 'info' ] ) )
		{
			header( "location: info.php" );
		}
		else if( isset( $_POST[ 'filter' ] ) ) 
		{
			$category = $_POST[ 'category' ];
			$tag = $_POST[ 'tag' ];
			$featured = $_POST[ 'featured' ];
			header( "location: welcome.php?cat=$category&tag=$tag&featured=$featured" );
		}
	}
?>

<html>
	<head>
		<title>Homepage</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:15px;">
		<?php 
			// Show error outputs
			if( $_GET['delete'] == 1 )
			{
				echo "<p><font color=yellow><b>Entry is deleted successfully!</b></font></p>";
			}
			else if( $_GET['delete'] == 2 )
			{
				echo "<p><font color=red><b>ERROR: Entry could not be deleted, please try again!</b></font></p>";
			}
		?>
		
		<p><b><font color=green>Welcome <?php echo $username ?>!</font></b>
		<form name="ControlPanel" method="post">
			<input type="submit" name="control" value="Profile" />
			<input type="submit" name="ask" value="Ask a Question" />
			<?php if ( mysqli_num_rows( $result ) === 1 ): ?>
			<input type="submit" name="admin" value="Admin Panel" />
			<?php endif; ?>
			<input type="submit" name="info" value="Information" />
			<input type="submit" name="logout" value="Logout" />
		</form></p>
		
		<hr style="background:black; border:0; height:3px" />
		
		<p><form name="Filter" method="post">
			<b>Category: </b>
			<select name="category">
				<option value="0">* All *</option>
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
						
						if( $_GET['cat'] === $tuple["catID"] )
							echo "<option selected=\"selected\" value=\"".$tuple["catID"]."\">".$tuple["name"]."</option>";
						else
							echo "<option value=\"".$tuple["catID"]."\">".$tuple["name"]."</option>";
							
						mysqli_data_seek( $result, 0 );
						while( $tuple2 = mysqli_fetch_assoc( $result ) ) 
						{
							if( $_GET['cat'] === $tuple2["catID"] )
								echo "<option selected=\"selected\" value=\"".$tuple2["catID"]."\">-- ".$tuple2["name"]."</option>";
							else
								echo "<option value=\"".$tuple2["catID"]."\">-- ".$tuple2["name"]."</option>";
						}
					}
				?>
			</select>
			<b>Tag (only one): </b>
			<input type="text" size=20 maxlength=50 name="tag" <?php if( isset( $_GET['tag'] ) ) echo "value=\"".$_GET['tag']."\""; ?>/>
			<b>Featured: </b>
			<select name="featured">
				<option value="all" >All</option>
				<option value="hot" <?php if( $_GET['featured'] === "hot" ) echo "selected=\"selected\""; ?>>Hot Questions</option>
				<option value="new" <?php if( $_GET['featured'] === "new" ) echo "selected=\"selected\""; ?>>New Questions</option>
				<option value="unanswered" <?php if( $_GET['featured'] === "unanswered" ) echo "selected=\"selected\""; ?>>Unanswered Questions</option>
			</select>
			<input type="submit" name="filter" value="Filter" />
		</form></p>
		
		<hr style="background:black; border:0; height:2px" />
		
		<table>
			<?php
			if( $_GET['featured'] === "hot" )
				$from = "hot_questions";
			else if( $_GET['featured'] === "new" )
				$from = "new_questions";
			else if( $_GET['featured'] === "unanswered" )
				$from = "unanswered_questions";
			else
				$from = "Entry";
			
			$sql = "select entryID, title, timestamp, noOfViews, upvotes-downvotes as votes ".
				   "from $from E where E.entryType='Q' ";
			
			if( isset( $_GET['tag'] ) && strlen( $_GET['tag'] ) > 0 )
			{
				$tag = $_GET['tag'];
				$sql = $sql."and E.entryID in (select ET.entryID from entry_tag ET where ET.tagName='$tag') ";
			}
			else if( $_GET['cat'] != 0 )
			{
				$cat = $_GET['cat'];
				$sql = $sql."and (E.catID = $cat or E.catID in".
							"(select CA.catID as catID from Category CA, sub_category ".
							"where parentCatID = $cat and CA.catID=childCatID))";
			}
			
			$questions = mysqli_query( $db, $sql );
			while( $tuple = mysqli_fetch_assoc( $questions ) ) 
			{
				$answerC = mysqli_query( $db, "select count(*) as count from Entry, has_parent where entryID=".$tuple["entryID"].
											  " and entryID=parentEntryID" );
				$tupleC = mysqli_fetch_assoc( $answerC );
				echo "<tr>\n<td><a href=\"question.php?id=".$tuple["entryID"]."\">".$tuple["title"]."</a></td>\n<td>Views: ".$tuple["noOfViews"]."</td>\n".
					 "<td>Answers: ".$tupleC["count"]."</td>\n<td>Votes: ".$tuple["votes"]."</td>\n</tr>".
					 "<tr>\n<td>Posted: ".$tuple["timestamp"]."</td><td colspan=3></td>\n</tr>".
					 "<tr>\n<td colspan=4><hr style=\"background:black; border:0; height:1px\" /></td>\n</tr>\n";
			}
			?>
		</table>
	</div>
	</body>
</html>