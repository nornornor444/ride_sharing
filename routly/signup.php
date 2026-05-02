<?php
// Start session and include your database connection
session_start();
require_once 'DBConnect.php'; // Make sure this matches your actual file name

$message = "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Grab the primary passenger form data
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $password   = $_POST['password'];
    $gender     = $_POST['gender'];

    // 2. Grab the emergency contact form data
    $e_first_name = $_POST['e_first_name'];
    $e_last_name  = $_POST['e_last_name'];
    $e_phone      = $_POST['e_phone'];

    // 3. Grab the preferences (Checkboxes only send data if checked, so we default to 0)
    $pref_chatty = isset($_POST['pref_chatty']) ? 1 : 0;
    $pref_silent = isset($_POST['pref_silent']) ? 1 : 0;
    $pref_music  = isset($_POST['pref_music']) ? 1 : 0;

    // 4. Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Prepare the SQL statement to insert a new passenger
    $sql = "INSERT INTO passenger_info (p_first_name, p_last_name, p_email, p_phone, p_password, p_gender, gender) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind the parameters (all strings: "sssssss")
        $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $hashed_password, $gender, $gender);

        // 6. Execute the query and handle potential duplicate errors
        try {
            if ($stmt->execute()) {
                // SUCCESS! Grab the brand new passenger_id:
                $new_passenger_id = $conn->insert_id;

                // 7. Insert the Emergency Contact
                $e_sql = "INSERT INTO passenger_emergency (passenger_id, e_phone, e_name_first, e_name_last) VALUES (?, ?, ?, ?)";
                $e_stmt = $conn->prepare($e_sql);
                if ($e_stmt) {
                    $e_stmt->bind_param("isss", $new_passenger_id, $e_phone, $e_first_name, $e_last_name);
                    $e_stmt->execute();
                    $e_stmt->close();
                }

                // 8. NEW: Insert the Ride Preferences
                $p_sql = "INSERT INTO passenger_preference (passenger_id, chatty_flag, silent_flag, music_flag) VALUES (?, ?, ?, ?)";
                $p_stmt = $conn->prepare($p_sql);
                if ($p_stmt) {
                    // "iiii" means: integer, integer, integer, integer
                    $p_stmt->bind_param("iiii", $new_passenger_id, $pref_chatty, $pref_silent, $pref_music);
                    $p_stmt->execute();
                    $p_stmt->close();
                }

                // Final Success message
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">Account created successfully! <a href="login.php" class="font-bold underline">Click here to Login</a>.</span>
                            </div>';
            }
        } catch (mysqli_sql_exception $e) {
            // MySQL error code 1062 means "Duplicate entry"
            if ($e->getCode() == 1062) {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">An account with that Email or Phone number already exists.</span>
                            </div>';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">Something went wrong. Please try again.</span>
                            </div>';
            }
        }
        $stmt->close();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Sign Up - Ride Sharing</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-10">

<div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
    <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">Create an Account</h2>

    <!-- Display Success or Error Messages -->
    <?php echo $message; ?>

    <form action="signup.php" method="POST">
        
        <!-- Section 1: Passenger Info -->
        <h3 class="text-xl font-bold mb-4 text-gray-700 border-b pb-2">Your Information</h3>

        <div class="flex gap-4 mb-4">
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">First Name</label>
                <input type="text" name="first_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Last Name</label>
                <input type="text" name="last_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Email Address</label>
            <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Your Phone Number</label>
            <input type="tel" name="phone" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Gender</label>
            <select name="gender" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Non-Binary">Non-Binary</option>
                <option value="Prefer not to say">Prefer not to say</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 font-semibold mb-1">Password</label>
            <input type="password" name="password" required minlength="6" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long.</p>
        </div>

        <!-- Section 2: Emergency Contact -->
        <h3 class="text-xl font-bold mt-8 mb-4 text-gray-700 border-b pb-2">Emergency Contact</h3>
        <p class="text-sm text-gray-500 mb-4">Who should we call if you feel unsafe during a ride?</p>

        <div class="flex gap-4 mb-4">
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">First Name</label>
                <input type="text" name="e_first_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Last Name</label>
                <input type="text" name="e_last_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-gray-700 font-semibold mb-1">Contact's Phone Number</label>
            <input type="tel" name="e_phone" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <!-- Section 3: Ride Preferences -->
        <h3 class="text-xl font-bold mt-8 mb-4 text-gray-700 border-b pb-2">Ride Preferences</h3>
        <p class="text-sm text-gray-500 mb-4">Let your drivers know what kind of ride you prefer.</p>

        <div class="flex flex-col gap-3 mb-8">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_chatty" value="1" class="w-5 h-5 text-black border-gray-300 rounded focus:ring-black">
                <span class="text-gray-700 font-medium">Chatty (I enjoy conversation)</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_silent" value="1" class="w-5 h-5 text-black border-gray-300 rounded focus:ring-black">
                <span class="text-gray-700 font-medium">Silent (I prefer a quiet ride)</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_music" value="1" class="w-5 h-5 text-black border-gray-300 rounded focus:ring-black">
                <span class="text-gray-700 font-medium">Music (I like listening to the radio/music)</span>
            </label>
        </div>

        <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition duration-200">
            Create Account
        </button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:underline font-semibold">Log in here</a>.</p>
    </div>

</div>

</body>
</html>