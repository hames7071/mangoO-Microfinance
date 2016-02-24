<!DOCTYPE HTML>
<?PHP
	require 'functions.php';
	check_logon();
	connect();
	$lastyear = date("Y", time())-1;
	
/** 
	* DISTRIBUTE ANNUAL SAVINGS INTEREST
	*/
	if(isset($_POST['int_distribute'])){
		
		//Sanitize user input
		$int_rate = sanitize($_POST['int_rate']);
		$int_year = sanitize($_POST['int_year']);
		$timestamp = time();
		
		//Calculate UNIX TIMESTAMP for first and last day of selected year
		$int_year_beg = mktime(0, 0, 0, 1, 1, $int_year);
		$int_year_end = mktime(0, 0, 0, 1, 0, ($int_year+1));
		
		//Get all active customers in array
		$query_cust = get_custact();
		$cust = array();
		while($row_cust = mysql_fetch_assoc($query_cust)){
			$cust[] = $row_cust;
		}
		
		//Get all savings in array
		$sql_sav = "SELECT * FROM savings WHERE cust_id IN (SELECT cust_id FROM customer WHERE cust_active = 1) AND sav_date < $int_year_end";
		$query_sav = mysql_query($sql_sav);
		check_sql($query_sav);
		$savings = array();
		while ($row_sav = mysql_fetch_assoc($query_sav)){
			$savings[] = $row_sav;
		}
		
		// Compute Savings Factor
		$int_base = 0;
		$int_fact = 0;
		$int_total = 0;
		
		foreach ($cust as $c){
			foreach ($savings as $s){
				if ($s['cust_id'] == $c['cust_id']){
					
					if ($s['sav_date'] < $int_year_beg)
						$int_base = $int_base + $s['sav_amount'];
					
					else {
						if ($s['sav_amount'] > 0)
							$int_base = $int_base + $s['sav_amount'] * round((($int_year_end - $s['sav_date'])/86400)/365,2);
						elseif ($s['sav_amount'] <= 0)
							$int_base = $int_base + $s['sav_amount'] * (1 - round((($s['sav_date'] - $int_year_beg)/86400)/365,2));
					}
				}
			}
			
			// Calculate dividend for current customer
			$int_cust = round($int_base /100 * $int_rate,0);
		
			// Insert interest in SAVINGS
			$sql_cust_int = "INSERT INTO savings (cust_id, sav_date, sav_amount, savtype_id, sav_created, user_id) VALUES ($c[cust_id], $int_year_end, $int_cust, 3, $timestamp, $_SESSION[log_id])";
			$query_cust_int = mysql_query($sql_cust_int);
			check_sql($query_cust_int);
						
			$int_total = $int_total + $int_cust;
			$int_base = 0;
		}
		// Insert grand total distributed interest into expenses
		$sql_int_exp = "INSERT INTO expenses (exptype_id, exp_amount, exp_date, exp_text, exp_created, user_id) VALUES (19, $int_total, $int_year_end, 'Distributed Interest for $int_year', $timestamp, $_SESSION[log_id])";
		$query_int_exp = mysql_query($sql_int_exp);
		check_sql($query_int_exp );
	}
	
