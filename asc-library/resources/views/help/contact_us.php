<div class="container">
	<h3>Contact Us / Send Report</h3>
	<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
	<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
	<form method="post" action="/help/send-report">
		<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
		<div class="mb-3">
			<label class="form-label">Message</label>
			<textarea name="message" class="form-control" rows="4" required></textarea>
		</div>
		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Contact Email</label>
				<input type="email" name="email" class="form-control">
			</div>
			<div class="col-md-6">
				<label class="form-label">Contact Phone</label>
				<input type="text" name="phone" class="form-control">
			</div>
		</div>
		<button class="btn btn-gold mt-3" type="submit">Send</button>
	</form>
</div>