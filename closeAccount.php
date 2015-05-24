<?php
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: index.php" );
	}
	
	if( !isset( $_GET['id'] ) )
	{
		// If no parameter is given, redirect to welcome page
		header( "location: admin.php" );
	}
	
	$username = $_GET['id'];
	
	if( $username === 'admin' )
	{
		// Can't delete admin
		header( "location: admin.php" );
	}
	else
	{
		// Check if user is admin
		$sql = "select username from User where username='".$_SESSION[ 'login_user' ]."' and userType='Admin'";
		$result = mysqli_query( $db, $sql );
		
		if( mysqli_num_rows( $result ) === 0 )
		{
			// User is not admin
			header( "location: welcome.php" );
		}
		else
		{
			// Try to close the entered account
			$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
								  'tan.kucukoglu', '**********','tan_kucukoglu' );
								  
			$sql = "CALL closeAccount('$username')";
			mysqli_query( $db, $sql );
			
			// Return to admin panel
			header( "location: admin.php" );
		}
	}
?>

<html>
	<head>
		<title>Redirecting...</title>
	</head>
</html>