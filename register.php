<?php include('server.php') ?>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="TurtleCoinWebWallet">
		<meta name="author" content="@crappyrules">
		<link rel="icon" href="favicon.ico">

		<title>Register </title>

		<!-- Bootstrap core CSS -->
		<link href="css/bootstrap.min.css" rel="stylesheet">

		<!-- Custom styles for this template -->
		<link href="css/signin.css" rel="stylesheet">
	</head>
	<body>
 	<div class="container">
 	<p>
 		<center>
 		<img src="logo.png" alt="TurtleCoin">
 	</center>
 	</p>
 	</div>

 	<div class="container">

	<!--form method="post" action="register.php">
	value="<?php echo $username; ?>">
	value="<?php echo $email; ?>">-->
		<form id="loginForm" name="loginForm" method="post" action="register.php" class="form-signin">
			<h2 class="form-signin-heading">Register Here</h2>

		<?php include('errors.php'); ?>

		<label for="username" class="sr-only">Username</label>
			<input type="text" class="form-control" name="username" placeholder="Username" value="<?php echo $username; ?>">

		<label for="email" class="sr-only">Email</label>
				<input type="email" class="form-control" name="email" placeholder="Email" value="<?php echo $email; ?>">

		<label for="password_1" class="sr-only">Password</label>
				<input type="password" class="form-control" name="password_1" placeholder="Password">

		<label for="password_2" class="sr-only">Confirm Password</label>
				<input type="password" class="form-control" name="password_2" placeholder="Confirm Password">

			<input class="btn btn-lg btn-primary btn-block" type="submit" name="reg_user" value="Register" />
		<p>
			Already a member? <a href="login.php">Sign in</a>
		</p>
		</div>
	</form>
</body>
</html>
