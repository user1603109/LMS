<div class="container-fluid">
	<h4 class="mb-3">Search Results for "<?= htmlspecialchars($query) ?>"</h4>
	<?php if (empty($results)): ?>
		<div class="alert alert-info">No results found.</div>
	<?php else: ?>
		<ul class="list-group shadow-sm">
			<?php foreach ($results as $r): ?>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<span>
						<span class="badge bg-gold me-2 text-dark"><?= htmlspecialchars($r['type']) ?></span>
						<?= htmlspecialchars($r['label']) ?>
					</span>
					<i class="bi bi-chevron-right"></i>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>