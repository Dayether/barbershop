<script src="js/admin.js"></script>

<!-- IziToast global settings -->
<script>
    // Set global IziToast options
    iziToast.settings({
        timeout: 5000,
        resetOnHover: true,
        transitionIn: 'fadeInDown',
        transitionOut: 'fadeOutUp',
        position: 'topRight',
        closeOnEscape: true,
        progressBar: true
    });
    
    // Default success notification
    function showSuccess(message, title = 'Success') {
        iziToast.success({
            title: title,
            message: message
        });
    }
    
    // Default error notification
    function showError(message, title = 'Error') {
        iziToast.error({
            title: title,
            message: message
        });
    }
    
    // Default warning notification
    function showWarning(message, title = 'Warning') {
        iziToast.warning({
            title: title,
            message: message
        });
    }
    
    // Default info notification
    function showInfo(message, title = 'Info') {
        iziToast.info({
            title: title,
            message: message
        });
    }
</script>

<!-- Scripts for AJAX operations -->
<script>
    // Generic AJAX function for form submissions with notifications
    function submitFormWithAjax(formId, successCallback) {
        document.getElementById(formId).addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            
            // Show loading state
            const submitBtn = form.querySelector('[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            fetch(form.action, {
                method: form.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    showSuccess(data.message);
                    if (successCallback) successCallback(data);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                // Reset button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                
                showError("An error occurred. Please try again.");
                console.error('Error:', error);
            });
        });
    }
</script>
</body>
</html>
