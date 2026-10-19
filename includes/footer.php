<?php
// includes/footer.php
?>
    </main>
  </div>
</div>

<!-- Toast container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
  <div id="toastContainer"></div>
</div>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Simple function to show a toast (message, type: 'success'|'danger'|'info')
function showToast(message, type='info') {
    const toastId = 'toast_' + Date.now();
    const toastHTML = `
    <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>`;
    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHTML);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl);
    bsToast.show();
}

// If there is a flash message set by PHP in session, display it
<?php if (isset($_SESSION['flash'])): ?>
    document.addEventListener('DOMContentLoaded', function(){
        showToast('<?php echo addslashes($_SESSION['flash']['msg']); ?>', '<?php echo $_SESSION['flash']['type']; ?>');
    });
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
</script>
</body>
</html>
