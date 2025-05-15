<?php
    include 'dbConnect.php';
    include 'adminSessionHandler.php';

    if (!isset($_GET['memberID']) || !isset($_GET['activityID'])) {
        die("Member ID or Activity ID not provided.");
    }

    $memberID = intval($_GET['memberID']);
    $activityID = intval($_GET['activityID']);

    //fetch the activity name for the selected activityID
    $activityNameQuery = $conn->prepare("SELECT activityName FROM Activities WHERE activityID = ?");
    $activityNameQuery->bind_param("i", $activityID);
    $activityNameQuery->execute();
    $activityNameResult = $activityNameQuery->get_result();
    $activityName = $activityNameResult->fetch_assoc()['activityName'] ?? 'Unknown';

    //fetch available schedules 
    $schedulesQuery = $conn->prepare(
        "SELECT activityID, activityName, schedule 
         FROM Activities 
         WHERE activityName = (SELECT activityName FROM Activities WHERE activityID = ?)"
    );
    $schedulesQuery->bind_param("i", $activityID);
    $schedulesQuery->execute();
    $schedulesResult = $schedulesQuery->get_result();

    // fetch the original schedule 
    $originalScheduleQuery = $conn->prepare(
        "SELECT activityID FROM ActivityBookings WHERE memberID = ? AND activityID = ?"
    );
    $originalScheduleQuery->bind_param("ii", $memberID, $activityID);
    $originalScheduleQuery->execute();
    $originalScheduleResult = $originalScheduleQuery->get_result();
    $originalActivityID = $originalScheduleResult->num_rows > 0 ? $originalScheduleResult->fetch_assoc()['activityID'] : null;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Enrolled Classes - Hustle Core</title>
    <link rel="stylesheet" href="HustleCoreStyles.css">
</head>
<body class="adminBody">
    <!-- Top bar -->
    <header class="adminHeader">
        <a href="adminDashboard.php"> <img class="adminGymLogo" src="images/logo.png" alt="gym logo" > </img> </a>
        
        <div class="adminUser">
        <span><?php echo htmlspecialchars($adminName); ?></span>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="logout">Log out</button>
        </form>
        </div>
        
    </header>
    
    
    <main class="adminMain">
        <!-- Sidebar -->
        <div class="adminSideBar">
            <h4>Admin Panel</h4>
            <p class="adminOtherPage"><a href="adminDashboard.php">Dashboard</a></p>
            <p class="adminOtherPage"><a href="adminManageActivities.php">Manage Classes & Sessions</a></p>
            <p class="adminCurrentPage"><a href="adminMembers.php">Members</a></p>
        </div>

        <!-- Content Area -->
        <div class="adminContent">
            <div class="adminBreadcrumb">&gt; <a href="adminMembers.php">Members</a>&gt; <a href="adminViewMemberClasses.php?memberID=<?= $memberID ?>"> View Classes </a> &gt; Change Schedule </div>
            <h2 class="adminHeading2">Available Schedules for: <?= htmlspecialchars($activityName) ?></h2>

            <table border="1" class="adminScheduleTable">
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $schedulesResult->fetch_assoc()): ?>
                        <?php if ($row['activityID'] !== $originalActivityID): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['activityName']) ?></td>
                                <td><?= htmlspecialchars($row['schedule']) ?></td>
                                <td>
                                    <form method="post" action="adminEnrollMemberHandler.php">
                                        <input type="hidden" name="activityID" value="<?= $row['activityID'] ?>">
                                        <input type="hidden" name="memberID" value="<?= $memberID ?>">
                                        <button type="submit" class="adminEnrollBtn">Enroll</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>

    </main>
</body>
</html>