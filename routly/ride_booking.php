<?php
session_start();
require_once('DBConnect.php');

if(!isset($_SESSION['passenger_id'])){
    header("Location: login.php");
    exit();
}

$passenger_id = $_SESSION['passenger_id'];
$step = isset($_POST['step']) ? $_POST['step'] : '1';

// --- NEW: Vibe Check Logic ---
// Fetch the current  preferences
$p_pref_sql = "SELECT chatty_flag, silent_flag, music_flag FROM passenger_preference WHERE passenger_id = '$passenger_id'";
$p_pref_result = mysqli_query($conn, $p_pref_sql);
$p_prefs = mysqli_fetch_assoc($p_pref_result);


if (!$p_prefs) {
    $p_prefs = ['chatty_flag' => 0, 'silent_flag' => 0, 'music_flag' => 0];
}

//check if women preference is selected
$form_women_pref = isset($_POST['form_women_driver_pref']) ? 1 : 0;
$use_women_filter = ($step == '1' && $form_women_pref) ? 1 : 0;

//driver preference is fetched along with women driver's preference is selected or not

$gender_filter = ($use_women_filter == 1) ? " AND d.gender = 'Female'" : "";
$drivers_sql = "SELECT d.driver_id, d.d_first_name, d.c_type, d.gender,
                       dp.chatty_flag, dp.silent_flag, dp.music_flag 
                FROM driver_info d 
                LEFT JOIN driver_preference dp ON d.driver_id = dp.driver_id
                WHERE 1=1 $gender_filter";
$drivers_result = mysqli_query($conn, $drivers_sql);

$drivers_with_vibes = [];
while($driver = mysqli_fetch_assoc($drivers_result)){
    
    $match_score = 0;
    $total_prefs = 3;
    
    // bull value is converted to 0
    $d_chatty = $driver['chatty_flag'] ?? 0;
    $d_silent = $driver['silent_flag'] ?? 0;
    $d_music  = $driver['music_flag'] ?? 0;

    if ($d_chatty == $p_prefs['chatty_flag']) $match_score++;
    if ($d_silent == $p_prefs['silent_flag']) $match_score++;
    if ($d_music == $p_prefs['music_flag']) $match_score++;
    
    $driver['match_pct'] = round(($match_score / $total_prefs) * 100);

    
    $vibes = [];
    if ($d_chatty) $vibes[] = "🗣️ Chatty";
    if ($d_silent) $vibes[] = "🤫 Quiet";
    if ($d_music)  $vibes[] = "🎵 Music";
    
    $driver['vibe_string'] = !empty($vibes) ? implode(" | ", $vibes) : "Neutral Vibe";
    
    $drivers_with_vibes[] = $driver;
}


usort($drivers_with_vibes, function($a, $b) {
    return $b['match_pct'] <=> $a['match_pct'];
});


// I fethced the routes along with the prices
$routes_sql = "SELECT route_id, r_start, r_end, route_price FROM route WHERE no_go_flag = 0";
$routes_result = mysqli_query($conn, $routes_sql);
$routes = [];
while($route = mysqli_fetch_assoc($routes_result)){
    $routes[] = $route;
}

// I calculated the price here in step 2
if($step == '2' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    $selected_driver_id = $_POST['driver_id'];
    $selected_route_id = $_POST['route_id'];
    $split = $_POST['split'];

    $driver_sql = "SELECT d_first_name, c_type FROM driver_info WHERE driver_id = '$selected_driver_id'";
    $driver_result = mysqli_query($conn, $driver_sql);
    $driver = mysqli_fetch_assoc($driver_result);

    $route_sql = "SELECT r_start, r_end, route_price FROM route WHERE route_id = '$selected_route_id'";
    $route_result = mysqli_query($conn, $route_sql);
    $route = mysqli_fetch_assoc($route_result);

    
    $sub_sql = "SELECT subscription_id FROM route_subscription WHERE passenger_id = '$passenger_id' AND route_id = '$selected_route_id' AND status = 'active' AND end_date > NOW()";
    $sub_result = mysqli_query($conn, $sub_sql);
    $has_subscription = mysqli_num_rows($sub_result) > 0;

    
    if($has_subscription) {
        $total_price = 0;
        $your_share = 0;
        $subscription_discount = true;
    } else {
        $total_price = $route['route_price'];
        $your_share = number_format($total_price / $split, 2);
        $subscription_discount = false;
    }
}

// In step 3 booking is saved and emergency contact is fetched from the database
$contact_found = false;
$emergency_name = "";
$emergency_phone = "";

