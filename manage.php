<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<link rel="stylesheet" href="css/manage.css"/>
<link rel="stylesheet" href="css/navbar.css">
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">

      <div class="menu">

        <a href="#">
          <img src="images/dashboard.png" class="sidebar-icon">
          Dashboard
        </a>

        <a href="#" class="active">
          <img src="images/manageevent.png" class="sidebar-icon">
          Manage Events
        </a>

        <a href="#">
          <img src="images/person2.png" class="sidebar-icon">
          Profile
        </a>

        <a href="#">
          <img src="images/analytics.png" class="sidebar-icon">
          Analytics
        </a>

        <a href="#">
          <img src="images/permission.png" class="sidebar-icon">
          Permission Control
        </a>

        <a href="#">
          <img src="images/settings.png" class="sidebar-icon">
          Settings
        </a>

      </div>

    </div>

    <div class="sidebar-bottom">

      <button class="org-btn">
        View Organization Page
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
      </button>

    </div>

  </div>
  <div class="main">
    <nav>
        <div class="container nav-container">
            <a href="#">
                <img src="images/logo.png" alt="Suni Logo">
            </a>
            <ul>
                <li><a href="#" class="active">CvSU Events</a></li>
                <li><a href="#" class="active">My Profile</a></li>
                <li><a href="#" class="active">Organization Dashboard</a></li>
                <li><img src="images/sid.png" class="profile"></li>
            </ul>
        </div>
    </nav>
    <div class="title-row">
      <h1>Manage events</h1>

      <a href="#" class="create-event">+ Create Event</a>
    </div>
    <div class="divider"></div>
    <div class="events">
      <div class="event-card">

        <div class="status">
          Active ●
        </div>
        <div class="event-img">
          <img src="images/stardew.png" alt="">
        </div>

        <div class="event-content">

          <h2>Hydrofest</h2>

          <p class="description">
            The best for HydroFest—an ultimate water fight experience!
            Grab your friends, get your team, and prepare for nonstop
            good vibes.
          </p>

          <div class="details">

            <div class="detail">
              <img src="images/calendar.png" class="btnIcon">
              April 25, 2026
            </div>

            <div class="detail">
              <img src="images/clock.png" class="btnIcon">
              5:00 PM – 10:00 PM
            </div>

            <div class="detail">
              <img src="images/location.png" class="btnIcon">
              CvSU – University Oval
            </div>

            <div class="detail">
              <img src="images/register.png" class="btnIcon">
              128 Registered
            </div>

          </div>

          <div class="card-buttons">

            <a href="#" class="view-btn">
              <img src="images/openview.png" alt="" class="btn-icon">
              View Event Page
            </a>

            <a href="#" class="manage-link">
              <i class="fa-regular fa-pen-to-square"></i>
              Manage Event
            </a>

          </div>

        </div>

      </div>
      <div class="event-card">

        <div class="status">
          Active ●
        </div>
        <div class="event-img">
          <img src="images/stardew.png" alt="">
        </div>
        <div class="event-content">
          <h2>Hydrofest</h2>
          <p class="description">
            The best for HydroFest—an ultimate water fight experience!
            Grab your friends, get your team, and prepare for nonstop
            good vibes.
          </p>
          <div class="details">
           <div class="detail">
              <img src="images/calendar.png" class="btnIcon">
              April 25, 2026
            </div>
            <div class="detail">
              <img src="images/clock.png" class="btnIcon">
              5:00 PM – 10:00 PM
            </div>
            <div class="detail">
              <img src="images/location.png" class="btnIcon">
              CvSU – University Oval
            </div>
            <div class="detail">
              <img src="images/register.png" class="btnIcon">
              128 Registered
            </div>
          </div>
          <div class="card-buttons">
            <a href="#" class="view-btn">
              <img src="images/openview.png" alt="" class="btn-icon">
              View Event Page
            </a>
            <a href="#" class="manage-link">
              <i class="fa-regular fa-pen-to-square"></i>
              Manage Event
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>