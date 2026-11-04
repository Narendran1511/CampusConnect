<?php $layout = $layout ?? 'public'; ?>
<?php if ($layout === 'admin' && current_user()): ?>
    </main>
  </div>
</div>
<?php elseif ($layout === 'student' && current_user()): ?>
</main>
<footer class="site-foot">CampusConnect &middot; College event portal</footer>
<?php else: ?>
</main>
<?php endif; ?>
<script src="<?= e(url('assets/js/script.js')) ?>"></script>
</body>
</html>
