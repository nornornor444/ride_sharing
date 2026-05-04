<?php
session_start();
require_once('DBConnect.php'); 

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];


    $sql = "SELECT passenger_id, p_first_name, p_password FROM passenger_info WHERE p_email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            
            if (password_verify($password, $row['p_password'])) {
                
                
                $_SESSION['passenger_id'] = $row['passenger_id'];
                $_SESSION['p_first_name'] = $row['p_first_name'];
                
            
                header("Location: home.php");
                exit();
            } else {
                // Password was wrong
                $error_message = "Invalid email or password.";
            }
        } else {
            // Email was not found
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error_message = "Database connection error. Please try again.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Login - Ride Sharing</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">Welcome Back</h2>

    <?php if(!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Email Address</label>
            <input type="email" name="email" required placeholder="Enter your email" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 font-semibold mb-2">Password</label>
            <input type="password" name="password" required placeholder="Enter your password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition duration-200">
            Log In
        </button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-gray-600">Don't have an account yet? <a href="signup.php" class="text-blue-600 hover:underline font-semibold">Sign up here</a>.</p>
    </div>

</div>

</body>
</html>
