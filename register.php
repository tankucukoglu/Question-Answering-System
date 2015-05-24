<?php
	session_start();
	
	// If user is already logged in, redirect to welcome page
	if( isset( $_SESSION[ 'login_user' ] ) )
	{
		header( "location: welcome.php" );
	}
	
	// Connect to database
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'tan.kucukoglu', '**********','tan_kucukoglu' );
	
	// When a button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'post' ] ) ) 
		{
			// Get credentials
			$username = mysqli_real_escape_string( $db, $_POST[ 'username' ] );
			$password = mysqli_real_escape_string( $db, $_POST[ 'password' ] );
			$confirmPass = mysqli_real_escape_string( $db, $_POST[ 'confirmPass' ] );
			$email = mysqli_real_escape_string( $db, $_POST[ 'email' ] );
			
			// If none of them is empty
			if( strlen( $username ) > 0 && strlen( $password ) > 0 && strlen( $confirmPass ) > 0 && strlen( $email ) > 0 )
			{
				// Check if passwords match
				if( $password !== $confirmPass )
				{
					header( "location: register.php?err=2" );
				}
				else
				{
					// Check if user exists
					$sql = "SELECT username FROM User WHERE username='$username'";
					$result = mysqli_query( $db, $sql );
					$count = mysqli_num_rows( $result );

					if( mysqli_num_rows( $result ) == 1 )
					{
						// User does exist, give error
						header( "location: register.php?err=1" );
					}
					else
					{
						// User does not exist, register user
						$sql = "INSERT INTO User VALUES( '$username', '$password', 0, '$email', NULL )";
						$result = mysqli_query( $db, $sql );
						
						if( $result )
						{
							$sql2 = "CALL updateUserType( '$username' )";
							$result = mysqli_query( $db, $sql2 );
							
							$_SESSION['login_user'] = $username;
							header( "location: welcome.php" );
						}
						else
						{
							header( "location: register.php?err=3" );
						}
					}
				}
			}
		}
		else if( isset( $_POST[ 'back' ] ) ) 
		{
			header( "location: index.php" );
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateForm()
{
	// If username or password is empty, give error message
	var user = document.forms["Register"]["username"].value;
	var pass = document.forms["Register"]["password"].value;
	var confirmPass = document.forms["Register"]["confirmPass"].value;
	var email = document.forms["Register"]["email"].value;
	
	if( user == null || user == "" )
	{
		alert( "ERROR: Username can't be empty!" );
		return false;
	}
	
	if( pass == null || pass == "" || confirmPass == null || confirmPass == "" )
	{
		alert( "ERROR: Password can't be empty!" );
		return false;
	}
	
	if( email == null || email == "" )
	{
		alert( "ERROR: Email can't be empty!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Register</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<?php 
			// If user tried registering with an already existing username
			if( $_GET['err'] == 1 )
			{
				echo "<p><font color=red><b>ERROR: Username is already in use!</b></font></p>";
			}
			else if( $_GET['err'] == 2 )
			{
				echo "<p><font color=red><b>ERROR: Passwords do not match!</b></font></p>";
			}
			else if( $_GET['err'] == 3 )
			{
				echo "<p><font color=red><b>ERROR: Something went wrong, please try again!</b></font></p>";
			}
		?>
		
		<table>
		<form name="Register" method="post">
			<tr>
			<td>Select username: </td><td><input type="text" size=50 maxlength=50 name="username" /></td>
			</tr>
			<tr>
			<td>Select password: </td><td><input type="password" size=50 maxlength=50 name="password" /></td>
			</tr>
			<tr>
			<td>Confirm password: </td><td><input type="password" size=50 maxlength=50 name="confirmPass" /></td>
			</tr>
			<tr>
			<td>Enter e-mail: </td><td><input type="text" size=50 maxlength=50 name="email" /></td>
			</tr>
			<tr>
			<td align="center" colspan=2>
			<input type="submit" onclick="return validateForm()" name="post" value="Register" />
			<input type="submit" name="back" value="Back" />
			</td>
			</tr>
		</form>
		</table>
	</div>
	</body>
</html>