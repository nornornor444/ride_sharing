<?php
session_start();
require_once 'DBConnect.php';

if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit();
}

$passenger_id = $_SESSION['passenger_id'];
$message = "";


if (isset($_GET['success'])) {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">✅ <strong>Success!</strong> You have subscribed to this route.</div>';
}


if (isset($_POST['cancel_subscription'])) {
    $subscription_id = $_POST['subscription_id'];
    
    $cancel_sql = "UPDATE route_subscription SET status = 'cancelled' WHERE subscription_id = ? AND passenger_id = ?";
    $stmt = $conn->prepare($cancel_sql);
    $stmt->bind_param("ii", $subscription_id, $passenger_id);
    
    if ($stmt->execute()) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">Subscription cancelled successfully.</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">Error cancelling subscription.</div>';
    }
    $stmt->close();
}


$subs_sql = "SELECT rs.*, r.r_start, r.r_end, r.route_price 
             FROM route_subscription rs 
             LEFT JOIN route r ON rs.route_id = r.route_id 
             WHERE rs.passenger_id = ? 
             ORDER BY rs.status DESC, rs.end_date DESC";

$stmt = $conn->prepare($subs_sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$subs_result = $stmt->get_result();
$subscriptions = [];
while ($row = $subs_result->fetch_assoc()) {
    $subscriptions[] = $row;
}
$stmt->close();


function formatSubscriptionType($type) {
    $types = [
        '1week' => '1 Week',
        '1month' => '1 Month',
        '3months' => '3 Months',
        '6months' => '6 Months',
        '1year' => '1 Year'
    ];
    return $types[$type] ?? $type;
}


function getStatusBadge($status) {
    switch ($status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'expired':
            return 'bg-gray-100 text-gray-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>My Route Subscriptions - Routly</title>
</head>
<body class="bg-gray-100 min-h-screen py-10">

<div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-md">
    
    <!-- Top Navigation -->
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-3xl font-bold text-gray-800">📅 My Route Subscriptions</h2>
        <a href="home.php" class="text-blue-600 hover:underline font-semibold">&larr; Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <?php if (empty($subscriptions)): ?>
        <div class="text-center py-12">
            <p class="text-gray-600 text-lg mb-6">You haven't subscribed to any routes yet.</p>
            <a href="subscribe_route.php" class="inline-block bg-black text-white py-3 px-6 rounded font-bold hover:bg-gray-800 transition">
                📅 Browse Routes & Subscribe
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($subscriptions as $sub): ?>
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($sub['r_start'] . " → " . $sub['r_end']); ?>
                            </h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Route ID: #<?php echo $sub['route_id']; ?>
                            </p>
                        </div>
                        <span class="<?php echo getStatusBadge($sub['status']); ?> px-4 py-2 rounded-full font-semibold text-sm uppercase">
                            <?php echo ucfirst($sub['status']); ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-600">Plan Duration</p>
                            <p class="font-semibold text-gray-900"><?php echo formatSubscriptionType($sub['subscription_type']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Monthly Rate</p>
                            <p class="font-semibold text-gray-900">$<?php echo number_format($sub['route_price'], 2); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Started</p>
                            <p class="font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Expires</p>
                            <p class="font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></p>
                        </div>
                    </div>

                    <?php if ($sub['status'] == 'active'): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                            <input type="hidden" name="cancel_subscription" value="1">
                            <input type="hidden" name="subscription_id" value="<?php echo $sub['subscription_id']; ?>">
                            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded font-semibold hover:bg-red-700 transition">
                                ✕ Cancel Subscription
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Want to add more subscriptions?</h3>
            <a href="subscribe_route.php" class="inline-block bg-black text-white py-3 px-6 rounded font-bold hover:bg-gray-800 transition">
                + Subscribe to Another Route
            </a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
