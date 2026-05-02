<?php
session_start();
require_once 'DBConnect.php';

// Kick out users who aren't logged in
if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit();
}

$passenger_id = $_SESSION['passenger_id'];
$message = "";

// ==========================================
// 1. HANDLE DELETE REQUEST
// ==========================================
if (isset($_POST['delete_profile']) && $_POST['delete_profile'] == '1') {
    try {
        // Delete from all related tables
        $delete_queries = [
            "DELETE FROM passenger_preference WHERE passenger_id = ?",
            "DELETE FROM passenger_emergency WHERE passenger_id = ?",
            "DELETE FROM passenger_info WHERE passenger_id = ?"
        ];
        
        foreach ($delete_queries as $query) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $passenger_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Log out the user
        session_destroy();
        header("Location: login.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">Error deleting profile. Please try again.</div>';
    }
}

// ==========================================
// 2. HANDLE FORM SUBMISSION (UPDATE DB)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_profile'])) {
    // Grab basic info
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $gender     = $_POST['gender'];

    // Grab emergency info
    $e_first_name = $_POST['e_first_name'];
    $e_last_name  = $_POST['e_last_name'];
    $e_phone      = $_POST['e_phone'];

    // Grab preferences
    $pref_chatty = isset($_POST['pref_chatty']) ? 1 : 0;
    $pref_silent = isset($_POST['pref_silent']) ? 1 : 0;
    $pref_music  = isset($_POST['pref_music']) ? 1 : 0;

    try {
        // Update passenger_info
        $sql1 = "UPDATE passenger_info SET p_first_name=?, p_last_name=?, p_email=?, p_phone=?, p_gender=?, gender=? WHERE passenger_id=?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $gender, $gender, $passenger_id);
        $stmt1->execute();
        $stmt1->close();

        // Update session name just in case they changed it
        $_SESSION['p_first_name'] = $first_name;

        // Update or Insert passenger_emergency (Handles older accounts smoothly)
        $sql2 = "INSERT INTO passenger_emergency (passenger_id, e_name_first, e_name_last, e_phone) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE e_name_first=VALUES(e_name_first), e_name_last=VALUES(e_name_last), e_phone=VALUES(e_phone)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("isss", $passenger_id, $e_first_name, $e_last_name, $e_phone);
        $stmt2->execute();
        $stmt2->close();

        // Update or Insert passenger_preference
        $sql3 = "INSERT INTO passenger_preference (passenger_id, chatty_flag, silent_flag, music_flag) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE chatty_flag=VALUES(chatty_flag), silent_flag=VALUES(silent_flag), music_flag=VALUES(music_flag)";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("iiii", $passenger_id, $pref_chatty, $pref_silent, $pref_music);
        $stmt3->execute();
        $stmt3->close();

        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">Profile updated successfully!</div>';

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">That email or phone is already in use by another account.</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">Error updating profile.</div>';
        }
    }
}

// ==========================================
// 3. FETCH CURRENT DATA (TO PRE-FILL FORM)
// ==========================================
$sql = "SELECT p.*, e.e_name_first, e.e_name_last, e.e_phone, pr.chatty_flag, pr.silent_flag, pr.music_flag 
        FROM passenger_info p 
        LEFT JOIN passenger_emergency e ON p.passenger_id = e.passenger_id 
        LEFT JOIN passenger_preference pr ON p.passenger_id = pr.passenger_id 
        WHERE p.passenger_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Safe variable assignment for the HTML form
$u_first   = htmlspecialchars($user_data['p_first_name'] ?? '');
$u_last    = htmlspecialchars($user_data['p_last_name'] ?? '');
$u_email   = htmlspecialchars($user_data['p_email'] ?? '');
$u_phone   = htmlspecialchars($user_data['p_phone'] ?? '');
$u_gender  = $user_data['p_gender'] ?? '';

$e_first   = htmlspecialchars($user_data['e_name_first'] ?? '');
$e_last    = htmlspecialchars($user_data['e_name_last'] ?? '');
$e_phone   = htmlspecialchars($user_data['e_phone'] ?? '');

