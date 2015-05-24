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
						  
	$sql1 = "select * from Permissions";
	$sql2 = "select * from UserType order by repThreshold desc";
	$sql3 = "select name, description, points from EventType order by points desc";
	$sql4 = "select * from user_permission";
	
	$permissions = mysqli_query( $db, $sql1 );
	$userType = mysqli_query( $db, $sql2 );
	$eventType = mysqli_query( $db, $sql3 );
	$userPermission = mysqli_query( $db, $sql4 );
	
	// A button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'logout' ] ) ) 
		{
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'back' ] ) ) 
		{
			header( "location: welcome.php" );
		}
		else if( isset( $_POST[ 'control' ] ) ) 
		{
			header( "location: profile.php" );
		}
	}	
?>

<html>
	<head>
		<title>Information Panel</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<form name="ButtonControl" method="post">
			<input type="submit" name="control" value="Profile"/>
			<input type="submit" name="back" value="Back"/>	
			<input type="submit" name="logout" value="Logout"/>
			<hr style="background:black; border:0; height:3px"/>
		</form>
		
		<p><font color=purple><b>- Permissions -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Permission Type</b></td>
		<td><b>Name</b></td><td><b>Description</b></td></tr>
			<?php
				// Print all user accounts
				mysqli_data_seek( $permissions, 0 );
				while( $tuple = mysqli_fetch_assoc( $permissions ) ) 
				{
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["permission_type"]."</td><td>".$tuple["name"]."</td>".
						 "<td>".$tuple["description"]."</td></tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- Existing User Types -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>User Type</b></td><td><b>Rep Threshold</b></td></tr>
			<?php
				mysqli_data_seek( $userType, 0 );
				while( $tuple = mysqli_fetch_assoc( $userType ) ) 
				{
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["userType"]."</td><td>".$tuple["repThreshold"]."</td></tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- Event Types -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Name</b></td><td><b>Description</b></td><td><b>Points</b></td></tr>
			<?php
				mysqli_data_seek( $eventType, 0 );
				while( $tuple = mysqli_fetch_assoc( $eventType ) ) 
				{					
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["name"]."</td><td>"
						 .$tuple["description"]."</td><td>".$tuple["points"]."</td></tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- User Permissions -</b></font>
		<table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>User Type</b></td><td><b>Permission Type</b></td></tr>
			<?php
				mysqli_data_seek( $userPermission, 0 );
				while( $tuple = mysqli_fetch_assoc( $userPermission ) ) 
				{					
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["userType"]."</td><td>".$tuple["permission_type"]."</td></tr>\n";
				}
			?>
		</table></p>
		
	</div>
	</body>
</html>