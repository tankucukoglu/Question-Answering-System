<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	// Connect to database
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
	
	$user = $_SESSION[ 'login_user' ];
	
	// Check if a username is entered as parameter
	if( isset( $_GET[ 'id' ] ) )
	{
		$username = $_GET[ 'id' ];
	}
	else
	{
		// show user's own profile
		header( "location: profile.php?id=$user" );
	}
	
	if( isset( $_GET[ 'id' ] ) )
	{
		// Check if username id is valid
		$sql = "select email, rep, userType from User where username ='$username'";
		$result = mysqli_query( $db, $sql );
		if( mysqli_num_rows( $result ) == 0 )
		{
			// username does not exist
			header( "location: welcome.php" );
		}
		
		if( $user == $username )
			$ownProfile = 1;
		else
			$ownProfile = 0;
						  
		$row = mysqli_fetch_assoc( $result );
		$email = $row['email'];
		$rep = $row['rep'];
		$userType = $row['userType'];
		
		$sql = "select count(*) as count from Entry where entryType='Q' and username='$username'";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$questionsTotal = $row['count'];
		
		$sql = "select count(*) as count from Entry where entryType='Q' and username='$username' ".
			   "and entryID not in(select entryID from unanswered_questions)";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$questionsAnswered = $row['count'];
		
		$sql = "select count(*) as count from Entry where entryType<>'Q' and username='$username'";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$answersTotal = $row['count'];
		
		$sql = "select count(*) as count from Entry where username='$username' ".
			   "and entryID in(select answerID from approves)";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$answersAccepted = $row['count'];
		
		$sql = "select count(*) as count from Badge, collects ".
			   "where username='$username' and badgeName=name and badgeType='Gold'";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$goldBadges = $row['count'];
		
		$sql = "select count(*) as count from Badge, collects ".
			   "where username='$username' and badgeName=name and badgeType='Silver'";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$silverBadges = $row['count'];
		
		$sql = "select count(*) as count from Badge, collects ".
			   "where username='$username' and badgeName=name and badgeType='Bronze'";
		$result = mysqli_query( $db, $sql );
		$row = mysqli_fetch_assoc( $result );
		$bronzeBadges = $row['count'];
		
		$totalBadges = $goldBadges + $silverBadges + $bronzeBadges;
		
		$sql = "select tagName from follows where username='$username'";
		$followingTags = mysqli_query( $db, $sql ); 
	}
	
	// A button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'logout' ] ) ) 
		{
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'myProfile' ] ) ) 
		{
			header( "location: profile.php?id=$user" );
		}
		else if( isset( $_POST[ 'back' ] ) ) 
		{
			header( "location: welcome.php" );
		}
		else if( isset( $_POST[ 'changeUsername' ] ) ) 
		{
			$userParam = mysqli_real_escape_string( $db, $_POST[ 'username' ] );
			
			if( strlen( $userParam ) <= 0 )
			{
				header( "location: profile.php?id=$username&err=1" );
			}
			else
			{
				// Check if username is available
				$sql = "select username from User where username ='$userParam'";
				$result = mysqli_query( $db, $sql );
				if( mysqli_num_rows( $result ) == 0 )
				{
					// username available
					mysqli_query( $db, "call changeUsername('$username','$userParam')" );
					$_SESSION[ 'login_user' ] = $userParam;
					header( "location: profile.php?id=$userParam" );
				}
				else
				{
					header( "location: profile.php?id=$username&err=2" );
				}
			}
		}
		else if( isset( $_POST[ 'changePassword' ] ) ) 
		{
			$password = mysqli_real_escape_string( $db, $_POST[ 'password' ] );
			
			if( strlen( $password ) <= 0 )
			{
				header( "location: profile.php?id=$username&err=1" );
			}
			else
			{
				mysqli_query( $db, "update User set password='$password' where username='$username'" );
				header( "location: profile.php?id=$username&success=1" );
			}
		}
		else if( isset( $_POST[ 'addTag' ] ) ) 
		{
			$tag = mysqli_real_escape_string( $db, $_POST[ 'tag' ] );
			
			if( strlen( $tag ) <= 0 )
			{
				header( "location: profile.php?id=$username&err=1" );
			}
			else
			{
				mysqli_query( $db, "insert into Tag values('$tag', NULL)" );
				mysqli_query( $db, "insert into follows values('$tag', '$username')" );
				header( "location: profile.php?id=$username&success=2" );
			}
		}
		else if( isset( $_POST[ 'removeTag' ] ) ) 
		{
			$tag = mysqli_real_escape_string( $db, $_POST[ 'tagRemove' ] );
			
			if( strlen( $tag ) <= 0 )
			{
				header( "location: profile.php?id=$username&err=1" );
			}
			else
			{
				mysqli_query( $db, "delete from follows where tagName='$tag' and username='$username'" );
				header( "location: profile.php?id=$username&success=2" );
			}
		}
	}	