$p_chatty  = isset($user_data['chatty_flag']) && $user_data['chatty_flag'] == 1;
$p_silent  = isset($user_data['silent_flag']) && $user_data['silent_flag'] == 1;
$p_music   = isset($user_data['music_flag']) && $user_data['music_flag'] == 1;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Edit Profile - Routly</title>
</head>
<body class="bg-gray-100 min-h-screen py-10">

<div class="max-w-2xl mx-auto bg-white p-8 rounded shadow-md">
    
    <!-- Top Navigation -->
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-3xl font-bold text-gray-800">Edit Profile</h2>
        <a href="home.php" class="text-blue-600 hover:underline font-semibold">&larr; Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <form action="edit_profile.php" method="POST">
        
        <!-- Basic Info -->
        <h3 class="text-xl font-bold mb-4 text-gray-700">Your Information</h3>
        <div class="flex gap-4 mb-4">
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">First Name</label>
                <input type="text" name="first_name" value="<?php echo $u_first; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?php echo $u_last; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Email Address</label>
            <input type="email" name="email" value="<?php echo $u_email; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-1">Phone Number</label>
            <input type="tel" name="phone" value="<?php echo $u_phone; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <div class="mb-8">
            <label class="block text-gray-700 font-semibold mb-1">Gender</label>
            <select name="gender" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
                <option value="Male" <?php echo ($u_gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($u_gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Non-Binary" <?php echo ($u_gender == 'Non-Binary') ? 'selected' : ''; ?>>Non-Binary</option>
                <option value="Prefer not to say" <?php echo ($u_gender == 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
            </select>
        </div>

        <!-- Emergency Contact -->
        <h3 class="text-xl font-bold mt-8 mb-4 text-gray-700 border-t pt-6">Emergency Contact</h3>
        <div class="flex gap-4 mb-4">
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">First Name</label>
                <input type="text" name="e_first_name" value="<?php echo $e_first; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
            <div class="w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Last Name</label>
                <input type="text" name="e_last_name" value="<?php echo $e_last; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-gray-700 font-semibold mb-1">Contact's Phone</label>
            <input type="tel" name="e_phone" value="<?php echo $e_phone; ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
        </div>

        <!-- Ride Preferences -->
        <h3 class="text-xl font-bold mt-8 mb-4 text-gray-700 border-t pt-6">Ride Preferences</h3>
        <div class="flex flex-col gap-3 mb-8">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_chatty" value="1" <?php echo $p_chatty ? 'checked' : ''; ?> class="w-5 h-5 text-black border-gray-300 rounded">
                <span class="text-gray-700 font-medium">Chatty (I enjoy conversation)</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_silent" value="1" <?php echo $p_silent ? 'checked' : ''; ?> class="w-5 h-5 text-black border-gray-300 rounded">
                <span class="text-gray-700 font-medium">Silent (I prefer a quiet ride)</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="pref_music" value="1" <?php echo $p_music ? 'checked' : ''; ?> class="w-5 h-5 text-black border-gray-300 rounded">
                <span class="text-gray-700 font-medium">Music (I like listening to the radio)</span>
            </label>
        </div>

        <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition">
            Save Changes
        </button>
    </form>

    <!-- Delete Profile Section -->
    <div class="mt-12 pt-8 border-t border-gray-300">
        <h3 class="text-xl font-bold mb-4 text-gray-700">Danger Zone</h3>
        <p class="text-gray-600 mb-4">Permanently delete your profile and all associated data. This action cannot be undone.</p>
        <form action="edit_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your profile? This cannot be undone.\n\nAll your bookings, preferences, and emergency contact information will be permanently deleted.')">
            <input type="hidden" name="delete_profile" value="1">
            <button type="submit" class="w-full bg-red-600 text-white py-3 rounded font-bold text-lg hover:bg-red-700 transition">
                🗑️ Delete My Profile
            </button>
        </form>
    </div>

</div>
</body>
</html>