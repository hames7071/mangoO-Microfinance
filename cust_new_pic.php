<!DOCTYPE HTML>
<?PHP
	require 'functions.php';
	check_logon();
	connect();
	
	//Check where Re-direct comes from
	if(isset($_GET['from'])){
		$from = sanitize($_GET['from']);
	}
	
	//SKIP-Button
	if (isset($_POST['skip'])){
		if ($from == "new") header('Location: acc_share_buy.php?cust='.$_SESSION['cust_id'].'&rec='.$_SESSION['receipt_no']);
		else header('Location: customer.php?cust='.$_SESSION['cust_id']);
	}
	
	//UPLOAD-Button
	if (isset($_POST['upload']) AND isset($_FILES['image'])){
		//Settings
		$max_file_size = 1024*2048; // 2048kb
		$valid_exts = array('jpeg', 'jpg', 'png', 'tif', 'tiff');
		
		//Thumbnail Sizes
		$sizes = array(100 => 130, 146 => 190, 230 => 300);

		//Check for maximum file size
		if( $_FILES['image']['size'] < $max_file_size ){			
			// Get file extension
			$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			
			if (in_array($ext, $valid_exts)) {
				//Resize image
				foreach ($sizes as $width => $height) {
					$files[] = resize_img($width, $height);
				}
				$sql_picpath = "UPDATE customer SET cust_pic = '$files[1]' WHERE cust_id = '$_SESSION[cust_id]'";
				$query_picpath = mysql_query($sql_picpath);
				check_sql($query_picpath);
				
				if ($from == "new")	header('Location: acc_share_buy.php?cust='.$_SESSION['cust_id'].'&rec='.$_SESSION['receipt_no']);
				else header('Location:customer.php?cust='.$_SESSION['cust_id']);
			}
			else $error_msg = 'Unsupported file';
		}
		else $error_msg = 'Please choose an image smaller than 2048kB.';
	}
?>

<html>
	<?PHP include_Head('New Picture Upload',1) ?>
	
	<body>
		<!-- MENU -->
		<?PHP include_Menu(2); ?>
		<div id="menu_main">
			<a href="cust_search.php">Search</a>
			<a href="cust_new.php" id="item_selected">New Customer</a>
			<a href="cust_act.php">Active Customers</a>
			<a href="cust_inact.php">Inactive Customers</a>
		</div>
			
		<div class="content_center">
			<p class="heading">Upload Photo for Customer <?PHP echo $_SESSION['cust_id']?></p>
			
			<?php if(isset($error_msg)): ?>
			<p class="alert"><?php echo $error_msg; ?></p>
			<?php endif ?>
			
			<!-- File uploading form -->
			<form action="" method="post" enctype="multipart/form-data">
				
				<label for="image" class="file-upload">
					<i class="fa fa-image"></i> Choose image
				</label>
				<input type="file" name="image" id="image" accept="image/*" />
				<br/>
				<input type="submit" name="upload" value="Upload" />
				<input type="submit" name="skip" value="Skip" />
			</form>
		</div>
	</body>
</html>