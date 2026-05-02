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

// Handle subscription creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subscribe'])) {
    $route_id = intval($_POST['route_id']);
    $subscription_type = $_POST['subscription_type'];
    
    if(!empty($route_id) && !empty($subscription_type)){
        // Calculate end date based on subscription type
        $end_date = new DateTime();
        switch($subscription_type) {
            case '1week':
                $end_date->modify('+7 days');
                break;
            case '1month':
                $end_date->modify('+1 month');
                break;
            case '3months':
                $end_date->modify('+3 months');
                break;
            case '6months':
                $end_date->modify('+6 months');
                break;
            case '1year':
                $end_date->modify('+1 year');
                break;
        }
        
        // Check if subscription already exists
        $check_sql = "SELECT subscription_id FROM route_subscription WHERE passenger_id = '$passenger_id' AND route_id = '$route_id' AND status = 'active'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if($check_result && mysqli_num_rows($check_result) > 0){
            // Update existing subscription
            $sub_sql = "UPDATE route_subscription SET subscription_type = ?, end_date = ? WHERE passenger_id = ? AND route_id = ? AND status = 'active'";
            $stmt = $conn->prepare($sub_sql);
            $stmt->bind_param("ssii", $subscription_type, $end_date->format('Y-m-d H:i:s'), $passenger_id, $route_id);
            $stmt->execute();
            $stmt->close();
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">✅ Subscription updated successfully!</div>';
        } else {
            // Create new subscription
            $sub_sql = "INSERT INTO route_subscription (passenger_id, route_id, subscription_type, end_date) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sub_sql);
            $stmt->bind_param("iiss", $passenger_id, $route_id, $subscription_type, $end_date->format('Y-m-d H:i:s'));
            if($stmt->execute()){
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">✅ Subscription created successfully!</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch all routes
$routes_sql = "SELECT route_id, r_start, r_end, route_price FROM route WHERE no_go_flag = 0 ORDER BY r_start ASC";
$routes_result = mysqli_query($conn, $routes_sql);
$routes = [];
while($route = mysqli_fetch_assoc($routes_result)){
    $routes[] = $route;
}

// Get user's existing subscriptions
$user_subs_sql = "SELECT route_id FROM route_subscription WHERE passenger_id = '$passenger_id' AND status = 'active'";
$user_subs_result = mysqli_query($conn, $user_subs_sql);
$user_subscribed_routes = [];
while($sub = mysqli_fetch_assoc($user_subs_result)){
    $user_subscribed_routes[] = $sub['route_id'];
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Subscribe to Routes - Routly</title>
</head>
<body class="bg-gray-100 min-h-screen py-10">

<div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-md">
    
    <!-- Top Navigation -->
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-3xl font-bold text-gray-800">📅 Subscribe to Routes</h2>
        <a href="subscriptions.php" class="text-blue-600 hover:underline font-semibold">&larr; My Subscriptions</a>
    </div>

    <?php echo $message; ?>

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
        <p class="text-blue-900"><strong>💡 Tip:</strong> Subscribe to your favorite routes and get 10-30% discounts!</p>
    </div>

    <?php if (empty($routes)): ?>
        <div class="text-center py-12">
            <p class="text-gray-600 text-lg">No routes available at the moment.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($routes as $route): 
                $is_subscribed = in_array($route['route_id'], $user_subscribed_routes);
            ?>
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($route['r_start'] . " → " . $route['r_end']); ?>
                            </h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Route ID: #<?php echo $route['route_id']; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($route['route_price'], 2); ?></p>
                            <p class="text-gray-600 text-sm">per trip</p>
                        </div>
                    </div>

                    <?php if ($is_subscribed): ?>
                        <div class="bg-green-50 border border-green-200 p-3 rounded text-center">
                            <p class="text-green-800 font-semibold">✅ You are subscribed to this route</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="route_id" value="<?php echo $route['route_id']; ?>">
                            <input type="hidden" name="subscribe" value="1">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 font-semibold mb-2">Choose Subscription Duration</label>
                                <select name="subscription_type" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-black">
                                    <option value="">Select a plan...</option>
                                    <option value="1week">1 Week (10% off) - $<?php echo number_format($route['route_price'] * 7 * 0.9, 2); ?></option>
                                    <option value="1month">1 Month (15% off) - $<?php echo number_format($route['route_price'] * 30 * 0.85, 2); ?></option>
                                    <option value="3months">3 Months (20% off) - $<?php echo number_format($route['route_price'] * 90 * 0.80, 2); ?></option>
                                    <option value="6months">6 Months (25% off) - $<?php echo number_format($route['route_price'] * 180 * 0.75, 2); ?></option>
                                    <option value="1year">1 Year (30% off) - $<?php echo number_format($route['route_price'] * 365 * 0.70, 2); ?></option>
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded font-bold text-lg hover:bg-green-700 transition">
                                ✅ Subscribe to This Route
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