/**
	* DISTRIBUTE ANNUAL DIVIDEND 
	*/	
	if(isset($_POST['div_distribute'])){
		
		//Sanitize user input
		$div_value = sanitize($_POST['div_value']);
		$div_type = sanitize($_POST['div_type']);
		$div_year = sanitize($_POST['div_year']);
		$timestamp = time();
		
		//Calculate UNIX TIMESTAMP for first and last day of selected year
		$div_year_beg = mktime(0, 0, 0, 1, 1, $div_year);
		$div_year_end = mktime(0, 0, 0, 1, 0, ($div_year+1));
		
		//Get all active customers in array
		$query_cust = get_custact();
		$cust = array();
		while($row_cust = mysql_fetch_assoc($query_cust)){
			$cust[] = $row_cust;
		}
		
		//Get all shares in array
		$sql_sh = "SELECT * FROM shares WHERE cust_id IN (SELECT cust_id FROM customer WHERE cust_active = 1) AND share_date < $div_year_end";
		$query_sh = mysql_query($sql_sh);
		check_sql($query_sh);
		$shares = array();
		$share_count = 0;
		while ($row_sh = mysql_fetch_assoc($query_sh)){
			$shares[] = $row_sh;
			$share_count = $share_count + $row_sh['share_amount'];
		}
		
		//If entered dividend value is grand total, divide it amount by the number of eligble shares
		if($div_type == 2) $div_value = ceil($div_value / $share_count);
		
		// Compute Share Factor
		$div_fact = 0;
		$div_total = 0;
		foreach ($cust as $c){
			foreach ($shares as $s){
				if ($s['cust_id'] == $c['cust_id']){
					if ($s['share_date'] < $div_year_beg)
						$div_fact = $div_fact + $s['share_amount'];
					else {
						if ($s['share_amount'] > 0)
							$div_fact = $div_fact + $s['share_amount'] * round(ceil(($div_year_end - $s['share_date'])/86400)/365,2);
						elseif ($s['share_amount'] <= 0)
							$div_fact = $div_fact + $s['share_amount'] * (1 - round(ceil(($s['share_date'] - $div_year_beg)/86400)/365,2));
					}
				}
			}
			
			// Calculate dividend for current customer
			$div_cust = round($div_fact * $div_value,0);
			
			// Insert dividend in SAVINGS
			$sql_cust_div = "INSERT INTO savings (cust_id, sav_date, sav_amount, savtype_id, sav_created, user_id) VALUES ($c[cust_id], $div_year_end, $div_cust, 9, $timestamp, $_SESSION[log_id])";
			$query_cust_div = mysql_query($sql_cust_div);
			check_sql($query_cust_div);
						
			$div_total = $div_total + $div_cust;
			$div_fact=0;
		}
		
		// Insert grand total distributed dividend into expenses
		$sql_div_exp = "INSERT INTO expenses (exptype_id, exp_amount, exp_date, exp_text, exp_created, user_id) VALUES (18, $div_total, $div_year_end, 'Distributed Dividend for $div_year', $timestamp, $_SESSION[log_id])";
		$query_div_exp = mysql_query($sql_div_exp);
		check_sql($query_div_exp );
	}
?>


<html>
	<?PHP include_Head('Dividend',1) ?>
	<body>
	
		<!-- MENU -->
		<?PHP include_Menu(4);	?>
		<div id="menu_main">
			<a href="start.php">Back</a>
			<a href="books_expense.php">Expenses</a>
			<a href="books_income.php">Incomes</a>
			<a href="books_annual.php" id="item_selected">Annual Accounts</a>
		</div>
		
		<div class="content_center" style="width:60%;">
		
			<div class="content_left" style="width:50%;">
					<p class="heading">Annual Share Dividend</p>
					<form action="books_annual.php" method="post">
						<input type="number" name="div_year" min="2000" max="<?PHP echo $lastyear; ?>" placeholder="Enter Year" value="<?PHP echo $lastyear; ?>" required="required" />
						<br/><br/>
						<select name="div_type">
							<option value="1">Dividend per share</option>
							<option value="2">Grand Total Dividend</option>
						</select>
						<br/><br/>
						<input type="number" name="div_value" min=".1" step="any" placeholder="<?PHP echo $_SESSION['set_cur']; ?>" required="required" />
						<br/><br/>
						<input type="submit" name="div_distribute" value="Distribute Dividend" />
					</form>			
			</div>
			
			<div class="content_right" style="width:50%;">
				<p class="heading">Annaul Savings Interest</p>
				<form action="books_annual.php" method="post">
					<input type="number" name="int_year" min="2000" max="<?PHP echo $lastyear; ?>" placeholder="Enter Year" value="<?PHP echo $lastyear; ?>" required="required" />
					<br/><br/>
					<input type="number" name="int_rate" min=".1" step=".1" placeholder="Interest Rate (%)" required="required" />
					<br/><br/>
					<input type="submit" name="int_distribute" value="Distribute Interest" />
				</form>
			</div>
			
		</div>
	</body>
</html>