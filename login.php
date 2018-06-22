 <?php include('server.php') ?>
 <html lang="en">
   <head>
     <meta charset="utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
     <meta name="description" content="TurtleCoinWebWallet">
     <meta name="author" content="@crappyrules">
     <link rel="icon" href="favicon.ico">

     <title>Signin </title>

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

			<form id="loginForm" name="loginForm" method="post" action="login.php" class="form-signin">
				<h2 class="form-signin-heading">Please Sign in</h2>

		<?php include('errors.php'); ?>

		<label for="login" class="sr-only">Username</label>
      <input name="username" type="text" class="form-control" id="username" placeholder="Username" />
        <label for="inputPassword" class="sr-only">Password</label>
        <input name="password" type="password" class="form-control" id="password" placeholder="Password" />
        <input class="btn btn-lg btn-primary btn-block" type="submit" name="login_user" value="Login" />
				<p>
					<h6>Not yet a member? <a href="register.php">Sign up</a>
				</p>
      </form>
      </div>

</body>
</html>
