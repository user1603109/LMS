<div class="container min-vh-100 d-flex align-items-center justify-content-center">
	<div class="login-card p-4 p-md-5 rounded-4 shadow-lg position-relative">
		<div class="floating-orb orb-1"></div>
		<div class="floating-orb orb-2"></div>
		<h1 class="h3 mb-3 fw-bold text-center text-gold">ASC Library Management</h1>
		<p class="text-center text-muted mb-4">Welcome back. Please sign in.</p>
		<?php if (!empty($error)): ?>
			<div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>
		<form method="post" action="/login" novalidate>
			<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
			<div class="mb-3">
				<label class="form-label">Username</label>
				<input type="text" class="form-control form-control-lg" name="username" required>
			</div>
			<div class="mb-3">
				<label class="form-label">Password</label>
				<input type="password" class="form-control form-control-lg" name="password" required>
			</div>
			<button type="submit" class="btn btn-gold w-100 btn-lg">Sign In</button>
			<div class="text-center small mt-3 text-muted">Roles: Admin, Librarian, Student</div>
		</form>
	</div>
</div>