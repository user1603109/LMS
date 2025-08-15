<?php
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= isset($title) ? htmlspecialchars($title) : APP_NAME ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="/assets/css/theme.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
	<div class="glow-overlay"></div>
	<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-blue sticky-top shadow-sm">
		<div class="container-fluid">
			<a class="navbar-brand fw-bold text-gold" href="<?= App\Core\Auth::check() ? '/dashboard' : '/login' ?>">ASC LMS</a>
			<?php if (App\Core\Auth::check()): ?>
			<form class="d-flex flex-grow-1 mx-3" action="/search" method="get" role="search">
				<div class="input-group futuristic-input">
					<span class="input-group-text"><i class="bi bi-search"></i></span>
					<input class="form-control" type="search" placeholder="Search the library..." aria-label="Search" name="q" value="<?= htmlspecialchars($query ?? '') ?>">
				</div>
			</form>
			<div class="d-flex align-items-center">
				<span class="me-3 small">Hello, <?= htmlspecialchars(($user['username'] ?? '') ?: (App\Core\Auth::user()['username'] ?? 'Guest')) ?></span>
				<a class="btn btn-sm btn-outline-gold" href="/logout">Logout</a>
			</div>
			<?php endif; ?>
		</div>
	</nav>
	<div class="container-fluid">
		<div class="row flex-nowrap">
			<?php if (App\Core\Auth::check()): ?>
			<aside class="col-auto px-0 sidebar bg-gradient-deep-blue text-light">
				<div class="sidebar-sticky pt-3">
					<ul class="nav nav-pills flex-column mb-auto">
						<li class="nav-item text-uppercase text-gold small px-3 mt-3">Files</li>
						<li><a href="/cataloging/books" class="nav-link">Books</a></li>
						<li><a href="/cataloging/audio-visual" class="nav-link">Audio-Visual Materials</a></li>
						<li><a href="/cataloging/academic-coursework" class="nav-link">Academic Coursework</a></li>
						<li><a href="/cataloging/electronic-resources" class="nav-link">Electronic Resources</a></li>
						<li><a href="/cataloging/audio-records" class="nav-link">Audio Records</a></li>
						<li><a href="/cataloging/video-records" class="nav-link">Video Records</a></li>
						<li><a href="/cataloging/serials" class="nav-link">Serials</a></li>
						<li><a href="/cataloging/patron" class="nav-link">Patron</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Circulation</li>
						<li><a href="/circulation/borrow-return" class="nav-link">Borrow-Return</a></li>
						<li><a href="/circulation/reservations" class="nav-link">Resource Reservation</a></li>
						<li><a href="/circulation/fines-payment" class="nav-link">Fines & Payment</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Inventory</li>
						<li><a href="/inventory/management" class="nav-link">Management</a></li>
						<li><a href="/inventory/acquisition" class="nav-link">Acquisition</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Reports</li>
						<li><a href="/reports/accession-list" class="nav-link">Accession List</a></li>
						<li><a href="/reports/patron-masterlist" class="nav-link">Patron Masterlist</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Administration</li>
						<li><a href="/administration/staff" class="nav-link">Staff Management</a></li>
						<li><a href="/administration/roles" class="nav-link">Roles & Permissions</a></li>
						<li><a href="/administration/users" class="nav-link">User Admin</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Settings</li>
						<li><a href="/settings/export-backups" class="nav-link">Export & Backups</a></li>
						<li><a href="/settings/audit-logs" class="nav-link">Audit Logs</a></li>
						<li><a href="/settings/user-settings" class="nav-link">User Settings</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">About</li>
						<li><a href="/about/system" class="nav-link">System</a></li>
						<li><a href="/about/developers" class="nav-link">Developers</a></li>

						<li class="nav-item text-uppercase text-gold small px-3 mt-4">Help</li>
						<li><a href="/help/contact-us" class="nav-link">Contact Us</a></li>
					</ul>
				</div>
			</aside>
			<?php endif; ?>
			<main class="<?= App\Core\Auth::check() ? 'col' : 'col-12' ?> py-4 content-area">
				<?php App\Core\View::content($view, get_defined_vars()); ?>
			</main>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
	<script src="/assets/js/theme.js"></script>
</body>
</html>