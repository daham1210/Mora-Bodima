document.addEventListener('DOMContentLoaded', () => {
    const reviewForm = document.getElementById('ajaxReviewForm');
    if (!reviewForm) return;

    reviewForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msgBox = document.getElementById('reviewMsg');
        const formData = new FormData(reviewForm);

        try {
            const response = await fetch(reviewForm.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            msgBox.style.display = 'block';
            msgBox.className = 'alert ' + (result.status === 'success' ? 'alert-success' : 'alert-error');
            msgBox.textContent = result.message;

            if (result.status === 'success') {
                setTimeout(() => window.location.reload(), 900);
            }
        } catch (err) {
            msgBox.style.display = 'block';
            msgBox.className = 'alert alert-error';
            msgBox.textContent = 'An error occurred while submitting your review.';
        }
    });
});