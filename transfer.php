<?php
	// Suleyman Yasir KULA
	// 21200823
	session_start();
	
	// If user is not logged in, redirect to login page
	if( !isset( $_SESSION[ 'login_user' ] ) || !isset( $_SESSION[ 'cid' ] ) )
	{
		header( "location: index.php" );
	}
	
	$username = $_SESSION[ 'login_user' ];
	$cid = $_SESSION[ 'cid' ];
	
	// Get user's accounts
	$db = mysqli_connect( 'dijkstra.ug.bcc.bilkent.edu.tr',
						  'suleyman.kula', '0hofgbyw','suleyman_kula' );
	$sql1 = "SELECT aid, branch, balance, openDate ".
		   "FROM account NATURAL JOIN owns ".
		   "WHERE cid='$cid'";
	$ownedAccs = mysqli_query( $db, $sql1 );
	
	// If user does not own any accounts
	if( mysqli_num_rows( $ownedAccs ) == 0 )
	{
		// Return to welcome page with an error code
		header( "location: welcome.php?err=1" );
	}
	
	// Get other accounts
	$sql2 = "SELECT aid, branch ".
		   "FROM account NATURAL JOIN owns ".
		   "WHERE aid NOT IN(".
		   "SELECT aid ".
		   "FROM account NATURAL JOIN owns ".
		   "WHERE cid='$cid')";
	$otherAccs = mysqli_query( $db, $sql2 );
	
	// Get all distinct account IDs
	$sql3 = "SELECT DISTINCT aid ".
		   "FROM account NATURAL JOIN owns ";
	$allAccs = mysqli_query( $db, $sql3 );
	
	// Logout, Back or Transfer button is pressed
	if( $_SERVER["REQUEST_METHOD"] == "POST" )
	{
		if( isset( $_POST[ 'logout' ] ) ) 
		{
			// If Logout button is pressed
			// Logout and redirect to login page
			$_SESSION[ 'login_user' ] = null;
			$_SESSION[ 'cid' ] = null;
			header( "location: index.php" );
		}
		else if( isset( $_POST[ 'transfer' ] ) )
		{
			// If Transfer button is pressed
			// Fetch values from input fields
			$fromAcc = $_POST['fromAccount'];
			$toAcc = $_POST['toAccount'];
			$amount = +$_POST['amount'];
			
			// If entered amount is legit
			if( $amount > 0 )
			{
				// Get the balance of the selected fromAccount
				$sql = "SELECT balance FROM account WHERE aid='$fromAcc'";
				$result = mysqli_query( $db, $sql );
				$tuple = mysqli_fetch_assoc( $result );
				
				if( $fromAcc == $toAcc )
				{
					// If fromAccount and toAccount are the same
					// Reload page with an error code
					header( "location: transfer.php?err=2" );
				}
				else if( $tuple["balance"] < $amount )
				{
					// If there is not enough money in the selected account
					// Reload page with an error code
					header( "location: transfer.php?err=1" );
				}
				else
				{
					// Transfer money
					$sql = "UPDATE account ".
						   "SET balance=balance+$amount ".
						   "WHERE aid='$toAcc'";
					mysqli_query( $db, $sql );
					
					$sql = "UPDATE account ".
						   "SET balance=balance-$amount ".
						   "WHERE aid='$fromAcc'";
					mysqli_query( $db, $sql );
					
					// Reload page with a confirmation message
					header( "location: transfer.php?from=$fromAcc&to=$toAcc&amount=$amount" );
				}
			}
		}
		else
		{
			// If Back button is pressed
			// Redirect to welcome page
			header( "location: welcome.php" );
		}
	}
?>

<script type="text/javascript">
// Inspired from: 
// http://stackoverflow.com/questions/3937513/javascript-validation-for-empty-input-field
function validateAmount()
{
	// If amount is not legit, give error message
	var amount = document.forms["Transfer"]["amount"].value;
	
	if( amount == null || amount == "" || amount <= 0 || isNaN( amount ) )
	{
		alert( "ERROR: Amount must be greater than 0 TL!" );
		return false;
	}
	
	return true;
}
</script>

