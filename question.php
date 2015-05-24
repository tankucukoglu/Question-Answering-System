<?php
	//start a PHP session
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	$user = $_SESSION[ 'login_user' ];
	
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
					
	// Check if a question id is entered as parameter
	if( isset( $_GET[ 'id' ] ) )
	{
		$curPage = $_GET[ 'id' ];
	}
	else
	{
		header( "location: welcome.php" );
	}
	
	// Check if entry id is valid
	$result = mysqli_query( $db, "select entryType from Entry where entryID=$curPage" );
	if( mysqli_num_rows( $result ) === 0 )
	{
		// entry does not exist
		header( "location: welcome.php" );
	}
	
	// if entry is not a question but an answer or comment, redirect to question page
	$row = mysqli_fetch_assoc( $result );
	if( $row['entryType'] == 'A' || $row['entryType'] == 'Q_C' )
	{
		// Parent entry is question itself
		$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=$curPage" );
		$row = mysqli_fetch_assoc( $result );
		header( "location: question.php?id=".$row['parentEntryID'] );
	}
	else if( $row['entryType'] == 'A_C' )
	{
		// Parent entry is an answer, its parent is question
		$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=$curPage" );
		$row = mysqli_fetch_assoc( $result );
		$result = mysqli_query( $db, "select parentEntryID from has_parent where childEntryID=".$row['parentEntryID'] );
		$row = mysqli_fetch_assoc( $result );
		header( "location: question.php?id=".$row['parentEntryID'] );
	}
	
	// check if entry is closed
	$result = mysqli_query( $db, "select entryID from closed_questions where entryID=$curPage" );
	if( mysqli_num_rows( $result ) !== 0 )
		$isClosed = 1;
	else
		$isClosed = 0;
	
	// Increment number of views for this question by 1
	$sql1 = "UPDATE Entry SET noOfViews=noOfViews+1 WHERE entryID=$curPage";
    mysqli_query( $db, $sql1 );
	
    // Get current click count for page from database
	$sql2 = "select title, text, timestamp, username, catID, upvotes-downvotes as votes ".
			"from Entry where entryID=$curPage";
	$result = mysqli_query( $db, $sql2 );
	$values = mysqli_fetch_assoc( $result );
	
	// Get information
	$title = $values['title'];
	$text = $values['text'];
	$username = $values['username'];
	$timestamp = $values['timestamp'];
	$totalVotes = $values['votes'];
	$catID = $values['catID'];
	
	$result = mysqli_query( $db, "select name from Category where catID=$catID" );
	$row = mysqli_fetch_assoc( $result );
	$catValue = $row[ 'name' ];
	
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'plus' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			$plus = "call voteEntry($postID,'$username',1);";
			mysqli_query( $db, $plus );
			header( "location: question.php?id=$curPage&vote=1" );
		}
		else if( isset( $_POST[ 'minus' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			$minus = "call voteEntry($postID,'$username',-1);";
			mysqli_query( $db, $minus );
			header( "location: question.php?id=$curPage&vote=-1" );
		}
		else if( isset( $_POST[ 'edit' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			header( "location: edit.php?id=$postID" );
		}
		else if( isset( $_POST[ 'approve' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			$approve = "insert into approves values($postID, '$username')";
			mysqli_query( $db, $approve );
			header( "location: question.php?id=$curPage&approve=1" );
		}
		else if( isset( $_POST[ 'disapprove' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			$disapprove = "delete from approves where answerID=$postID";
			mysqli_query( $db, $disapprove );
			header( "location: question.php?id=$curPage&approve=-1" );
		}
		else if( isset( $_POST[ 'answerPost' ] ) ) 
		{
			$content = mysql_real_escape_string( $_POST[ 'answerContent' ] );
			
			if( strlen( $content ) > 0 )
			{
				$sql = "INSERT INTO Entry VALUES(0, '', CURRENT_TIMESTAMP, '$content', 'A', 0, 0, 0, '$user', '$catID')";
				mysqli_query( $db, $sql );
				$answerID = mysqli_insert_id( $db );
				
				$sql2 = "INSERT INTO has_parent VALUES($answerID, $curPage)";
				mysqli_query( $db, $sql2 );
				
				$sql4 = "INSERT INTO Event VALUES(0, '$user', 'post_answer', CURRENT_TIMESTAMP)";
				mysqli_query( $db, $sql4 );
				
				$sql5 = "SELECT points from EventType where event_type='post_answer'";
				$sql6 = "SELECT rep from User where username='$user'";
				$points = mysqli_query( $db, $sql5 );
				$pointValue = mysqli_fetch_assoc( $points );
				$rep = mysqli_query( $db, $sql6 );
				$repValue = mysqli_fetch_assoc( $rep );
				
				$totalRep = $pointValue[ 'points' ] + $repValue[ 'rep' ];
				
				$sql7 = "UPDATE User SET rep=$totalRep WHERE username='$user'";
				mysqli_query( $db, $sql7 );				
				
				header( "location: question.php?id=$curPage&answer=1" );
			}
		}
		else if( isset( $_POST[ 'commentPost' ] ) ) 
		{
			$postID = $_POST[ 'eID' ];
			$content = mysql_real_escape_string( $_POST[ 'commentContent' ] );
			
			if( strlen( $content ) > 0 )
			{
				if( $postID == $curPage )
					$sql = "INSERT INTO Entry VALUES(0, '', CURRENT_TIMESTAMP, '$content', 'Q_C', 0, 0, 0, '$user', '$catID')";
				else
					$sql = "INSERT INTO Entry VALUES(0, '', CURRENT_TIMESTAMP, '$content', 'A_C', 0, 0, 0, '$user', '$catID')";
				
				mysqli_query( $db, $sql );
				$commentID = mysqli_insert_id( $db );
				
				$sql2 = "INSERT INTO has_parent VALUES($commentID, $postID)";
				mysqli_query( $db, $sql2 );
				
				$sql4 = "INSERT INTO Event VALUES(0, '$user', 'post_comment', CURRENT_TIMESTAMP)";
				mysqli_query( $db, $sql4 );
				
				$sql5 = "SELECT points from EventType where event_type='post_comment'";
				$sql6 = "SELECT rep from User where username='$user'";
				$points = mysqli_query( $db, $sql5 );
				$pointValue = mysqli_fetch_assoc( $points );
				$rep = mysqli_query( $db, $sql6 );
				$repValue = mysqli_fetch_assoc( $rep );
				
				$totalRep = $pointValue[ 'points' ] + $repValue[ 'rep' ];
				
				$sql7 = "UPDATE User SET rep=$totalRep WHERE username='$user'";
				mysqli_query( $db, $sql7 );
				
				header( "location: question.php?id=$curPage&comment=1" );
			}
		}
		else if( isset( $_POST[ 'logout' ] ) ) 
		{
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'control' ] ) ) 
		{
			header( "location: profile.php" );
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
	// If username or password is empty, give error message
	var answer = document.forms["NewAnswer"]["answerContent"].value;
	
	if( answer == null || answer == "" )
	{
		alert( "ERROR: You can't post an empty answer!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title><?php echo $title; ?></title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:15px;">
		<?php 
			// If user tried registering with an already existing username
			if( $_GET['err'] == 1 )
			{
				echo "<p><font color=red><b>ERROR: Can't edit a closed entry!</b></font></p>";
			}
		?>
		
		<p><form name="ControlPanel" method="post">
			<input type="submit" name="control" value="Profile" />
			<input type="submit" name="back" value="Back" />
			<input type="submit" name="logout" value="Logout" />
		</form></p>
		
		<hr style="background:black; border:0; height:3px" />
		
		<table>
		<form name="QuestionForm" method="post">
		<?php echo "<input type=\"hidden\" name=\"eID\" value=\"$curPage\" />\n"; ?>
		<tr>
		<td>
			<?php 
			echo "<b>$title</b> (tags:";
			$tags = mysqli_query( $db, "select tagName from entry_tag where entryID=$curPage" );
			mysqli_data_seek( $tags, 0 );
			while( $fetchTags = mysqli_fetch_assoc( $tags ) ) 
			{
				echo " ".$fetchTags['tagName'];
			}
			echo ")(category: $catValue)";
			?>
		</td>
		<td></td>
		</tr>
		<tr>
		<td rowspan=4><textarea rows=8 cols=100 readonly="readonly"><?php echo $text; ?></textarea></td>
		<td align="center">Votes</td>
		</tr>
		<tr>
		<td align="center"><input type="submit" name="plus" value="+" /> <?php echo $totalVotes; ?> <input type="submit" name="minus" value="-" /></td>
		</tr>
		<tr>
		<td align="center"></td>
		</tr>
		<tr>
		<td align="center">Posted: <?php echo $timestamp; ?></td>
		</tr>
		<tr>
		<td>
			<?php
			echo "<b>posted by:</b> ".$username; 
			
			// check if entry is edited
			$result = mysqli_query( $db, "select username, timestamp, description from edits ".
							"where entryID=$curPage order by timestamp desc limit 1" );
			if( mysqli_num_rows( $result ) !== 0 )
			{
				$row = mysqli_fetch_assoc( $result );
				echo ", <a href=\"question.php?id=$curPage\" title=\"".$row['description']."\"><b>edited by:</b> ".$row['username']." (".$row['timestamp'].")</a>";
			}
			
			// check if entry is closed
			if( $isClosed == 1 )
			{
				$result = mysqli_query( $db, "select username, timestamp, description from closed_by ".
								"where entryID=$curPage" );	
				if( mysqli_num_rows( $result ) !== 0 )
				{
					$row = mysqli_fetch_assoc( $result );
					echo ", <a href=\"question.php?id=$curPage\" title=\"".$row['description']."\"><b>closed by:</b> ".$row['username']." (".$row['timestamp'].")</a>";
				}
			}
			?>
		</td>
		<td>
			<?php
			// Check if user has permission to edit this entry
			$result1 = mysqli_query( $db, "call canEditEntry($curPage,'$user',@result);" );
			$result2 = mysqli_query( $db, "select @result as res;" );
			$canEdit = mysqli_fetch_assoc( $result2 );
			$canEdit = $canEdit['res'];
			
			if( $canEdit != 0 )
			{
				echo "<input type=\"submit\" style=\"width: 100%;\" name=\"edit\" value=\"Edit\" />\n";
			}
			?>
		</td>
		</tr>
		<tr><td colspan=2><?php 
			if( $isClosed == 0 )
			{
				echo "<input type=\"text\" size=100 maxlength=250 name=\"commentContent\" /> <input type=\"submit\" name=\"commentPost\" value=\"Comment\" />";
			}
		?></td></tr>
		</form>
		<?php
		// Get comments to the question
		$sql = "select entryID, timestamp, text, username ".
			   "from Entry, has_parent where entryType='Q_C' and entryID=childEntryID and parentEntryID=$curPage";
		$comments = mysqli_query( $db, $sql );
		while( $tuple2 = mysqli_fetch_assoc( $comments ) )
		{
			$c_id = $tuple2['entryID'];
			$c_text = $tuple2['text'];
			$c_username = $tuple2['username'];
			$c_timestamp = $tuple2['timestamp'];
			
			echo "<form method=\"post\">\n";
			echo "<input type=\"hidden\" name=\"eID\" value=\"$c_id\" />\n";
			echo "<tr><td colspan=2>\n";
			echo "<i>- ".$c_text."</i></td></tr>\n";
			
			echo "<tr><td>\n";
			echo "<b>commented by:</b> ".$c_username; 
			// check if entry is edited
			$result = mysqli_query( $db, "select username, timestamp, description from edits ".
							"where entryID=$c_id order by timestamp desc limit 1" );
			if( mysqli_num_rows( $result ) !== 0 )
			{
				$row = mysqli_fetch_assoc( $result );
				echo ", <a href=\"question.php?id=$curPage\" title=\"".$row['description']."\"><b>edited by:</b> ".$row['username']." (".$row['timestamp'].")</a>";
			}
			echo "</td><td>\n";
			
			// Check if user has permission to edit this entry
			$result1 = mysqli_query( $db, "call canEditEntry($c_id,'$user',@result);" );
			$result2 = mysqli_query( $db, "select @result as res;" );
			$canEdit = mysqli_fetch_assoc( $result2 );
			$canEdit = $canEdit['res'];
			
			if( $canEdit != 0 && $isClosed == 0  )
			{
				echo "<input type=\"submit\" style=\"width: 100%;\" name=\"edit\" value=\"Edit\" />\n";
			}
		
			echo "</td></tr></form>\n";
		}
		
		echo "</table>\n";
		?>
		
		<!-- displaying answers and comments -->
		<?php 
			// Get answers to the question
			$sql = "select entryID, timestamp, text, username, upvotes-downvotes as votes ".
				   "from Entry, has_parent where entryType='A' and entryID=childEntryID and parentEntryID=$curPage";
			$answers = mysqli_query( $db, $sql );
			
			while( $tuple = mysqli_fetch_assoc( $answers ) )
			{
				$a_id = $tuple['entryID'];
				$a_text = $tuple['text'];
				$a_username = $tuple['username'];
				$a_timestamp = $tuple['timestamp'];
				$a_totalVotes = $tuple['votes'];
				
				echo "<hr style=\"background:black; border:0; height:2px\" />\n";
				echo "<table><form method=\"post\">\n";
				echo "<input type=\"hidden\" name=\"eID\" value=\"$a_id\" />\n";
				echo "<tr><td rowspan=4>\n";
				echo "<textarea rows=4 cols=100 readonly=\"readonly\">$a_text</textarea></td>\n";
				echo "<td align=\"center\">Votes</td>\n";
				echo "</tr><tr>\n";
				echo "<td align=\"center\"><input type=\"submit\" name=\"plus\" value=\"+\" /> $a_totalVotes <input type=\"submit\" name=\"minus\" value=\"-\" /></td>\n";
				
				echo "</tr><tr><td align=\"center\">\n";
				$result = mysqli_query( $db, "select answerID from approves where answerID=$a_id" );
				if( mysqli_num_rows( $result ) != 0 )
				{
					// show approved answer text
					echo "<font color=green><b>approved answer</b></font>";
					
					if( $user == $username )
					{
						// show a button to disapprove answer
						echo "</br><input type=\"submit\" name=\"disapprove\" value=\"Disapprove\" />\n";
					}
				}
				else if( $user == $username && $user != $a_username )
				{
					// check if question already has an approved answer
					$result = mysqli_query( $db, "select entryID from approved_questions ".
									"where entryID=$curPage" );
					if( mysqli_num_rows( $result ) == 0 )
					{
						// show a button to approve answer
						echo "<input type=\"submit\" name=\"approve\" value=\"Approve this answer\" />\n";
					}
				}
				echo "</td></tr><tr>\n";
				
				echo "<td align=\"center\">Posted: $a_timestamp</td>\n";
				echo "</tr><tr><td>\n";
		
				echo "<b>answered by:</b> ".$a_username; 
				// check if entry is edited
				$result = mysqli_query( $db, "select username, timestamp, description from edits ".
								"where entryID=$a_id order by timestamp desc limit 1" );
				if( mysqli_num_rows( $result ) !== 0 )
				{
					$row = mysqli_fetch_assoc( $result );
					echo ", <a href=\"question.php?id=$curPage\" title=\"".$row['description']."\"><b>edited by:</b> ".$row['username']." (".$row['timestamp'].")</a>";
				}
				echo "</td><td>\n";
				
				// Check if user has permission to edit this entry
				$result1 = mysqli_query( $db, "call canEditEntry($a_id,'$user',@result);" );
				$result2 = mysqli_query( $db, "select @result as res;" );
				$canEdit = mysqli_fetch_assoc( $result2 );
				$canEdit = $canEdit['res'];
				
				if( $canEdit != 0 && $isClosed == 0  )
				{
					echo "<input type=\"submit\" style=\"width: 100%;\" name=\"edit\" value=\"Edit\" />\n";
				}
			
				echo "</td></tr>\n";
				if( $isClosed == 0  )
				{
					echo "<tr><td colspan=2><input type=\"text\" size=100 maxlength=250 name=\"commentContent\" /> <input type=\"submit\" name=\"commentPost\" value=\"Comment\" /></td></tr>";
				}
				echo "</form>\n";
				
				// Get comments to this answer
				$sql = "select entryID, timestamp, text, username ".
					   "from Entry, has_parent where entryType='A_C' and entryID=childEntryID and parentEntryID=$a_id";
				$comments = mysqli_query( $db, $sql );
				while( $tuple2 = mysqli_fetch_assoc( $comments ) )
				{
					$c_id = $tuple2['entryID'];
					$c_text = $tuple2['text'];
					$c_username = $tuple2['username'];
					$c_timestamp = $tuple2['timestamp'];
					
					echo "<form method=\"post\">\n";
					echo "<input type=\"hidden\" name=\"eID\" value=\"$c_id\" />\n";
					echo "<tr><td colspan=2>\n";
					echo "<i>- ".$c_text."</i></td></tr>\n";
					
					echo "<tr><td>\n";
					echo "<b>commented by:</b> ".$c_username; 
					// check if entry is edited
					$result = mysqli_query( $db, "select username, timestamp, description from edits ".
									"where entryID=$c_id order by timestamp desc limit 1" );
					if( mysqli_num_rows( $result ) !== 0 )
					{
						$row = mysqli_fetch_assoc( $result );
						echo ", <a href=\"question.php?id=$curPage\" title=\"".$row['description']."\"><b>edited by:</b> ".$row['username']." (".$row['timestamp'].")</a>";
					}
					echo "</td><td>\n";
					
					// Check if user has permission to edit this entry
					$result1 = mysqli_query( $db, "call canEditEntry($c_id,'$user',@result);" );
					$result2 = mysqli_query( $db, "select @result as res;" );
					$canEdit = mysqli_fetch_assoc( $result2 );
					$canEdit = $canEdit['res'];
					
					if( $canEdit != 0 && $isClosed == 0  )
					{
						echo "<input type=\"submit\" style=\"width: 100%;\" name=\"edit\" value=\"Edit\" />\n";
					}
				
					echo "</td></tr></form>\n";
				}
				
				echo "</table>\n";
			}
		?>
		
		<?php if( $isClosed == 0 ): ?>
			<!-- entry is not closed, posting answer is possible -->
			<hr style="background:black; border:0; height:3px" />
			<p><form name="NewAnswer" method="post">
			<textarea rows=5 cols=70 name="answerContent"></textarea></br>
			<input type="submit" onclick="return validateForm()" name="answerPost" value="Post Your Answer" />
			</form></p>
		<?php endif ?>
	</div>
	</body>
</html>