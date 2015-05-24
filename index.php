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
	
	// When Login button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		// Get username and password
		$username = mysqli_real_escape_string( $db, $_POST[ 'username' ] );
		$password = mysqli_real_escape_string( $db, $_POST[ 'password' ] );
		
		// If none of them is empty
		if( strlen( $username ) > 0 && strlen( $password ) > 0 )
		{
			// Check if user exists
			$sql = "SELECT username FROM User WHERE username='$username' and password='$password'";
			$result = mysqli_query( $db, $sql );
			$count = mysqli_num_rows( $result );

			if( mysqli_num_rows( $result ) == 1 )
			{
				// User does exist, login
				$_SESSION['login_user'] = $username;
				header( "location: welcome.php" );
			}
			else
			{
				// User does not exist, reload page
				header( "location: index.php?err=1" );
			}
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateForm()
{
	// If username or password is empty, give error message
	var user = document.forms["Login"]["username"].value;
	var pass = document.forms["Login"]["password"].value;
	
	if( user == null || user == "" )
	{
		alert( "ERROR: Username can't be empty!" );
		return false;
	}
	
	if( pass == null || pass == "" )
	{
		alert( "ERROR: Password can't be empty!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Login</title>
	</head>
	
	<body background="images/back.jpg">
	<div align="center" style="padding-top:25px;">
		<?php 
			// If user tried logging in with wrong username or password before
			if( $_GET['err'] == 1 )
			{
				echo "<p><font color=red><b>ERROR: Username or password is invalid!</b></font></p>";
			}
		?>
		
		<table>
		<form name="Login" onsubmit="return validateForm()" action="" method="post">
			<tr>
			<td>Username: </td><td><input type="text" size=50 maxlength=50 name="username" /></td>
			</tr>
			<tr>
			<td>Password: </td><td><input type="password" size=50 maxlength=50 name="password" /></td>
			</tr>
			<tr>
			<td align="center" colspan=2>
			<input type="submit" value="Login" />
			</td>
			</tr>
		</form>
		</table>
		
		<p>
		<a href="register.php">Create new account</a></br>
		<a href="forgotPass.php">Forgot password?</a>
		</p>
	</div>
	</body>
</html>