<html>
	<head>
		<title>Money Transfer</title>
	</head>
	
	<body bgcolor=#F9FDFF>
		<p><b><font color=green>Welcome <?php echo $username ?>!</font></b></p>
		<hr style="background:black; border:0; height:3px" />
		
		<?php
			if( isset( $_GET['from'] ) && isset( $_GET['to'] ) && isset( $_GET['amount'] ) )
			{
				// If user transferred money successfully in the previous step
				echo "<p><b>Transferred ".$_GET['amount']." from ".$_GET['from']." to ".$_GET['to']." successfully</b></p>";
			}
			else if( $_GET['err'] == 1 )
			{
				// If user tried to transfer more than available money in the previous step
				echo "<p><b>ERROR: There is not enough money in that account</b></p>";
			}
			else if( $_GET['err'] == 2 )
			{
				// If user tried to transfer money to same account in the previous step
				echo "<p><b>ERROR: Can't transfer money to same account</b></p>";
			}
		?>
		
		<p><font color=purple><b>- Your Account(s) -</b></font><table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Account ID</b></td><td><b>Branch</b></td>
		<td><b>Balance</b></td><td><b>Open Date</b></td></tr>
			<?php
				// Print the accounts of the user
				mysqli_data_seek( $ownedAccs, 0 );
				while( $tuple = mysqli_fetch_assoc( $ownedAccs ) ) 
				{
					echo "<tr bgcolor=#DCFFD8 align=center>".
						 "<td>".$tuple["aid"]."</td><td>".$tuple["branch"]."</td>".
						 "<td>".$tuple["balance"]." TL</td><td>".$tuple["openDate"]."</td>".
						 "</tr>\n";
				}
			?>
		</table></p>
		
		<p><font color=purple><b>- Other Account(s) -</b></font><table border=4 bgcolor=#75A557>
		<tr bgcolor=#EDD8FF align=center><td><b>Account ID</b></td><td><b>Branch</b></td></tr>
			<?php
				// Print other accounts
				if( mysqli_num_rows( $otherAccs ) > 0 ) 
				{
					while( $tuple = mysqli_fetch_assoc( $otherAccs ) ) 
					{
						echo "<tr bgcolor=#DCFFD8 align=center>".
							 "<td>".$tuple["aid"]."</td><td>".$tuple["branch"]."</td>".
							 "</tr>\n";
					}
				} 
				else 
				{
					echo "<tr><td colspan=2><b>There are no other accounts registered in the system.</b></td></tr>";
				}
			?>
		</table></p>
		
		<p><form name="Transfer" action="" method="post">
			Transfer from: 
			<select name="fromAccount">
			<?php
				// Create combo boxes for fromAccount
				mysqli_data_seek( $ownedAccs, 0 );
				while( $tuple = mysqli_fetch_assoc( $ownedAccs ) ) 
				{
					echo "<option value=\"".$tuple["aid"]."\">".$tuple["aid"]."</option>";
				}
			?>
			</select></br>
			
			Transfer to: 
			<select name="toAccount">
			<?php
				// Create combo boxes for toAccount
				while( $tuple = mysqli_fetch_assoc( $allAccs ) ) 
				{
					echo "<option value=\"".$tuple["aid"]."\">".$tuple["aid"]."</option>";
				}
			?>
			</select></br>
			
			Amount: <input type="text" size=50 value=0 name="amount" />
			<input type="submit" name="transfer" onclick="return validateAmount();" value="Transfer" />
		
			<hr style="background:black; border:0; height:3px" />
			<input type="submit" name="back" value="Back" />
			<input type="submit" name="logout" value="Logout" />
		</form></p>
	</body>
</html>