if($step == '3' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    $selected_driver_id = $_POST['driver_id'];
    $selected_route_id = $_POST['route_id'];
    $your_share = $_POST['your_share'];

    // I updataed passenger route here
    $sql = "UPDATE passenger_info SET route_id = '$selected_route_id' WHERE passenger_id = '$passenger_id'";
    $result = mysqli_query($conn, $sql);

    // I fetched emergency contact from here
    $e_sql = "SELECT e_name_first, e_name_last, e_phone FROM passenger_emergency WHERE passenger_id = '$passenger_id'";
    $e_result = mysqli_query($conn, $e_sql);
    
    if($e_result && mysqli_num_rows($e_result) > 0){
        $e_row = mysqli_fetch_assoc($e_result);
        $emergency_name = $e_row['e_name_first'] . " " . $e_row['e_name_last'];
        $emergency_phone = $e_row['e_phone'];
        $contact_found = true;
    }
}


// iN STEP 4 I SAVED THE Rating
if($step == '4' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    $rating = $_POST['rating'];
    $driver_to_rate = $_POST['driver_to_rate'];

    $sql = "UPDATE driver_rating SET skill_rating = '$rating' WHERE driver_id = '$driver_to_rate'";
    mysqli_query($conn, $sql);
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Book Your Ride</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-10">
<div class="bg-white p-8 rounded shadow-md w-full max-w-lg">

    <?php if($step == '4'): ?>
        
        <h2 class="text-2xl font-bold mb-4 text-center">Thank You!</h2>
        <p class="text-green-600 text-xl font-bold text-center">Your rating has been submitted.</p>
        <a href="home.php" class="block mt-6 text-center text-blue-500 underline font-semibold">Return to Dashboard</a>

    <?php elseif($step == '3'): ?>
       
        <h2 class="text-2xl font-bold mb-4 text-center">Booking Confirmed!</h2>
        <p class="text-green-600 font-bold text-center mb-4">Ride booked! Your fare is $<?php echo $your_share; ?></p>

        
        <?php if($contact_found): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <p class="text-sm text-red-700 font-bold mb-2 text-center">Need Help?</p>
                <a href="tel:<?php echo htmlspecialchars($emergency_phone); ?>" class="block w-full bg-red-600 text-white text-center py-3 rounded font-bold text-lg hover:bg-red-700 shadow-sm transition">
                    🚨 Call <?php echo htmlspecialchars($emergency_name); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-3 mb-6 rounded">
                <p class="text-sm text-yellow-700 text-center"><em>No emergency contact is set in your profile.</em></p>
            </div>
        <?php endif; ?>

        <hr class="mb-4">

        <p class="text-gray-700 font-semibold mb-2 text-center">Rate your driver (1-5):</p>

        <form action="ride_booking.php" method="POST">
            <input type="hidden" name="step" value="4">
            <input type="hidden" name="driver_to_rate" value="<?php echo $selected_driver_id; ?>">

            <select name="rating" required class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:border-black">
                <option value="">Select rating</option>
                <option value="1">1 ⭐ (Poor)</option>
                <option value="2">2 ⭐ (Fair)</option>
                <option value="3">3 ⭐ (Good)</option>
                <option value="4">4 ⭐ (Very Good)</option>
                <option value="5">5 ⭐ (Excellent)</option>
            </select>

            <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition">
                Submit Rating
            </button>
        </form>
        <a href="home.php" class="block mt-4 text-center text-gray-500 hover:text-black underline text-sm">Return to Dashboard</a>

    <?php elseif($step == '2'): ?>
        
        <h2 class="text-2xl font-bold mb-6 text-center">Confirm Your Ride</h2>

        <div class="mb-6 p-5 bg-gray-50 border border-gray-200 rounded-lg shadow-inner">
            <p class="mb-2"><span class="font-semibold text-gray-600">Driver:</span> <span class="text-gray-900 font-medium"><?php echo $driver['d_first_name'] . " (" . $driver['c_type'] . ")"; ?></span></p>
            <p class="mb-2"><span class="font-semibold text-gray-600">Route:</span> <span class="text-gray-900 font-medium"><?php echo $route['r_start'] . " → " . $route['r_end']; ?></span></p>
            <p class="mb-2"><span class="font-semibold text-gray-600">Total Price:</span> <span class="text-gray-900 font-medium">$<?php echo number_format($total_price, 2); ?></span></p>
            <p class="mb-2"><span class="font-semibold text-gray-600">Split Between:</span> <span class="text-gray-900 font-medium"><?php echo $split; ?> person(s)</span></p>
            <div class="mt-4 pt-4 border-t border-gray-300">
                <?php if($subscription_discount): ?>
                    <div class="bg-green-50 border border-green-200 p-3 rounded">
                        <p class="text-center"><span class="font-semibold">Your Share:</span> <span class="text-green-600 font-extrabold text-lg">FREE</span></p>
                        <p class="text-center text-green-700 text-sm font-semibold mt-2">✅ Included in your subscription!</p>
                    </div>
                <?php else: ?>
                    <p class="text-xl text-center"><span class="font-semibold">Your Share:</span> <span class="text-green-600 font-extrabold">$<?php echo $your_share; ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <form action="ride_booking.php" method="POST">
            <input type="hidden" name="step" value="3">
            <input type="hidden" name="driver_id" value="<?php echo $selected_driver_id; ?>">
            <input type="hidden" name="route_id" value="<?php echo $selected_route_id; ?>">
            <input type="hidden" name="your_share" value="<?php echo $your_share; ?>">
            <input type="hidden" name="form_women_driver_pref" value="<?php echo $form_women_pref; ?>">

            <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition shadow-md">
                Confirm Booking
            </button>
        </form>
        <a href="ride_booking.php" class="block mt-4 text-center text-blue-500 hover:text-blue-700 underline text-sm font-semibold">Go back</a>

    <?php else: ?>
        <!-- This is step-1 -->
        <h2 class="text-3xl font-bold mb-2 text-center text-gray-800">Book a Ride</h2>
        <p class="text-center text-gray-500 mb-6">Find the perfect driver for your trip.</p>

        <form action="ride_booking.php" method="POST">
            <input type="hidden" name="step" value="2">

            <div class="mb-8">
                <label class="flex items-center gap-3 cursor-pointer p-3 bg-blue-50 rounded border border-blue-200">
                    <input type="checkbox" id="womenDriverCheckbox" name="form_women_driver_pref" value="1" <?php echo $form_women_pref ? 'checked' : ''; ?> class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                    <span class="text-gray-700 font-medium">👩 I prefer a women driver only</span>
                </label>
            </div>

            <div class="mb-5">
                <label class="block text-gray-700 font-bold mb-2">Select Driver & Vibe</label>
                <select name="driver_id" required class="w-full border border-gray-300 rounded px-3 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Choose a driver...</option>
                    <?php foreach($drivers_with_vibes as $d): ?>
                        <option value="<?php echo $d['driver_id']; ?>" data-gender="<?php echo $d['gender'] ?? 'Not Specified'; ?>">
                            <?php echo $d['match_pct']; ?>% Match | <?php echo $d['d_first_name']; ?> (<?php echo $d['c_type']; ?>) - [<?php echo $d['vibe_string']; ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Drivers are sorted by how well their vibes match your profile preferences!</p>
            </div>

            <div class="mb-5">
                <label class="block text-gray-700 font-bold mb-2">Select Route</label>
                <select name="route_id" required class="w-full border border-gray-300 rounded px-3 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Choose a route...</option>
                    <?php foreach($routes as $route): ?>
                        <option value="<?php echo $route['route_id']; ?>">
                            <?php echo $route['r_start'] . " → " . $route['r_end']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-8">
                <label class="block text-gray-700 font-bold mb-2">Split Fare With</label>
                <select name="split" class="w-full border border-gray-300 rounded px-3 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="1">No splitting (just me)</option>
                    <option value="2">1 other person (split between 2)</option>
                    <option value="3">2 other people (split between 3)</option>
                    <option value="4">3 other people (split between 4)</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-black text-white py-3 rounded font-bold text-lg hover:bg-gray-800 transition shadow-md">
                Calculate Fare
            </button>
            <a href="home.php" class="block mt-4 text-center text-gray-500 hover:text-black underline text-sm">Cancel</a>
        </form>
    <?php endif; ?>

</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const womenCheckbox = document.getElementById('womenDriverCheckbox');
    const driverSelect = document.querySelector('select[name="driver_id"]');
    
    if (womenCheckbox && driverSelect) {
        // In here original data with gender option is stored
        const allOptions = Array.from(driverSelect.options).slice(1).map(opt => ({
            value: opt.value,
            text: opt.text,
            gender: opt.getAttribute('data-gender') || 'unknown'
        }));
        
        
        function updateDriverList() {
            const showWomenOnly = womenCheckbox.checked;
            driverSelect.innerHTML = '<option value="">Choose a driver...</option>';
            
            allOptions.forEach(opt => {
                if (showWomenOnly && opt.gender !== 'Female') {
                    // Skip non-female drivers when women-only is checked
                    return;
                }
                
                const newOpt = document.createElement('option');
                newOpt.value = opt.value;
                newOpt.text = opt.text;
                newOpt.setAttribute('data-gender', opt.gender);
                driverSelect.appendChild(newOpt);
            });
        }
        
        
        updateDriverList();
        
        
        womenCheckbox.addEventListener('change', updateDriverList);
    }
});
</script>

</body>
</html>