?>

<html>
	<head>
		<title>Profile</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:15px;">
	<?php 
		if( $_GET['err'] == 1 )
		{
			echo "<p><font color=red><b>ERROR: Required field can't be blank!</b></font></p>";
		}
		else if( $_GET['err'] == 2 )
		{
			echo "<p><font color=red><b>ERROR: That username is already taken!</b></font></p>";
		}
		else if( $_GET['success'] == 1 )
		{
			echo "<p><font color=green><b>Password is changed successfully!</b></font></p>";
		}
	?>
	
	<p><form name="ControlPanel" method="post">
		<?php if( $ownProfile == 0 ): ?>
		<input type="submit" name="myProfile" value="My Profile" />
		<?php endif ?>
		<input type="submit" name="back" value="Back" />
		<input type="submit" name="logout" value="Logout" />
	</form></p>
	
	<hr style="background:black; border:0; height:3px" />
	
	<table>
		<form name="Profile" method="post">
		<tr><td>Username: </td><td><?php
			if( $ownProfile == 1 )
			{
				echo "<input type=\"text\" size=50 maxlength=50 name=\"username\" value=\"$username\" />";
				echo "<input type=\"submit\" name=\"changeUsername\" value=\"Change\" />";
				echo "</td></tr><tr><td>\n";
				echo "Password: </td><td>";
				echo "<input type=\"password\" size=50 maxlength=50 name=\"password\" />";
				echo "<input type=\"submit\" name=\"changePassword\" value=\"Change\" />";
			}
			else
			{
				echo $username." ";
			}
		?></td></tr>
		<tr><td>Email: </td><td><?php echo $email; ?></td></tr>
		<tr><td>Rep: </td><td><?php echo $rep; ?></td></tr>
		<tr><td>Total Questions: </td><td><?php echo $questionsTotal; ?></td></tr>
		<tr><td>Answered Questions: </td><td><?php echo $questionsAnswered; ?></td></tr>
		<tr><td>Total Answers: </td><td><?php echo $answersTotal; ?></td></tr>
		<tr><td>Accepted Answers: </td><td><?php echo $answersAccepted; ?></td></tr>
		<tr><td>Total Badges: </td><td><?php echo $totalBadges; ?></td></tr>
		<tr><td>Gold Badges: </td><td><?php echo $goldBadges; ?></td></tr>
		<tr><td>Silver Badges: </td><td><?php echo $silverBadges; ?></td></tr>
		<tr><td>Bronze Badges: </td><td><?php echo $bronzeBadges; ?></td></tr>
		<tr><td>Following Tags: </td>
		<td><?php 
			while( $fetchTags = mysqli_fetch_assoc( $followingTags ) ) 
			{
				echo $fetchTags['tagName']." ";
			}
			
			if( $ownProfile == 1 )
			{
				echo "<input type=\"text\" size=15 maxlength=50 name=\"tag\" />";
				echo "<input type=\"submit\" name=\"addTag\" value=\"Add\" />";
				echo "<input type=\"text\" size=15 maxlength=50 name=\"tagRemove\" />";
				echo "<input type=\"submit\" name=\"removeTag\" value=\"Remove\" />";
			}
		?></td></tr>
		</form>
	</table>
	
	</div>
	</body>
	</html>
