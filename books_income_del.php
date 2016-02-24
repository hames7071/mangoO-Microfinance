<?PHP
	require 'functions.php';
	check_logon();
	check_delete();
	connect();
	
	//Delete Entry from INCOMES
	if (isset($_GET['inc_id'])){
		
		// Sanitize input
		$inc_id = sanitize($_GET['inc_id']);
		
		// Get details about income to delete
		$sql_income = "SELECT inc_id, inctype_id, cust_id, loan_id, ltrans_id, sav_id FROM incomes WHERE inc_id = '$inc_id'";
		$query_income = mysql_query($sql_income);
		check_sql($query_income);
		$income = mysql_fetch_row($query_income);
		
		// Delete from SAVINGS where applicaple
		if($income[5] != NULL){
			$sql_delsav = "DELETE FROM savings WHERE sav_id = '$income[5]'";
			$query_delsav = mysql_query($sql_delsav);
			check_sql($query_delsav);
		}
		
		// If Loan Default Fine is deleted, flag loan transaction as not fined
		if ($income[1] == 5){
			$sql_revert_fined = "UPDATE ltrans SET ltrans_fined = 0 WHERE ltrans_id = '$income[4]'";
			$query_revert_fined = mysql_query($sql_revert_fined);
			check_sql($query_revert_fined);
		}
		
		// If Subscription Fee is deleted, revert date of last subscription by one year and five seconds
		if ($income[1] == 8){
			$sql_revert_subscr = "UPDATE customer SET cust_lastsub = (cust_lastsub - 31536005) WHERE cust_id = '$income[2]'";
			$query_revert_subscr = mysql_query($sql_revert_subscr);
			check_sql($query_revert_subscr);
		}
		
		// Delete income from INCOMES
		$sql_delinc = "DELETE FROM incomes WHERE inc_id = '$inc_id'";
		$query_delinc = mysql_query($sql_delinc);
		check_sql($query_delinc);
	}
	
	//Refer to books_income.php
	header('Location: books_income.php');
?>